<?php
class Tejar_Catalog_Helper_Product extends Mage_Catalog_Helper_Product
{
  
  /**
     * Check if a product can be shown
     *
     * @param  Mage_Catalog_Model_Product|int $product
     * @return boolean
     */
    public function canShow($product, $where = 'catalog')
    {
        if (is_int($product)) {
            $product = Mage::getModel('catalog/product')->load($product);
        }

        /* @var $product Mage_Catalog_Model_Product */
        if (!$product->getId()) {
            return false;
        }
		
		
		//--- Avoid this check if product is configurable.. (To display Configurable product even if set to 'Not-visible-Individually' )
		if($product->getTypeId()=="simple"){
			
			return $product->isVisibleInCatalog() && $product->isVisibleInSiteVisibility();
		}else{
			
			//echo $product->getStatus();die;
			if($product->getStatus()==1){
				//--- check If the product is Enabled but visibility is 'not visible individually'
					if($product->getVisibility()==1){
						//echo Mage::helper('core/url')->getCurrentUrl(); die;
						
						return $product->getProductUrl()!== Mage::helper('core/url')->getCurrentUrl()? true:false;
					}else{
						return true;
					}
				
			}else{
				return false;
				
			}
			
		}
       
    }
}