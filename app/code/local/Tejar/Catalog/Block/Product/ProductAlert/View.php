<?php
/* @category   Tejar
 * @package    Tejar_ProductAlert
 * @author     Zeeshan
 */
class Tejar_Catalog_Block_Product_ProductAlert_View extends Mage_ProductAlert_Block_Product_View
{
	
	/**
     * Check whether the stock alert data can be shown and prepare related data
     *
     * @return void
     */
    public function ___prepareStockAlertData()
    {
		//var_dump(Mage::helper('catalog/data')->customStockAlertStatus($this->_product));die;
		//---Zee Code Add support for Custom Stock 'Pre Order & Back Order'...
		$clearAlert = false;
		if( $this->_product->isAvailable() && !Mage::helper('catalog/data')->customStockAlertStatus($this->_product)){
			$clearAlert = true;
		 }
		
		if ($clearAlert && (!$this->_getHelper()->isStockAlertAllowed() || !$this->_product || $this->_product->isAvailable())) {
		
            $this->setTemplate('');
            return;
        }
        $this->setSignupUrl($this->_getHelper()->getSaveUrl('stock'));
    }
  
}
