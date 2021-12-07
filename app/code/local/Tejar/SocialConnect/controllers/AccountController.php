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
require_once Mage::getModuleDir('controllers', 'Mage_Customer') . DS . 'AccountController.php';
class Tejar_SocialConnect_AccountController extends Mage_Customer_AccountController
{

  /**
       * Returns captcha helper
       *
       * @return Mage_Captcha_Helper_Data
       */
      // protected function _getHelper()
      // {
      //     if (empty($this->_helper)) {
      //         $this->_helper = Mage::helper('customer');
      //     }
      //
      //     return $this->_helper;
      // }

    public function preDispatch()
    {

        parent::preDispatch();

        if (!$this->getRequest()->isDispatched()) {
            return $this;
        }

        /*
         * Avoid situations where before_auth_url redirects when doing connect
         * and disconnect from account dashboard. Authenticate.
         */
        if (!Mage::getSingleton('customer/session')
                ->unsBeforeAuthUrl()
                ->unsAfterAuthUrl()
                ->authenticate($this)) {
			if(!strstr($this->getRequest()->getOriginalPathInfo(), 'account/loginAjax') &&
			!strstr($this->getRequest()->getOriginalPathInfo(), 'account/sendmail') &&
			!strstr($this->getRequest()->getOriginalPathInfo(), 'account/loginForgotPassPost') &&
			!strstr($this->getRequest()->getOriginalPathInfo(), 'account/loginAjaxRegister') &&
			!strstr($this->getRequest()->getOriginalPathInfo(), 'account/loginFlagPost') &&
			!strstr($this->getRequest()->getOriginalPathInfo(), 'account/loginSocialButtonUrl') &&
			!strstr($this->getRequest()->getOriginalPathInfo(), 'account/confirm/')){

				$this->setFlag('', 'no-dispatch', true);

			}
        }

    }

    public function googleAction()
    {
        $userInfo = Mage::getSingleton('tejar_socialconnect/google_info_user')
                ->load();

        Mage::register('tejar_socialconnect_google_userinfo', $userInfo);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function facebookAction()
    {
        $userInfo = Mage::getSingleton('tejar_socialconnect/facebook_info_user')
            ->load();

        Mage::register('tejar_socialconnect_facebook_userinfo', $userInfo);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function twitterAction()
    {
        // Cache user info inside customer session due to Twitter window frame rate limits
        if(!($userInfo = Mage::getSingleton('customer/session')
                ->getTejarSocialconnectTwitterUserinfo()) || !$userInfo->hasData()) {

            $userInfo = Mage::getSingleton('tejar_socialconnect/twitter_info_user')
                ->load();

            Mage::getSingleton('customer/session')
                ->setTejarSocialconnectTwitterUserinfo($userInfo);
        }

        Mage::register('tejar_socialconnect_twitter_userinfo', $userInfo);

        $this->loadLayout();
        $this->renderLayout();
    }

    public function _linkedinAction()
    {
        $userInfo = Mage::getSingleton('tejar_socialconnect/linkedin_info_user')
            ->load();

        Mage::register('tejar_socialconnect_linkedin_userinfo', $userInfo);

        $this->loadLayout();
        $this->renderLayout();
    }

	public function _getSession(){
		return Mage::getSingleton('customer/session');
	}

	/**
     * Ajax Login post action
     */
    public function loginAjaxRegisterAction()
    {
		//echo "zee";die;
		$message=null;
		$messageType=null;
		$errUrl = $this->_getUrl('*/*/create', array('_secure' => true));

        if (!$this->_validateFormKey()) {
            $this->_redirectError($errUrl);
            return;
        }

        /** @var $session Mage_Customer_Model_Session */
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }

        if (!$this->getRequest()->isPost()) {
            $this->_redirectError($errUrl);
            return;
        }

        $customer = $this->_getCustomer();
//var_dump($this->getRequest()->getParams());die;
        try {
            $errors = $this->_getCustomerErrors($customer);

            $coreModel = Mage::getModel('tejar_core/observer');
			$postData = $this->getRequest()->getPost();
			$include = array("firstname" => "First Name","lastname" => "Last Name","email" => "Email");
			if(!empty($postData)){
				foreach($include as $key => $name){
					if(array_key_exists($key,$postData)){
						if($match = $coreModel->checkDisallowChar($postData[$key])){
							$error = true;
							$errors = 'Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $name;
						}
					}
				}
			}

            if(!empty($errors)){
            $helper = $this->_getHelper('customer');
              if($helper->getZeroBounce("register")){
                $email = $this->getRequest()->getPost('email');
                $data = $helper->getValidateZeroBounce($email);
                if(isset($data['status']) &&  $data['zerobounce_result']){
                  $emailData = array("Form"=> "Create Account","email" => $email, "error" => $data['sub_status']);
                  $messageType = "error";
                  $errors = $this->__("There was a problem with the email address: " . $data['sub_status']);
                }
              }
            } 

            if (empty($errors)) {

				$customer->cleanPasswordsValidationData();
                $customer->save();

                $this->_dispatchRegisterSuccess($customer);

                $message = $this->_successAjaxProcessRegistration($customer);
				$messageType = "success";
                //return;
            } else {
                $messageType = "error";
				$message = $errors;
            }
        } catch (Mage_Core_Exception $e) {
            $session->setCustomerFormData($this->getRequest()->getPost());
            if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                $url = $this->_getUrl('customer/account/forgotpassword');
                $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
				$messageType = "error";
            } else {
                $message = $this->_escapeHtml($e->getMessage());
            }
            //$session->addError($message);
        } catch (Exception $e) {
            $session->setCustomerFormData($this->getRequest()->getPost());
            //$session->addException($e, $this->__('Cannot save the customer.'));
			$message = $this->__('Cannot save the customer.');
			$messageType = "error";
        }
		//echo $message;
		if(!is_null($message)){
				$response = array(
                    'response' => $message,
					'responseType' => $messageType
                );
               // $this->getResponse()->setHeader('Content-Type', 'application/json', true);
                echo json_encode($response);exit;
		}
       // $this->_redirectError($errUrl);
	}

	/**
     * Success Registration
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Mage_Customer_AccountController
     */
    protected function _successAjaxProcessRegistration(Mage_Customer_Model_Customer $customer)
    {
		$message = null;
        $session = $this->_getSession();

        if ($customer->isConfirmationRequired()) {

            /** @var $app Mage_Core_Model_App */
            $app = $this->_getApp();
            /** @var $store  Mage_Core_Model_Store*/
            $store = $app->getStore();
            $customer->sendNewAccountEmail(
                'confirmation',
                $session->getBeforeAuthUrl(),
                $store->getId()
            );

            $customerHelper = $this->_getHelper('customer');

			//-- $session->addSuccess($this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.',$customerHelper->getEmailConfirmationUrl($customer->getEmail())));

			$message = $this->__('Account confirmation is required. Please, check your email for the confirmation link. To resend the confirmation email please <a href="%s">click here</a>.',
                $customerHelper->getEmailConfirmationUrl($customer->getEmail()));
            $url = $this->_getUrl('*/*/index', array('_secure' => true));
        } else {
            $session->setCustomerAsLoggedIn($customer);
            $url = $this->_welcomeCustomer($customer);
			$message = "WELCOME Customer!";
        }
       // $this->_redirectSuccess($url);
        return $message;
    }


	/**
     * Ajax Login post action
     */
    public function loginAjaxAction()
    {

		$message = null;
		$messageType = null;
        if (!$this->_validateFormKey()) {
            $this->_redirect('*/*/');
            return;
        }

        if ($this->_getSession()->isLoggedIn()) {
            $this->_redirect('*/*/');
            return;
        }
        $session = $this->_getSession();

        if ($this->getRequest()->isPost()) {

            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {

                try {

                    $session->login($login['username'], $login['password']);
                    if ($session->getCustomer()->getIsJustConfirmed()) {
                        $this->_welcomeCustomer($session->getCustomer(), true);
						$messageType = "success";
                    }

                } catch (Mage_Core_Exception $e) {

                    switch ($e->getCode()) {
                        case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
                            $value =  Mage::helper('customer/data')->getEmailConfirmationUrl($login['username']);
                            $message =  Mage::helper('customer/data')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
							$messageType = "error";
                            break;
                        case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
                            $message = $e->getMessage();
							$messageType = "error";
                            break;
                        default:
                            $message = $e->getMessage();
                    }
                   // $session->addError($message);
                    $session->setUsername($login['username']);
                } catch (Exception $e) {
                    // Mage::logException($e); // PA DSS violation: this exception log can disclose customer password
                }
            } else {
				$message = $this->__('Login and password are required.');
				$messageType = "error";
                $session->addError($this->__('Login and password are required.'));
            }
        }

		//echo "--> ",$message;
		// if(!is_null($messageType)){

				$response = array(
                    'response' => $message,
					'responseType' => $messageType,
					'redirectUrl' => Mage::getStoreConfigFlag(Mage_Customer_Helper_Data::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD)
                );
               // $this->getResponse()->clearHeaders()->setHeader('Content-type', 'application/json', true);
                //$this->getResponse()->setBody(json_encode($response));
				echo json_encode($response);exit;
		// }
		// echo var_dump( $this->getResponse()->setHeader('Content-Type', 'application/json', true));die;
       // $this->_loginPostRedirect();
    }
	/*
	* sendmailAction - to send product link to a friend using Ajax Call
	*
	*/
	public function ___sendmailAction()
    {
		$message     = null;
		$messageType = null;
		//echo $this->getRequest()->getParam('form_key');die;
        if (!$this->_validateFormKey()) {

            //return $this->_redirect('*/*/send', array('_current' => true));
            return false;
        }

        $product    = $this->_initProduct();
        $model      = $this->_initSendToFriendModel();
        $data       = $this->getRequest()->getPost();

        if (!$product || !$data) {
            $this->_forward('noRoute');
            return;
        }

        $categoryId = $this->getRequest()->getParam('cat_id', null);
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')
                ->load($categoryId);
            $product->setCategory($category);
            Mage::register('current_category', $category);
        }

        $model->setSender($this->getRequest()->getPost('sender'));
        $model->setRecipients($this->getRequest()->getPost('recipients'));
        $model->setProduct($product);

        try {
            $validate = $model->validate();
            if ($validate === true) {
                $model->send();
				$message = $this->__('The link to a friend was sent.');
				$messageType = "success";
               // Mage::getSingleton('catalog/session')->addSuccess($this->__('The link to a friend was sent.'));
                //$this->_redirectSuccess($product->getProductUrl());
               // return;
            }
            else {
                if (is_array($validate)) {
                    foreach ($validate as $errorMessage) {
                        Mage::getSingleton('catalog/session')->addError($errorMessage);
                    }
                }
                else {
					$message = $this->__('There were some problems with the data.');
					$messageType = "error";
                   // Mage::getSingleton('catalog/session')->addError($this->__('There were some problems with the data.'));
                }
            }
        }
        catch (Mage_Core_Exception $e) {
           // Mage::getSingleton('catalog/session')->addError($e->getMessage());
			$message = $e->getMessage();
			$messageType = "error";
        }
        catch (Exception $e) {
			$message = $this->__('Some emails were not sent.');
			$messageType = "error";
            //Mage::getSingleton('catalog/session')->addException($e, $this->__('Some emails were not sent.'));
        }
		//echo $message;die;
		$response = array(
                    'response' => $message,
					'responseType' => $messageType
                );
		echo json_encode($response);exit;
        // save form data
        //Mage::getSingleton('catalog/session')->setSendfriendFormData($data);

        //$this->_redirectError(Mage::getURL('*/*/send', array('_current' => true)));
    }

	 /**
     * Initialize Product Instance
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _initProduct()
    {
        $productId  = (int)$this->getRequest()->getParam('id');

        if (!$productId) {
            return false;
        }
        $product = Mage::getModel('catalog/product')
            ->load($productId);
        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            return false;
        }

        Mage::register('product', $product);
        return $product;
    }

    /**
     * Initialize send friend model
     *
     * @return Mage_Sendfriend_Model_Sendfriend
     */
    protected function _initSendToFriendModel()
    {
        $model  = Mage::getModel('sendfriend/sendfriend');
        $model->setRemoteAddr(Mage::helper('core/http')->getRemoteAddr(true));
        $model->setCookie(Mage::app()->getCookie());
        $model->setWebsiteId(Mage::app()->getStore()->getWebsiteId());

        Mage::register('send_to_friend_model', $model);

        return $model;
    }

	/**
     * Ajax Login post action
     */
    public function __loginForgotPassPostAction()
    {
		$message = null;
		$messageType = null;
		$email = (string) $this->getRequest()->getPost('email-forgot');

        if ($email) {
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                $this->_getSession()->addError($this->__('Invalid email address.'));
				$message = $this->__('Invalid email address.');
                //$this->_redirect('*/*/forgotpassword');
               // return;
            }

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($customer->getId()) {
                try {
                    $newResetPasswordLinkToken = Mage::helper('customer')->generateResetPasswordLinkToken();
                    $customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
                    $customer->sendPasswordResetConfirmationEmail();
					$this->_getSession()
                ->addSuccess(Mage::helper('customer')->__('We have sent you a reset link in your email address %s .', Mage::helper('customer')->htmlEscape($email)));
				$message = $this->__('We have sent you a reset link in your email address %s .', Mage::helper('customer')->htmlEscape($email));
				$messageType = "success";
                } catch (Exception $exception) {
                    $this->_getSession()->addError($exception->getMessage());
                    $this->_redirect('*/*/forgotpassword');
                    return;
                }
            }
            else //custom code start
            {
              // $this->_getSession()->addError(Mage::helper('customer')->__('Your email address %s does not exist.',Mage::helper('customer')->htmlEscape($email)));
				$message = $this->__('Your email address %s does not exist.', Mage::helper('customer')->htmlEscape($email));
				$messageType = "error";
				//$this->_redirect('*/*/forgotpassword');
            //return;
            }//custom code end

           // $this->_redirect('*/*/');
            //return;
        } else {
            // $this->_getSession()->addError($this->__('Please enter your email.'));
            $message = $this->__('Please enter your email.');
			$messageType = "error";
			//$this->_redirect('*/*/forgotpassword');
            //return;
        }

		//if(!is_null($message)){
				$response = array(
                    'response' => $message,
                    'responseType' => $messageType
                );
				echo json_encode($response);exit;
                //$this->getResponse()->setHeader('Content-Type', 'application/json', true);
                //$this->getResponse()->setBody(json_encode($response));
		//}
	}

    	/**
     * Ajax Login post action
     */
    public function loginForgotPassPostAction()
    {
		$email = $this->getRequest()->getPost('email-forgot');
		$message = null;
		$messageType = null;
        if (!Zend_Validate::is($email, 'EmailAddress')) {
			$message = $this->__('Invalid email address.');
			$messageType = "error";
        } else {
            /**
             * @var $flowPassword Mage_Customer_Model_Flowpassword
             */
            $flowPassword = $this->_getModel('customer/flowpassword');
            $flowPassword->setEmail($email)->save();
            if (!$flowPassword->checkCustomerForgotPasswordFlowEmail($email)) {
				$response = array(
					'response' => $this->__('You have exceeded requests to times per 24 hours from 1 e-mail.'),
					'responseType' => "error"
				);
				echo Zend_Json::encode($response);
				exit;
            }
            if (!$flowPassword->checkCustomerForgotPasswordFlowIp()) {
				$response = array(
					'response' => $this->__('You have exceeded requests to times per hour from 1 IP.'),
					'responseType' => "error"
				);
				echo Zend_Json::encode($response);
				exit;
            }

            $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);
            if ($customer->getId()) {
                try {
					if($customer->getConfirmation()){
						$value = $this->_getHelper('customer')->getEmailConfirmationUrl($email);
						$message = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
						$messageType = "error";
					} else {
						$newPassword = $customer->generatePassword();
						$customer->changePassword($newPassword, false);
						$customer->sendPasswordReminderEmail();
						$message = $this->__('We have sent you a reset link in your email address %s.', Mage::helper('customer')->htmlEscape($email));
						$messageType = "success";
					}
                } catch (Exception $e) {
					$message = $e->getMessage();
					$messageType = "error";
                }
            } else {
                $message = $this->__('Your email address %s does not exist.', Mage::helper('customer')->htmlEscape($email));
				$messageType = "error";
            }
        }
		$response = array(
			'response' => $message,
			'responseType' => $messageType
		);
        echo Zend_Json::encode($response);
		exit;
	}

	/**
     * Ajax setRedirectFlag post action
     */
    public function loginFlagPostAction()
	{
		//echo $this->getRequest()->getPost('redirectFlag');
		return Mage::getSingleton('core/session')->setSocialRedirectFlag($this->getRequest()->getPost('redirectFlag'));
	}
	/**
     * Ajax setRedirectFlag post action
     */
    public function loginSocialButtonUrlAction()
	{
		$facebookButtonHtml = Mage::app()->getLayout()->createBlock('tejar_socialconnect/facebook_button')->tohtml();
		$googleButtonHtml = Mage::app()->getLayout()->createBlock('tejar_socialconnect/google_button')->tohtml();
		$response = array(
                    'response_google'   => $googleButtonHtml,
                    'response_facebook' => $facebookButtonHtml,

                );
		//$this->getResponse()->clearHeaders()->setHeader('Content-type','application/json',true);
       //$this->getResponse()->setBody(json_encode($response));
		echo json_encode($response);exit;
		//$this->getResonse()->sendResponse();
	}
}
