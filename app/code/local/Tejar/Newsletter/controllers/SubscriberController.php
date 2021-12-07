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
 * @package     Mage_Newsletter
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Newsletter subscribe controller
 *
 * @category    Mage
 * @package     Mage_Newsletter
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Newsletter/controllers/SubscriberController.php';
class Tejar_Newsletter_SubscriberController extends Mage_Newsletter_SubscriberController
{
	public function indexAction()
    {
        $this->loadLayout();
         $this->_initLayoutMessages('customer/session');
         $this->_initLayoutMessages('catalog/session');

         if ($block = $this->getLayout()->getBlock('customer_unsubscribe')) {
             $block->setRefererUrl($this->_getRefererUrl());
         }
        $this->getLayout()->getBlock('head')->setTitle($this->__('Newsletter Unsubscribe'));
        $this->renderLayout();
    }

    /**
     * Unsubscribe newsletter
     */
    public function unsubscribeAction()
    {

        $id    = (int) $this->getRequest()->getParam('id');
        $code  = (string) $this->getRequest()->getParam('code');
	//echo "shariq"; die;
        if ($id && $code) {
            $session = Mage::getSingleton('core/session');
            try {
                Mage::getModel('newsletter/subscriber')->load($id)
                    ->setCheckCode($code)
                    ->unsubscribe();
               // $session->addSuccess($this->__('You have been unsubscribed.'));
            }
            catch (Mage_Core_Exception $e) {
                $session->addException($e, $e->getMessage());
            }
            catch (Exception $e) {
                $session->addException($e, $this->__('There was a problem with the un-subscription.'));
            }
        }
        //$this->_redirectReferer();
		$this->_redirect('newsletter/subscriber/');


    }

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


			 /**
		      * New subscription action
		      */
		    public function newAction()
		    {

		        if ($this->getRequest()->isPost() && $this->getRequest()->getPost('email')) {
		            $session            = Mage::getSingleton('core/session');
		            $customerSession    = Mage::getSingleton('customer/session');
		            $email              = (string) $this->getRequest()->getPost('email');


		            try {
										$captchaHelper = Mage::helper('tejar_captcha');
										if($captchaHelper->getConfigNode('recaptcha_enable')){
											$formId = 'newsletter_captcha';
											$captchaModel = Mage::helper('captcha')->getCaptcha($formId);
											if ($captchaModel->isRequired()) {
												if (!$captchaModel->isCorrect($this->_getCaptchaString($this->getRequest(), $formId))) {
													$this->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
													 Mage::throwException(Mage::helper('captcha')->__('You are not a human.'));
												}
											}
										}
		                if (!Zend_Validate::is($email, 'EmailAddress')) {
		                    Mage::throwException($this->__('Please enter a valid email address.'));
		                }

						$helper = $this->_getHelper();
						if($helper->getZeroBounce("newsletter")){
							$data = $helper->getValidateZeroBounce($email);
							if(isset($data['status']) && $data['zerobounce_result']){
								$emailData = array("Form" => "Newsletter","email" => $email, "error" => $data['sub_status']);
								 Mage::throwException($this->__($data['sub_status']));
							}
						}


		                if (Mage::getStoreConfig(Mage_Newsletter_Model_Subscriber::XML_PATH_ALLOW_GUEST_SUBSCRIBE_FLAG) != 1 &&
		                    !$customerSession->isLoggedIn()) {
		                    Mage::throwException($this->__('Sorry, but administrator denied subscription for guests. Please <a href="%s">register</a>.', Mage::helper('customer')->getRegisterUrl()));
		                }

		                $ownerId = Mage::getModel('customer/customer')
		                        ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
		                        ->loadByEmail($email)
		                        ->getId();
		                if ($ownerId !== null && $ownerId != $customerSession->getId()) {
		                    Mage::throwException($this->__('This email address is already assigned to another user.'));
		                }

										// $serverAddress = array("Server" => $_SERVER,"Email" => $this->getRequest()->getPost('email'));
										// Mage::log($serverAddress, null, 'newsletter.log');


		                $status = Mage::getModel('newsletter/subscriber')->subscribe($email);
		                if ($status == Mage_Newsletter_Model_Subscriber::STATUS_NOT_ACTIVE) {
		                    $session->addSuccess($this->__('Confirmation request has been sent.'));
		                }
		                else {
		                    $session->addSuccess($this->__('Thank you for your subscription.'));
		                }


		            }
		            catch (Mage_Core_Exception $e) {
		                $session->addException($e, $this->__('There was a problem with the subscription: %s', $e->getMessage()));
		            }
		            catch (Exception $e) {
		                $session->addException($e, $this->__('There was a problem with the subscription.'));
		            }
		        }
		        $this->_redirectReferer();
		    }

				/**
							 * Get Captcha String
							 *
							 * @param Varien_Object $request
							 * @param string $formId
							 * @return string
							 */
							protected function _getCaptchaString($request, $formId)
							{
								$captchaParams = $request->getPost(Mage_Captcha_Helper_Data::INPUT_NAME_FIELD_VALUE);
								return $captchaParams[$formId];
							}

}
