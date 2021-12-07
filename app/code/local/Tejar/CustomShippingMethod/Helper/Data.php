<?php 
/* CustomCheckout Helper 
 *
 * @category   Tejar
 * @package    Tejar_CustomCheckout
 * @class      Tejar_CustomCheckout_Helper_Data
 * @author     Zeeshan <zeeshan.zeeshan123@gmail.com>
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Tejar_CustomShippingMethod_Helper_Data extends Mage_Core_Helper_Abstract{
    const XML_EXPRESS_MAX_WEIGHT = 'carriers/customshippingmethod/express_max_weight';
    /**
     * Get max weight of single item for express shipping
     *
     * @return mixed
     */
    public function getExpressMaxWeight()
    {
        return Mage::getStoreConfig(self::XML_EXPRESS_MAX_WEIGHT);
    }
	
	
	public function getFinalShippingCost($_code, $quote){
		//--- Get all attributes of selected shippping method and calculate shipping cost..
		$shippingMethodPrice       = Mage::getStoreConfig('carriers/'.$_code.'/price', $quote->getStore()->getId());
		$shippingMethodHendligType = Mage::getStoreConfig('carriers/'.$_code.'/handling_type', $quote->getStore()->getId());
		$shippingMethodHendligFee  = Mage::getStoreConfig('carriers/'.$_code.'/handling_fee', $quote->getStore()->getId());
		$shippingMethodType        = Mage::getStoreConfig('carriers/'.$_code.'/type', $quote->getStore()->getId());
		
		
		
		//--- Check if Price or Free Shipping Subtotal is available...
		if($shippingMethodPrice){
			$finalShippingCost = $shippingMethodPrice;
		}else{
			$finalShippingCost = 0;
		}
		
		
		if(!$finalShippingCost || $finalShippingCost==""){
			$finalShippingCost = 0;
		}
		
		//--- Check if Handling fee is enabled...
		if($shippingMethodHendligFee){
			if($shippingMethodHendligType=="P"){
				$finalShippingCost = (float) $finalShippingCost + ($finalShippingCost / 100) * $shippingMethodHendligFee;
				
			}else{
				$finalShippingCost = (float) $finalShippingCost + $shippingMethodHendligFee;
			}
		}
		
		//--- Check if per item option was selected...
		if($shippingMethodType=="I"){
			$itemsCount = Mage::helper('checkout/cart')->getSummaryCount();
			$finalShippingCost = (float) $finalShippingCost *  $itemsCount;
		}
		
		//$finalShippingCost = Mage::helper('checkout/cart')->getSummaryCount();
		
		return $finalShippingCost;
		
	}
	
}