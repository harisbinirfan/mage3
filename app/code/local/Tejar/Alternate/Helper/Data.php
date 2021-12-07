<?php
class Tejar_Alternate_Helper_Data extends Mage_Core_Helper_Abstract
{
   
	
	/*
	*@name    getPageDefaultUrlKey
	*@desc    This function will return URL string/key excluding the domain name
	*@returns String 
	*/
	public function getPageDefaultUrlKey(){
		$currentStore = Mage::app()->getStore();
		$storeCode    = $currentStore->getCode();
		if($storeCode=="pk" || $storeCode=="ae"){
			//echo $currentStore->getBaseUrl();die;
			return $urlSuffix = str_replace($currentStore->getBaseUrl(),'',Mage::helper('core/url')->getCurrentUrl());
		}else{
			return $urlSuffix = str_replace($this->getDefaultStoreUrl(),'',Mage::helper('core/url')->getCurrentUrl());
		}
	}
	/*
	*@name    getPageDefaultUrlKey
	*@desc    This function will return URL string/key excluding the domain name
	*@returns String 
	*/
	public function getPageUrlKey(){
		  return $urlSuffix = str_replace($this->getUrl(),'',Mage::helper('core/url')->getCurrentUrl());
	}
	
	/*
	*@name      getDefaultStoreUrl
	*@desc      This function will return default Store URL     
	*@return	String
	*/
	public function getDefaultStoreUrl(){
		return Mage::app()->getStore(1)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
	}
	
	public function isCanonical(){
		
		$currentStore = Mage::app()->getStore();
		$storeCode    = $currentStore->getCode();
		$storeId      = $currentStore->getId();
		$isProd       = Mage::registry('current_product');
		$isCat        = Mage::registry('current_category');
		//echo "<pre>"; var_dump($isCat);die;
		if($isCat && $isProd==null){
			
			if(Mage::getStoreConfig('catalog/seo/category_canonical_tag', $storeId)){
				return true;
			}else{
				return false;
			}
		}elseif($isProd){
			
			if(Mage::getStoreConfig('catalog/seo/product_canonical_tag', $storeId)){
				return true;
			}else{
				return false;
			}
		}
	}
}