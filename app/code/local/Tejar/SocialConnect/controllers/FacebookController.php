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

class Tejar_SocialConnect_FacebookController extends Tejar_SocialConnect_Controller_Abstract
{

    protected function _disconnectCallback(Mage_Customer_Model_Customer $customer) {
        Mage::helper('tejar_socialconnect/facebook')->disconnect($customer);
        Mage::getSingleton('core/session')
            ->addSuccess(
                $this->__('You have successfully disconnected your Facebook account from our store account.')
            );
    }

    protected function _connectCallback() {
		//--- Initialize a variable to hold the Redirect URL ...
		$redirectUrl = null;

		//--- Check session variable to generate the Redirect URL after Successfull Login....
		if(Mage::getSingleton('core/session')->getSocialRedirectFlag() =="buyNow"){

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

		Mage::getSingleton('core/session')->unsSocialRedirectFlag();
        $errorCode = $this->getRequest()->getParam('error');
        $code = $this->getRequest()->getParam('code');
        $state = $this->getRequest()->getParam('state');

        if(!($errorCode || $code) && !$state) {
            // Direct route access - deny
            //return $this;
			exit("<script>window.close();".$redirectUrl.";</script>");
        }

	//--- If $state is not equals to the csrf token then redirect to the login page..
        if(!$state || $state != Mage::getSingleton('core/session')->getFacebookCsrf()) {
          $redirectToLogin = Mage::getUrl('customer/account/login');
		   exit("<script>window.close();window.opener.location.href='".$redirectToLogin."'</script>");
        }

        if($errorCode) {
            // Facebook API read light - abort
            if($errorCode === 'access_denied') {
                Mage::getSingleton('core/session')
                    ->addNotice(
                        $this->__('Facebook Connect process aborted.')
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
            /** @var Tejar_SocialConnect_Helper_Facebook $helper */
            $helper = Mage::helper('tejar_socialconnect/facebook');

            // Facebook API green light - proceed
            /** @var Tejar_SocialConnect_Model_Facebook_Info $info */
            $info = Mage::getModel('tejar_socialconnect/facebook_info');

            $token = $info->getClient()->getAccessToken($code);

            $info->load();
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
            $customersByFacebookId = $helper->getCustomersByFacebookId($info->getId());

            if(Mage::getSingleton('customer/session')->isLoggedIn()) {
                // Logged in user
                if($customersByFacebookId->getSize()) {
                    // Facebook account already connected to other account - deny
                    Mage::getSingleton('core/session')
                        ->addNotice(
                            $this->__('Your Facebook account is already connected to one of our store accounts.')
                        );
					//---ZEE CODE (To close the popup and realod the 'PARENT' page)
					exit("<script>window.close();".$redirectUrl.";</script>");
                    return $this;
                }

                // Connect from account dashboard - attach
                $customer = Mage::getSingleton('customer/session')->getCustomer();



				$helper->connectByFacebookId(
                    $customer,
                    $info->getId(),
                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                   $this->__('Your Facebook account is now connected to your store account. You can now login using '.
                        'our Facebook Login button or using store account credentials you will receive to your email address.')
                );
				//--- To close the popup and realod the 'PARENT' page, with Redirect URL
				exit("<script>window.close();".$redirectUrl.";</script>");
				return $this;
            }

            if($customersByFacebookId->getSize()) {
                // Existing connected user - login
                $customer = $customersByFacebookId->getFirstItem();

                $helper->loginByCustomer($customer);

                Mage::getSingleton('core/session')
                    ->addSuccess(
                        $this->__('You have successfully logged in using your Facebook account.')
                    );
				//---To close the popup and realod the 'PARENT' page, with Redirect URL
				exit("<script>window.close();".$redirectUrl.";</script>");
                return $this;
            }

            $customersByEmail = $helper->getCustomersByEmail($info->getEmail());

            if($customersByEmail->getSize()) {
                // Email account already exists - attach, login
                $customer = $customersByEmail->getFirstItem();

                $helper->connectByFacebookId(
                    $customer,
                    $info->getId(),
                    $token
                );

                Mage::getSingleton('core/session')->addSuccess(
                    $this->__('We have discovered you already have an account at our store. Your Facebook account is '.
                        'now connected to your store account.')
                );
				//---To close the popup and realod the 'PARENT' page, with Redirect URL
				exit("<script>window.close();".$redirectUrl.";</script>");
                return $this;
            }

            // New connection - create, attach, login
            $firstName = $info->getFirstName();
            if(empty($firstName)) {
                throw new Exception(
                    $this->__('Sorry, could not retrieve your Facebook first name. Please try again.')
                );
            }

            $lastName = $info->getLastName();
            if(empty($lastName)) {
                throw new Exception(
                    $this->__('Sorry, could not retrieve your Facebook last name. Please try again.')
                );
            }

            $birthday = $info->getBirthday();
            $birthday = Mage::app()->getLocale()->date($birthday, null, null, false)
                ->toString('yyyy-MM-dd');

            $gender = $info->getGender();
            if(empty($gender)) {
                $gender = null;
            } else if($gender == 'male') {
                $gender = 1;
            } else if($gender == 'female') {
                $gender = 2;
            }

            $helper->connectByCreatingAccount(
                $info->getEmail(),
                $info->getFirstName(),
                $info->getLastName(),
                $info->getId(),
                $birthday,
                $gender,
                $token
            );

            Mage::getSingleton('core/session')->addSuccess(
                $this->__('Your Facebook account is now connected to your new user account at our store.'.
                    ' Now you can login using our Facebook Login button.')
            );
			exit("<script>window.close();".$redirectUrl.";</script>");
        }
    }

}
