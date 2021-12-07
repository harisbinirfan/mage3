<?php

/**
 * Catalog data helper
 *
 * @category   Tejar
 * @package    Tejar_Catalog
 * @author     Zeeshan
 */
 
class Tejar_Catalog_Helper_Data extends Mage_Catalog_Helper_Data{
	
	public function getOrderQuoteError($items){
		foreach($items as $item){
			//--- Load Product By
			$sku = $item->getProduct()->getSku();
			$product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
			if (!$product->getStockItem()->getIsInStock() || !$this->customStockAddtoCartStatus($product)){
				return false;
			}
		}
		return true;
	}

	public function customStockStatus($_product){
		$defaultStockDisplayStatus = Mage::getStoreConfig('cataloginventory/options/show_out_of_stock');
		$customStockProduct = Mage::getModel('catalog/product')->load($_product->getId());
		$customStockAvailability = $customStockProduct->getAttributeText('custom_stock_availability');
		
		if($defaultStockDisplayStatus==0 && ($customStockAvailability =="No Longer Available" || $customStockAvailability =="Discontinued" )){
			return false;
		}
		return true;
	}
	
	public function customStockFinalStatus($_product){
		$customStockProduct      = Mage::getModel('catalog/product')->load($_product->getId());
		$productDefaultStatus    = $customStockProduct->isAvailable();
		$customStockAvailability = $customStockProduct->getAttributeText('custom_stock_availability');
		
		if($productDefaultStatus == false && ($customStockAvailability =="No Longer Available" || $customStockAvailability =="Discontinued" )){
			return false;
		}
		return true;
	}
	
	public function customStockAlertStatus($_product){
		$customStockProduct      = Mage::getModel('catalog/product')->load($_product->getId());
		$customStockAvailability = $customStockProduct->getAttributeText('custom_stock_availability');
		
		if(!$customStockAvailability || ($customStockAvailability =="No Longer Available" || $customStockAvailability =="Discontinued" )){
			return false;
		}
		return true;
	}
	
	public function customStockAddtoCartStatus($_product){
		
			//Mage::getStoreConfig('cataloginventory/options/display_product_stock_status');
			$customStockProduct = Mage::getModel('catalog/product')->load($_product->getId());
			$customStockAvailability = $customStockProduct->getAttributeText('custom_stock_availability');
			if($customStockAvailability =="No Longer Available" || $customStockAvailability =="Discontinued" ){
				return false;
			}
			return true;
		}	
		
	public function getCustomStockStatusText($customStockProduct){
		//echo "zee---> ".$customStockProduct->getId();
		$customStockAvailability = $customStockProduct->getAttributeText('custom_stock_availability');
		return $customStockAvailability;	
	}

}