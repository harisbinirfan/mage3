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
 * Customer model
 *
 * @category    Mage
 * @package     Mage_Customer
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Customer_Model_Customer extends Mage_Customer_Model_Customer
{
    

    /**
     * Validate customer attribute values.
     * For existing customer password + confirmation will be validated only when password is set (i.e. its change is requested)
     *
     * @return bool
     */
    public function validate()
    {
		
		if (Mage::helper('onestepcheckout')->enabledOnestepcheckout() && Mage::app()->getRequest()->getModuleName() == "onestepcheckout") {
            return true;
        }
		
		$store = Mage::app()->getStore()->getCode();
		if(!Mage::getStoreConfig('customer/create_account/max_first_last_name', $store)){
			 return parent::validate();
		}
		
		$maxChar = Mage::getStoreConfig('customer/create_account/max_characters', $store);
		
		if(!$maxChar){
			$maxChar = 25;
		}
		
        $errors = array();
        if (!Zend_Validate::is( trim($this->getFirstname()) , 'NotEmpty')) {
            $errors[] = Mage::helper('customer')->__('The first name cannot be empty.');
        }
		

		if ( strlen(trim($this->getFirstname())) && !Zend_Validate::is(trim($this->getFirstname()) , 'StringLength' , array('max' => $maxChar))) {
             $errors[] = Mage::helper('customer')
                ->__('Please enter a first name with at most %s characters.', $maxChar);
        }
		
		if ( strlen(trim($this->getFirstname())) && Zend_Validate::is(trim($this->getFirstname()) , 'Regex' , array('/^http(s)?:\/\/|(?:http(s)?:\/\/)/'))) {
			 $errors[] = Mage::helper('customer')->__('"HTTP" or "HTTPS" is not allowed on first name.');
		}
		

        if (!Zend_Validate::is( trim($this->getLastname()) , 'NotEmpty')) {
            $errors[] = Mage::helper('customer')->__('The last name cannot be empty.');
        }
		
		if ( strlen(trim($this->getLastname())) && !Zend_Validate::is(trim($this->getLastname()) , 'StringLength' , array('max' => $maxChar))) {
             $errors[] = Mage::helper('customer')
                ->__('Please enter a last name with at most %s characters.', $maxChar);
        }
		
		if ( strlen(trim($this->getLastname())) && Zend_Validate::is(trim($this->getLastname()) , 'Regex' , array('/^http(s)?:\/\/|(?:http(s)?:\/\/)/'))) {
			 $errors[] = Mage::helper('customer')->__('"HTTP" or "HTTPS" is not allowed on last name.');
		}

        if (!Zend_Validate::is($this->getEmail(), 'EmailAddress')) {
            $errors[] = Mage::helper('customer')->__('Invalid email address "%s".', $this->getEmail());
        }

        $password = $this->getPassword();
        if (!$this->getId() && !Zend_Validate::is($password , 'NotEmpty')) {
            $errors[] = Mage::helper('customer')->__('The password cannot be empty.');
        }
        if (strlen($password) && !Zend_Validate::is($password, 'StringLength', array(self::MINIMUM_PASSWORD_LENGTH))) {
            $errors[] = Mage::helper('customer')
                ->__('The minimum password length is %s', self::MINIMUM_PASSWORD_LENGTH);
        }
        if (strlen($password) && !Zend_Validate::is($password, 'StringLength', array('max' => self::MAXIMUM_PASSWORD_LENGTH))) {
            $errors[] = Mage::helper('customer')
                ->__('Please enter a password with at most %s characters.', self::MAXIMUM_PASSWORD_LENGTH);
        }
        $confirmation = $this->getPasswordConfirmation();
        if ($password != $confirmation) {
            $errors[] = Mage::helper('customer')->__('Please make sure your passwords match.');
        }

        $entityType = Mage::getSingleton('eav/config')->getEntityType('customer');
        $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'dob');
        if ($attribute->getIsRequired() && '' == trim($this->getDob())) {
            $errors[] = Mage::helper('customer')->__('The Date of Birth is required.');
        }
        $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'taxvat');
        if ($attribute->getIsRequired() && '' == trim($this->getTaxvat())) {
            $errors[] = Mage::helper('customer')->__('The TAX/VAT number is required.');
        }
        $attribute = Mage::getModel('customer/attribute')->loadByCode($entityType, 'gender');
        if ($attribute->getIsRequired() && '' == trim($this->getGender())) {
            $errors[] = Mage::helper('customer')->__('Gender is required.');
        }

        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    	/**
     * Send email with new account related information
     *
     * @param string $type
     * @param string $backUrl
     * @param string $storeId
     * @param string $password
     * @throws Mage_Core_Exception
     * @return Mage_Customer_Model_Customer
     */
    public function sendNewAccountEmail($type = 'registered', $backUrl = '', $storeId = '0', $password = null)
    {
        $types = array(
            'registered'   => self::XML_PATH_REGISTER_EMAIL_TEMPLATE, // welcome email, when confirmation is disabled
            'confirmed'    => self::XML_PATH_CONFIRMED_EMAIL_TEMPLATE, // welcome email, when confirmation is enabled
            'confirmation' => self::XML_PATH_CONFIRM_EMAIL_TEMPLATE, // email with confirmation link
			'custom_confirmation' => self::XML_PATH_CONFIRM_EMAIL_TEMPLATE, // email with confirmation link
        );
        if (!isset($types[$type])) {
            Mage::throwException(Mage::helper('customer')->__('Wrong transactional account email type'));
        }
        if (!$storeId) {
            $storeId = $this->_getWebsiteStoreId($this->getSendemailStoreId());
        }
        if (!is_null($password)) {
            $this->setPassword($password);
        }
		$array = array('customer' => $this, 'back_url' => $backUrl,'display_password' => '[The password you specified]');
		if($type == 'custom_confirmation'){
			$array['display_password'] = $this->getPassword();
		}
        $this->_sendEmailTemplate($types[$type], self::XML_PATH_REGISTER_EMAIL_IDENTITY,
            $array, $storeId);
        $this->cleanPasswordsValidationData();
        return $this;
    }

    
}
