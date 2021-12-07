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
 * @package     Mage_Captcha
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Captcha Observer
 *
 * @category    Mage
 * @package     Mage_Captcha
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Captcha_Model_Observer extends Mage_Captcha_Model_Observer
{
	
	
			/**
     * Returns captcha helper
     *
     * @return Mage_Captcha_Helper_Data
     */
    protected function _getHelper()
    {
        if (empty($this->_helper)) {
            $this->_helper = Mage::helper('tejar_captcha');
        }

        return $this->_helper;
    }



    /**
     * Check Captcha On Forgot Password Page
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Captcha_Model_Observer
     */
    public function checkForgotpassword($observer)
    {
		if($this->_getHelper()->getConfigNode('recaptcha_enable')){	
			$formId = 'user_forgotpassword';
			$captchaModel = Mage::helper('captcha')->getCaptcha($formId);
			if ($captchaModel->isRequired()) {
				$controller = $observer->getControllerAction();
				if (!$captchaModel->isCorrect($this->_getCaptchaString($controller->getRequest(), $formId))) {
					Mage::getSingleton('customer/session')->addError(Mage::helper('captcha')->__('You are not a human.'));
					$controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
					$controller->getResponse()->setRedirect(Mage::getUrl('*/*/forgotpassword'));
				}
			}
			return $this;
		}
		
		return parent::checkForgotpassword($observer);
    }

    /**
     * Check Captcha On User Login Page
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Captcha_Model_Observer
     */
    public function checkUserLogin($observer)
    {
		if($this->_getHelper()->getConfigNode('recaptcha_enable')){	
			$formId = 'user_login';
			$captchaModel = Mage::helper('captcha')->getCaptcha($formId);
			$controller = $observer->getControllerAction();
			$loginParams = $controller->getRequest()->getPost('login');
			$login = isset($loginParams['username']) ? $loginParams['username'] : null;
			if ($captchaModel->isRequired($login)) {
				$word = $this->_getCaptchaString($controller->getRequest(), $formId);
				if (!$captchaModel->isCorrect($word)) {
					Mage::getSingleton('customer/session')->addError(Mage::helper('captcha')->__('You are not a human.'));
					$controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
					Mage::getSingleton('customer/session')->setUsername($login);
					$beforeUrl = Mage::getSingleton('customer/session')->getBeforeAuthUrl();
					$url =  $beforeUrl ? $beforeUrl : Mage::helper('customer')->getLoginUrl();
					$controller->getResponse()->setRedirect($url);
				}
			}
			$captchaModel->logAttempt($login);
			return $this;
		}
		
		return parent::checkUserLogin($observer);
    }

    /**
     * Check Captcha On Register User Page
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Captcha_Model_Observer
     */
    public function checkUserCreate($observer)
    {
		
		if($this->_getHelper()->getConfigNode('recaptcha_enable')){	
			$formId = 'user_create';
			$captchaModel = Mage::helper('captcha')->getCaptcha($formId);
			if ($captchaModel->isRequired()) {
				$controller = $observer->getControllerAction();
				if (!$captchaModel->isCorrect($this->_getCaptchaString($controller->getRequest(), $formId))) {
					Mage::getSingleton('customer/session')->addError(Mage::helper('captcha')->__('You are not a human.'));
					$controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
					Mage::getSingleton('customer/session')->setCustomerFormData($controller->getRequest()->getPost());
					$controller->getResponse()->setRedirect(Mage::getUrl('*/*/create'));
				}
			}
			return $this;
			
		}
        return parent::checkUserCreate($observer);
    }

    /**
     * Check Captcha On Checkout as Guest Page
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Captcha_Model_Observer
     */
    public function checkGuestCheckout($observer)
    {
		
		if($this->_getHelper()->getConfigNode('recaptcha_enable')){	
			$formId = 'guest_checkout';
			$captchaModel = Mage::helper('captcha')->getCaptcha($formId);
			$checkoutMethod = Mage::getSingleton('checkout/type_onepage')->getQuote()->getCheckoutMethod();
			if ($checkoutMethod == Mage_Checkout_Model_Type_Onepage::METHOD_GUEST) {
				if ($captchaModel->isRequired()) {
					$controller = $observer->getControllerAction();
					if (!$captchaModel->isCorrect($this->_getCaptchaString($controller->getRequest(), $formId))) {
						$controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
						$result = array('error' => 1, 'message' => Mage::helper('captcha')->__('You are not a human.'));
						$controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
					}
				}
			}
			return $this;
		}
		
		return parent::checkGuestCheckout($observer);
    }

    /**
     * Check Captcha On Checkout Register Page
     *
     * @param Varien_Event_Observer $observer
     * @return Mage_Captcha_Model_Observer
     */
    public function checkRegisterCheckout($observer)
    {
		
		if($this->_getHelper()->getConfigNode('recaptcha_enable')){	
			$formId = 'register_during_checkout';
			$captchaModel = Mage::helper('captcha')->getCaptcha($formId);
			$checkoutMethod = Mage::getSingleton('checkout/type_onepage')->getQuote()->getCheckoutMethod();
			if ($checkoutMethod == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER) {
				if ($captchaModel->isRequired()) {
					$controller = $observer->getControllerAction();
					if (!$captchaModel->isCorrect($this->_getCaptchaString($controller->getRequest(), $formId))) {
						$controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
						$result = array('error' => 1, 'message' => Mage::helper('captcha')->__('You are not a human.'));
						$controller->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
					}
				}
			}
			return $this;
		}
		
		return parent::checkRegisterCheckout($observer);
    }

   
}
