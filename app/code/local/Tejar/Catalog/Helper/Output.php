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
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Tejar_Catalog_Helper_Output extends Mage_Catalog_Helper_Output
{

    public function getWhishlistId()
    {
        $_collection = Mage::helper('wishlist')->getWishlistItemCollection();
		    $ProductsId = "";
        if ($_collection->getSize()){
			foreach($_collection as $product){
				$ProductsId .= $product->getProductId() . ',';
			}
			$ProductsId = rtrim($ProductsId, ',');
			return $ProductsId;
        }
        return false;
    }

    public function getCompareId()
    {
        $_collection = Mage::helper('catalog/product_compare')->getItemCollection();
		    $ProductsId = "";
        if ($_collection->getSize()){
			foreach($_collection as $product){
				$ProductsId .= $product->getProductId() . ',';
			}
			$ProductsId = rtrim($ProductsId, ',');
			return $ProductsId;
        }
        return false;
    }

    public function getCustomProduct($_product)
    {
		$this->_getCustomCurrentProduct = $_product;
        $product = Mage::getModel('catalog/product')->load($_product->getId());
		$this->_getCustomProduct = $product;
		if($product->getTypeId()=="simple" && $product->getVisibility()==1){
			$parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($_product->getId());
			$parentId  = $parentIds[0];
			if($parentId!=null){
				$product    = Mage::getModel('catalog/product')->load($parentId);
				$this->_getCustomProduct = $product;
			}
		}
		return  $product;


    }

	public function getCustomProductUrl()
    {
		$product = $this->_getCustomProduct;
		$productUrl = $product->getProductUrl();
		return  $productUrl;
    }

	public function getCustomInStockStatus()
    {

		$customStockStatusText = "";
		$customInStockStatus = true;
		$product = $this->_getCustomProduct;
		$_product = $this->_getCustomCurrentProduct;
		if($product->hasData('custom_stock_availability') && $product->getCustomStockAvailability()){
			$customStockStatusText = Mage::helper('catalog/data')->getCustomStockStatusText($product);
			if(Mage::helper('catalog/data')->customStockAddtoCartStatus($_product)){
				$customInStockStatus = true;
			}else{
				$customInStockStatus = false;
			}
		}
		$result['stockStatusText'] = $customStockStatusText;
		$result['inStockStatus'] = $customInStockStatus;
		return  $result;
    }
}
