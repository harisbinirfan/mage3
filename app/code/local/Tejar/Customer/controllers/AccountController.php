<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Customer
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Customer account controller
 *
 * @category   Mage
 * @package    Mage_Customer
 * @author      Magento Core Team <core@magentocommerce.com>
 */
 require_once 'Mage/Customer/controllers/AccountController.php';
class Tejar_Customer_AccountController extends Mage_Customer_AccountController
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


  	/**
       * Create customer account action
       */
      public function createPostAction()
      {
  		// var_dump($this->getRequest()->getPost('email')); die;

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

          try {
              $errors = $this->_getCustomerErrors($customer);


  			$helper = $this->_getHelper('customer');
  			if($helper->getZeroBounce("register")){
  				$email = $this->getRequest()->getPost('email');
  				$data = $helper->getValidateZeroBounce($email);
          if(isset($data['status']) && $data['zerobounce_result']){
  					$emailData = array("Form"=> "Create Account","email" => $email, "error" => $data['sub_status']);
  					$session->addError("There was a problem with the email address: " . $data['sub_status']);
  					$this->_redirectError($errUrl);
  					return;
  				}
  			}

              if (empty($errors)) {
                  $customer->cleanPasswordsValidationData();
                  $customer->save();
                  $this->_dispatchRegisterSuccess($customer);
                  $this->_successProcessRegistration($customer);
                  return;
              } else {
                  $this->_addSessionError($errors);
              }
          } catch (Mage_Core_Exception $e) {
              $session->setCustomerFormData($this->getRequest()->getPost());
              if ($e->getCode() === Mage_Customer_Model_Customer::EXCEPTION_EMAIL_EXISTS) {
                  $url = $this->_getUrl('customer/account/forgotpassword');
                  $message = $this->__('There is already an account with this email address. If you are sure that it is your email address, <a href="%s">click here</a> to get your password and access your account.', $url);
              } else {
                  $message = $this->_escapeHtml($e->getMessage());
              }
              $session->addError($message);
          } catch (Exception $e) {
              $session->setCustomerFormData($this->getRequest()->getPost());
              $session->addException($e, $this->__('Cannot save the customer.'));
          }

          $this->_redirectError($errUrl);
      }

            /**
     * Forgot customer password action
     */
    public function forgotPasswordPostAction()
    {
        $email = (string) $this->getRequest()->getPost('email');
        if ($email) {
            /**
             * @var $flowPassword Mage_Customer_Model_Flowpassword
             */
            $flowPassword = $this->_getModel('customer/flowpassword');
            $flowPassword->setEmail($email)->save();
            if (!$flowPassword->checkCustomerForgotPasswordFlowEmail($email)) {
                $this->_getSession()
                    ->addError($this->__('You have exceeded requests to times per 24 hours from 1 e-mail.'));
                $this->_redirect('*/*/forgotpassword');
                return;
            }
            if (!$flowPassword->checkCustomerForgotPasswordFlowIp()) {
                $this->_getSession()->addError($this->__('You have exceeded requests to times per hour from 1 IP.'));
                $this->_redirect('*/*/forgotpassword');
                return;
            }
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                $this->_getSession()->setForgottenEmail($email);
                $this->_getSession()->addError($this->__('Invalid email address.'));
                $this->_redirect('*/*/forgotpassword');
                return;
            }
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);
            if ($customer->getId()) {
                try {
					if($customer->getConfirmation()){
						$value = $this->_getHelper('customer')->getEmailConfirmationUrl($email);
						$message = $this->_getHelper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
						$this->_getSession()->addError($message);   
						$this->_redirect('*/*/forgotpassword');
						return;						
					}
					$newResetPasswordLinkToken =  $this->_getHelper('customer')->generateResetPasswordLinkToken();
					$customer->changeResetPasswordLinkToken($newResetPasswordLinkToken);
					$customer->sendPasswordResetConfirmationEmail();
                } catch (Exception $exception) {
                    $this->_redirect('*/*/forgotpassword');
                    return;
                }
            }
            $this->_getSession()
                ->addSuccess( $this->_getHelper('customer')
                ->__('If there is an account associated with %s you will receive an email with a link to reset your password.',
                    $this->_getHelper('customer')->escapeHtml($email)));
            $this->_redirect('*/*/');
            return;
        } else {
            $this->_getSession()->addError($this->__('Please enter your email.'));
            $this->_redirect('*/*/forgotpassword');
            return;
        }
    }
}
