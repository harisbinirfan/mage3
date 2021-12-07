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


/**
 * Product list
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Catalog_Block_Product_List extends Mage_Catalog_Block_Product_List
{
	/**
     * Get catalog whishlist id
     *
     */
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



	/**
     * Get catalog whishlist id
     *
     */
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


	/**
     * Get catalog whishlist id
     *
     */
    public function getCustomProduct($_product)
    {
		$this->_getCustomCurrentProduct = $_product;
		$this->_getCustomProductUrl = $_product->getProductUrl();
        $product = Mage::getModel('catalog/product')->load($_product->getId());
		$this->_getCustomProduct = $product;
		if($product->getTypeId()=="simple" && $product->getVisibility()==1){
			$parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($_product->getId());
			$parentId  = $parentIds[0];
			if($parentId!=null){
				$product    = Mage::getModel('catalog/product')->load($parentId);
				$this->_getCustomProduct = $product;
				$this->_getCustomProductUrl = $product->getProductUrl();
			}
		}
		return  $product;


    }

	public function getCustomProductUrl()
    {
		$productUrl = $this->_getCustomProductUrl;
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

    public function getLabels()
      {
  		$html = '';
  		$image = '';
  		$labels = array();
  		$collectionlabel = Mage::getModel('itactica_productlabel/label')
  		->getCollection()->addFieldToFilter('status', array('eq' => 1))
  		->addDateFilter()
  		->addFieldToFilter('store_ids', array('finset' => Mage::app()->getStore()->getId()))
  		->setOrder('priority', 'desc');

  		$imagePath = Mage::helper('itactica_productlabel/image')->getImageBaseUrl();

  		foreach($collectionlabel as $collection){
  			$image = '';
  			if ($collection->getImage()) {
  				$image = ' background-image: url(' . $imagePath . $collection->getImage() . ');';
  			}

  			$html .= '<div class="intenso-product-label-wrapper position-' . $collection->getPosition() . ' ' . $collection->getCustomClassname() . '" style="' . $collection->getLabelStyles() . '">';
  			$html .= '<span class="intenso-product-label" style="' . $image . $collection->getTextStyles() . '">' . $collection->getText() . '</span>';
  			$html .= '</div>';
  			$labels[$collection->getName()] = $html;
  			$html = '';
  		}

  		return $labels;
  	}

}
