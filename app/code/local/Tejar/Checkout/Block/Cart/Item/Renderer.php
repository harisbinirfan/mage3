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
 * @package     Mage_Checkout
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shopping cart item render block
 *
 * @category    Mage
 * @package     Mage_Checkout
 * @author      Magento Core Team <core@magentocommerce.com>
 *
 * @method Mage_Checkout_Block_Cart_Item_Renderer setProductName(string)
 * @method Mage_Checkout_Block_Cart_Item_Renderer setDeleteUrl(string)
 */
class Tejar_Checkout_Block_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer
{
  
    /**
     * Check Product has URL
     *
     * @return bool
     */
    public function hasProductUrl()
    {
        if ($this->_ignoreProductUrl) {
            return false;
        }
		
        if ($this->_productUrl || $this->getItem()->getRedirectUrl()) {
            return true;
        }
		
        $product = $this->getProduct();
		
        $option  = $this->getItem()->getOptionByCode('product_type');
		
        if ($option) {
            $product = $option->getProduct();
        }
		
        if ($product->isVisibleInSiteVisibility()) {
            return true;
        }
		
		$hasParent = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
		
		if(!empty($hasParent)){
			return true;
		}
		
		
        return false;
    }

    /**
     * Retrieve URL to item Product
     *
     * @return string
     */
    public function getProductUrl()
    {
        if (!is_null($this->_productUrl)) {
            return $this->_productUrl;
        }
		
        if ($this->getItem()->getRedirectUrl()) {
            return $this->getItem()->getRedirectUrl();
        }

        $product = $this->getProduct();
        $option  = $this->getItem()->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }
		
		$hasParent = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
		
		if(!empty($hasParent) && $product->isVisibleInSiteVisibility() == false){
			$parentParent = Mage::getModel('catalog/product')->load($hasParent[0]);
			return $product->getUrlModel()->getUrl($parentParent);
		}

        return $product->getUrlModel()->getUrl($product);
    }
}
