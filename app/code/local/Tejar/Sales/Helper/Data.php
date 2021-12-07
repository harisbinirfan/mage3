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
 * @package     Mage_Sales
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales module base helper
 *
 * @category    Mage
 * @package     Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Sales_Helper_Data extends Mage_Sales_Helper_Data
{
  

    /**
     * Get old field map
     *
     * @param string $entityId
     * @return array
     */
    public function getAdditionalData($_item)
    {
		$object 			= 	new Varien_Object();
		
		$cartThumbnailConfig = Mage::getStoreConfig('checkout/cart/configurable_product_image');
		$product = Mage::getModel('catalog/product')->load($_item->getProductId());
		$object->setProduct($product);
		$productImageUrl = Mage::Helper('catalog/image')->init($product, 'thumbnail')->resize(150);
		$productUrl = $product->getProductUrl();
		$productName = $this->escapeHtml($_item->getName());
       	if($product->getTypeId() == "configurable"){
			if($cartThumbnailConfig==="itself"){
				if($_item->getSku() != ''){
					$childProduct = Mage::getModel('catalog/product')->loadByAttribute('sku', $_item->getSku());
					$object->setChildProduct($childProduct);
					$productImageUrl = $this->helper('catalog/image')->init($childProduct, 'thumbnail')->resize(150);				
				}
			}
		}

		if($product->getTypeId() == "simple"){
			if(!$product->isVisibleInSiteVisibility()){
				$parentProducts =  Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($_item->getProductId());
				if(!empty($parentProducts)){
					$parentProduct = Mage::getModel('catalog/product')->load($parentProducts[0]);
					$object->setParentProduct($parentProduct);
					$productUrl = $parentProduct->getProductUrl();
				}
			}
		}
		
		
		$object->setProductName($productName);
		$object->setProductImage($productImageUrl);
		$object->setProductUrl($productUrl);
		
		
        return $object;
    }
}
