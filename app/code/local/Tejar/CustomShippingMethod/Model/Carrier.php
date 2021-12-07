<?php
/**
 * Tejar_CustomShippingMethod_Model_Carrier
 *
 * @category   Tejar
 * @package    Tejar_CustomShippingMethod
 * @class      Tejar_CustomShippingMethod_Model_Carrier
 * @author     Zeeshan <zeeshan.zeeshan123@gmail.com>
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Tejar_CustomShippingMethod_Model_Carrier extends Mage_Shipping_Model_Carrier_Abstract 
implements Mage_Shipping_Model_Carrier_Interface {
    /**
     * Carrier's code, as defined in parent class
     *
     * @var string
     */
    protected $_code = 'customshippingmethod';
    /**
     * Returns available shipping rates for Tejar Shipping carrier
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    { 
		//--- Check for minium and maximum order totals...........
		$total =  $request->getPackageValue();
		//$total = 5000;
		$minTotal = $this->getConfigData('min_order_total');

		if(!empty($minTotal) && ($total < $minTotal)) {
			return false;
		}
		
		$maxTotal = $this->getConfigData('max_order_total');
		if(!empty($maxTotal) && ($total > $maxTotal)) {
			return false;
		}
		
        /** @var Mage_Shipping_Model_Rate_Result $result */
        $result = Mage::getModel('shipping/rate_result');
        /** @var Tejar_CustomCheckout_Helper_Data $expressMaxProducts */
        $expressMaxWeight = Mage::helper('customshippingmethod')->getExpressMaxWeight();
        $expressAvailable = true;
        foreach ($request->getAllItems() as $item) {
            if ($item->getWeight() > $expressMaxWeight) {
                $expressAvailable = false;
            }
        }
        if ($expressAvailable) {
           // $result->append($this->_getExpressRate());
        }
        $result->append($this->_getStandardRate());
        //return $result;
		return $result;
    }
	
    /**
     * Returns Allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array(
            'standard'    =>  'Standard delivery',
            'express'     =>  'Express delivery',
        );
    }
    /**
     * Get Standard rate object
     *
     * @return Mage_Shipping_Model_Rate_Result_Method
     */
    protected function _getStandardRate()
    {
        /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        $rate = Mage::getModel('shipping/rate_result_method');
        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod('customshippingmethod');
        $rate->setMethodTitle($this->getConfigData('name'));
        $rate->setPrice($this->getConfigData('price'));
        $rate->setCost($this->getConfigData('price'));
        return $rate;
    }
    /**
     * Get Express rate object
     *
     * @return Mage_Shipping_Model_Rate_Result_Method
     */
    protected function _getExpressRate()
    {
        /** @var Mage_Shipping_Model_Rate_Result_Method $rate */
        $rate = Mage::getModel('shipping/rate_result_method');
        $rate->setCarrier($this->_code);
        $rate->setCarrierTitle($this->getConfigData('title'));
        $rate->setMethod('express');
        $rate->setMethodTitle('Express delivery');
        $rate->setPrice(12.3);
        $rate->setCost(0);
        return $rate;
    }

}