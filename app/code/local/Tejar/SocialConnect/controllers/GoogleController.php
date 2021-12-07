<?php
/**
 * Tejar is not affiliated with or in any way responsible for this code.
 *
 * Commercial support is available directly from the [extension author](http://www.techytalk.info/contact/).
 *
 * @category Marko-M
 * @package SocialConnect
 * @author Marko Martinović <marko@techytalk.info>
 * @copyright Copyright (c) Marko Martinović (http://www.techytalk.info)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Tejar_SocialConnect_GoogleController extends Tejar_SocialConnect_Controller_Abstract
{
    protected function _disconnectCallback(Mage_Customer_Model_Customer $customer) {
        Mage::helper('tejar_socialconnect/google')->disconnect($customer);
        Mage::getSingleton('core/session')
            ->addSuccess(
                $this->__('You have successfully disconnected your Google account from our store account.')
            );
    }

    protected function _connectCallback() {

		//--- Initialize a variable to hold the Redirect URL ...
		$redirectUrl = null;

		//--- Check session variable to generate the Redirect URL after Successfull Login....

		if(Mage::getSingleton('core/session')->getSocialRedirectFlag()=="buyNow"){
			$redirectUrl = 'window.opener.location.replace("'.Mage::getBaseUrl().'checkout/onepage");';
		}else{
			if(Mage::getStoreConfigFlag(Mage_Customer_Helper_Data::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD)){

				$redirectUrl = 'window.opener.location.replace("'.Mage::getBaseUrl().'customer/account");';
			}else{
				$redirectUrl = 'window.opener.location.reload()';
			}
		}

    $session = Mage::getSingleton('customer/session');

		if ($session->getBeforeAuthUrl()) {
			preg_match('/[\/]+[productalert]+[\/]+[add]+[\/]/', $session->getBeforeAuthUrl(), $match);
			if(!empty($match)){
				$redirectUrl = 'window.opener.location.replace("'.$session->getBeforeAuthUrl().'");';
			}
		}

		//--- Unset session variable SocialRedirectFlag once we have set the redirection URL..
		Mage::getSingleton('core/session')->unsSocialRedirectFlag();

        $errorCode = $this->getRequest()->getParam('error');
        $code = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');
			//echo Mage::getSingleton('core/session')->getGoogleCsrf()." --- ".$state;die;
        if(!($errorCode || $code) && !$state) {
            // Direct route access - deny
           exit("<script>window.close();".$redirectUrl.";</script>");
        }

		//--- If $state is not equals to the csrf token then redirect to the login page..
        if(!$state || $state != Mage::getSingleton('core/session')->getGoogleCsrf()) {
           $redirectToLogin = Mage::getUrl('customer/account/login');
		   exit("<script>window.close();window.opener.location.href='".$redirectToLogin."'</script>");
        }

 //var_dump(Mage::getSingleton('core/session')->getGoogleCsrf());die;
        if($errorCode) {
            // Google API red light - abort
            if($errorCode === 'access_denied') {
                Mage::getSingleton('core/session')
                    ->addNotice(
                        $this->__('Google Connect process aborted.')
                    );
                return $this;
            }

            throw new Exception(
                sprintf(
                    $this->__('Sorry, "%s" error occured. Please try again.'),
                    $errorCode
                )
            );
        }

        if ($code) {

            // Google API green light - proceed
            $info = Mage::getModel('tejar_socialconnect/google_info')->load();
            /* @var $info Tejar_SocialConnect_Model_Google_Info */

			// --- Confirm User if user is not Confirmed...
			$customer = Mage::getModel('customer/customer');
			//-- Set Customer Website Id.. (Resolving Issue in new Install)
			$customer->setWebsiteId(Mage::app()->getWebsite()->getId());
			$customer->loadByEmail($info->getEmail());
			//--- Set Session Customer ID
			Mage::getSingleton('customer/session')->setCustomerId($customer->getId());
			if($customer->getConfirmation()!== null){
				 $customer->setConfirmation(null);
				 $customer->save();
			}

            $token = $info->getClient()->getAccessToken();

            $customersByGoogleId = Mage::helper('tejar_socialconnect/google')
                ->getCustomersByGoogleId($info->getId());

            if(Mage::getSingleton('customer/session')->isLoggedIn()) {

                // Logged in user
                if($customersByGoogleId->getSize()) {
                    // Google account already connected to other account - deny
                    Mage::getSingleton('core/session')
                        ->addNotice(
                            $this->__('Your Google account is already connected to one of our store accounts.')
                        );
					//---ZEE CODE (To close the popup and realod the 'PARENT' page)
						exit("<script>window.close();".$redirectUrl.";</script>");
                    return $this;
                }

                // Connect from account dashboard - attach
                $customer = Mage::getSingleton('customer/session')->getCustomer();

                Mage::helper('tejar_socialconnect/google')->connectByGoogleId(
                    $customer,
                    $info->getId(),
                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                    $this->__('Your Google account is now connected to your store account. You can now login using our Google Login button or using store account credentials you will receive to your email address.')
                );
				//---ZEE CODE (To close the popup and realod the 'PARENT' page)
				exit("<script>window.close();".$redirectUrl.";</script>");
                return $this;
            }

            if($customersByGoogleId->getSize()) {
                // Existing connected user - login
                $customer = $customersByGoogleId->getFirstItem();

                Mage::helper('tejar_socialconnect/google')->loginByCustomer($customer);

                Mage::getSingleton('core/session')
                    ->addSuccess(
                        $this->__('You have successfully logged in using your Google account.')
                    );
				//---ZEE CODE (To close the popup and realod the 'PARENT' page)
				exit("<script>window.close();".$redirectUrl.";</script>");
                return $this;
            }

            $customersByEmail = Mage::helper('tejar_socialconnect/google')
                ->getCustomersByEmail($info->getEmail());

            if($customersByEmail->getSize())  {
                // Email account already exists - attach, login
                $customer = $customersByEmail->getFirstItem();

                Mage::helper('tejar_socialconnect/google')->connectByGoogleId(
                    $customer,
                    $info->getId(),
                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                    $this->__('We have discovered you already have an account at our store. Your Google account is now connected to your store account.')
                );

				//---ZEE CODE (To close the popup and realod the 'PARENT' page)
				exit("<script>window.close();".$redirectUrl.";</script>");
                return $this;
            }

            // New connection - create, attach, login
            $givenName = $info->getGivenName();
            if(empty($givenName)) {
                throw new Exception(
                    $this->__('Sorry, could not retrieve your Google first name. Please try again.')
                );
            }

            $familyName = $info->getFamilyName();
            if(empty($familyName)) {
                throw new Exception(
                    $this->__('Sorry, could not retrieve your Google last name. Please try again.')
                );
            }

            Mage::helper('tejar_socialconnect/google')->connectByCreatingAccount(
                $info->getEmail(),
                $info->getGivenName(),
                $info->getFamilyName(),
                $info->getId(),
                $token
            );

            Mage::getSingleton('core/session')->addSuccess(
                $this->__('Your Google account is now connected to your new user account at our store. Now you can login using our Google Login button.')
            );
			exit("<script>window.close();".$redirectUrl.";</script>");
        }
    }

}
