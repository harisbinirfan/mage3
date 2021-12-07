<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Onestepcheckout
 * @copyright   Copyright (c) 2017 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Class Magestore_Onestepcheckout_AjaxController
 */
require_once 'Magestore/Onestepcheckout/controllers/AjaxController.php';
class Tejar_Onestepcheckout_AjaxController extends Magestore_Onestepcheckout_AjaxController {
	
	
	/**
	* Returns captcha helper
	*
	* @return Mage_Captcha_Helper_Data
	*/
	protected function _getHelper()
	{
	  if (empty($this->_helper)) {
		  $this->_helper = Mage::helper('customer');
	  }

	  return $this->_helper;
	}
	
	
    /* Create new account on checkout page - Leo 08042015 */

    /**
     *
     */
    public function createAccAction() {
        $session = Mage::getSingleton('customer/session');
        $firstName = $this->getRequest()->getPost('onestepcheckout_firstname', false);
        $lastName = $this->getRequest()->getPost('onestepcheckout_lastname', false);
        $pass = $this->getRequest()->getPost('onestepcheckout_register_password', false);
        $passConfirm = $this->getRequest()->getPost('onestepcheckout_confirmation_password', false);
        $email = $this->getRequest()->getPost('onestepcheckout_register_username', false);

		$error = "";
		$helper = $this->_getHelper();
		if($helper->getZeroBounce("register")){
			$data = $helper->getValidateZeroBounce($email);
			// var_dump($data); die;
			if(isset($data['status']) &&  $data['zerobounce_result']){
				$emailData = array("Form"=> "Create Account","email" => $email, "error" => $data['sub_status']);
				$error = array('success' => false, 'error' => "There was a problem with the email address: " . $data['sub_status']);
			}
		}



        $customer = Mage::getModel('customer/customer')
                ->setFirstname($firstName)
                ->setLastname($lastName)
                ->setEmail($email)
                ->setPassword($pass)
                ->setConfirmation($passConfirm);

        try {
			
			if(empty($error)){
				$customer->save();
				Mage::dispatchEvent('customer_register_success', array('customer' => $customer));

				/*Changed By Adam (26102016): Add confirmation message and send confirm email*/
				if ($customer->isConfirmationRequired()) {
					$customer->sendNewAccountEmail(
						'confirmation', $session->getBeforeAuthUrl(), Mage::app()->getStore()->getId()
					);
					$result = array('confirm' => true, 'confirm_message' => $this->__('Account confirmation is required. Please, check your email for the confirmation link.'));
				} else {
					$session->setCustomerAsLoggedIn($customer);
					$result = array('success' => true);
					$session->setData('success_message', $this->__('Your account has been create successfully.'));
				}
			} else {
				$result = $error;
			}
        } catch (Exception $e) {
            $result = array('success' => false, 'error' => $e->getMessage());
        }
        if (isset($result['error']))
            $session->setData('register_result_error', $result['error']);
			$this->getResponse()->setBody(Zend_Json::encode($result));
    }

		/**
     *
     */
    public function forgotPasswordAction() {
        $email = $this->getRequest()->getPost('email', false);
        /**
		 * @var $flowPassword Mage_Customer_Model_Flowpassword
		 */
		$flowPassword = Mage::getModel('customer/flowpassword');
		$flowPassword->setEmail($email)->save();
        if (!Zend_Validate::is($email, 'EmailAddress')) {
            $result = array('success' => false);
		} else if (!$flowPassword->checkCustomerForgotPasswordFlowEmail($email)) {
			$result = array(
				'error' => $this->__('You have exceeded requests to times per 24 hours from 1 e-mail.'),
				'success' => false
			);
		} else if (!$flowPassword->checkCustomerForgotPasswordFlowIp()) {
			$result = array(
				'error' => $this->__('You have exceeded requests to times per hour from 1 IP.'),
				'success' => false
			);		
        } else {
            $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);
            if ($customer->getId()) {
                try {
					if($customer->getConfirmation()){
						$value = $this->_getHelper('customer')->getEmailConfirmationUrl($email);
						$message = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
						$result = array('success' => false, 'error' => $message);
					} else {
						$newPassword = $customer->generatePassword();
						$customer->changePassword($newPassword, false);
						$customer->sendPasswordReminderEmail();
						$result = array('success' => true);
					}
                } catch (Exception $e) {
                    $result = array('success' => false, 'error' => $e->getMessage());
                }
            } else {
                $result = array('success' => false, 'error' => 'notfound');
            }
        }
        $this->getResponse()->setBody(Zend_Json::encode($result));
    }
   
}
