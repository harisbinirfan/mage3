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
 * Class Magestore_Onestepcheckout_Block_Onestepcheckout
 */
class Tejar_Onestepcheckout_Block_Onestepcheckout extends Magestore_Onestepcheckout_Block_Onestepcheckout {

    /**  
     * @return Mage_Sales_Model_Quote_Address
     */
    public function getBillingAddress() {
        if ($this->isCustomerLoggedIn()) {
            return $this->getQuote()->getBillingAddress();
        } else {
            return Mage::getModel('sales/quote_address');
        }
    }

    /**
     * @return false|Mage_Core_Model_Abstract|Mage_Sales_Model_Quote_Address
     */
    public function getShippingAddress() {
        if ($this->isCustomerLoggedIn()) {
            return $this->getQuote()->getShippingAddress();
        } else {
            return Mage::getModel('sales/quote_address');
        }
    }

}
