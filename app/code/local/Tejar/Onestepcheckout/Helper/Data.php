<?php

/**
 * Class Magestore_Onestepcheckout_Helper_Data
 */
class Tejar_Onestepcheckout_Helper_Data extends Magestore_Onestepcheckout_Helper_Data
{
	/*
     * @return string
     */
    public function getCheckoutUrl()
    {
        return Mage::getUrl('checkout/buy');
    }
}