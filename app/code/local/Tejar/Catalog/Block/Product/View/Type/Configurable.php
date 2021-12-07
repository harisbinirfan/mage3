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
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Catalog super product configurable part block
 *
 * @category   Tejar
 * @package    Tejar_Catalog
 * @author     Zeeshan
 */
class Tejar_Catalog_Block_Product_View_Type_Configurable extends Mage_Catalog_Block_Product_View_Type_Configurable
{
	/**
    * Check Custom Stock Value of the given product
    * @name getCustomStockStatus
	* @param product object
    * @return null/Integer
    */
	public function getCustomStockStatus($product){

		if($product->getCustomStockAvailability()){
			//---
			$inStockValues = array('1115','1118');
			return in_array($product->getCustomStockAvailability(), $inStockValues) && $product->getStockItem()->getIsInStock()?true:false;
		}else{
			return $product->getStockItem()->getIsInStock()?true:false;
		}
	}

	/**
    * Check Custom Stock Value of the given product
    * @name getCustomStockStatus
	* @param product object
    * @return null/Integer
    */
	public function getCustomStockStatusText($customStockProduct){

		$customStockStatusText = "";
		$customStockValue = $customStockProduct->getCustomStockAvailability();
		if($customStockValue){
			switch($customStockValue){
				case '1115':
				$customStockStatusText = "Pre Order";
				break;
				case '1116':
				$customStockStatusText = "Discontinued By Manufacturer";
				break;
				case '1117':
				$customStockStatusText = "No Longer Available";
				break;
				case '1118':
				$customStockStatusText = "Back Order";
				break;

			}
		return $customStockStatusText;
		}else{
			return false;
		}
	}

	    /**
     * Get Allowed Products
     *
     * @return array
     */
    public function getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            $products = array();
            $skipSaleableCheck = Mage::helper('catalog/product')->getSkipSaleableCheck();
            $allProducts = $this->getProduct()->getTypeInstance(true)
                ->getUsedProducts(null, $this->getProduct());
			$productIds = array();
			foreach ($allProducts as $product) {
				$productIds[] = $product->getId();
			}
			$productStatus = Mage::getModel('catalog/product_status')->getProductStatus($productIds,$this->getProduct()->getStoreId());
            foreach ($allProducts as $product) {
				if($productStatus[$product->getId()] == 2){
					continue;
				}
                if ($product->isSaleable()
                    || $skipSaleableCheck
                    || (!$product->getStockItem()->getIsInStock()
                        && Mage::helper('cataloginventory')->isShowOutOfStock())) {
                    $products[] = $product;
                }
            }
            $this->setAllowProducts($products);
        }
        return $this->getData('allow_products');
    }

	/**
		* Get Allowed Products
		*
		* @return array
		*/
	 public function __getAllowProducts()
	 {
			 if (!$this->hasAllowProducts()) {
					 $products = array();
					 $skipSaleableCheck = Mage::helper('catalog/product')->getSkipSaleableCheck();
					 $allProducts = $this->getProduct()->getTypeInstance(true)
							 ->getUsedProducts(null, $this->getProduct());
					 foreach ($allProducts as $product) {
						 $isEnabled = Mage::getSingleton('catalog/product_status')->getProductStatus($product->getId());
							 if ($product->isSaleable()
									 || $skipSaleableCheck
									 || ($isEnabled[$product->getId()]==1 && (!$product->getStockItem()->getIsInStock()
											 && Mage::helper('cataloginventory')->isShowOutOfStock()))) {
									 $products[] = $product;
							 }
					 }
					 $this->setAllowProducts($products);
			 }
			 return $this->getData('allow_products');
	 }

    /**
     * Get Allowed Products
     *
     * @return array
     */
    public function ___getAllowProducts()
    {
        if (!$this->hasAllowProducts()) {
            $products = array();
            $skipSaleableCheck = Mage::helper('catalog/product')->getSkipSaleableCheck();
            $allProducts = $this->getProduct()->getTypeInstance(true)
                ->getUsedProducts(null, $this->getProduct());
            foreach ($allProducts as $product) {
			//--- In order to get 'Out of Stock Asscociated Product BUT excluding 'Disabled' products'
				//--- Get product Stock Status...
				$productStockStatus = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getIsInStock();

				//--- Get Current Store Id
				if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) // store level
				{
					$store_id = Mage::getModel('core/store')->load($code)->getId();
				}
				elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) // website level
				{
					$website_id = Mage::getModel('core/website')->load($code)->getId();
					$store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
				}
				else // default level
				{
					$store_id = 0;
				}

				//--- Get product By Store & Store Status...
				$productByStore = Mage::getModel('catalog/product')->setStoreId($store_id)->load($product->getId());

				$productStatus = $productByStore->getStatus();
				//echo $productStatus," -- ";
				if($productStatus!=2){
				//--- check to allow 'Out of Stock' Products as well (Product Stock Status = 0)...
					if ($product->isSaleable() || $skipSaleableCheck || $productStockStatus==0) {
							$products[] = $product;
					}
				}
            }
            $this->setAllowProducts($products);
        }
        return $this->getData('allow_products');
    }

		/**
		* Composes configuration for js
		*
		* @return string
		*/
	    public function getJsonConfig()
	    {
	        $attributes = array();
	        $options    = array();
	        $store      = $this->getCurrentStore();
	        $taxHelper  = Mage::helper('tax');
	        $currentProduct = $this->getProduct();

	        $preconfiguredFlag = $currentProduct->hasPreconfiguredValues();
	        if ($preconfiguredFlag) {
	            $preconfiguredValues = $currentProduct->getPreconfiguredValues();
	            $defaultValues       = array();
	        }

	        $productStock = array();
			$stockAvailability = array();
	        foreach ($this->getAllowProducts() as $product) {
	            $productId  = $product->getId();
	            $productStock[$productId] = $product->getStockItem()->getIsInStock();
	            foreach ($this->getAllowAttributes() as $attribute) {
	                $productAttribute   = $attribute->getProductAttribute();
	                $productAttributeId = $productAttribute->getId();
	                $attributeValue     = $product->getData($productAttribute->getAttributeCode());



	                if (!isset($options[$productAttributeId])) {
	                    $options[$productAttributeId] = array();
	                }

	                if (!isset($options[$productAttributeId][$attributeValue])) {
	                    $options[$productAttributeId][$attributeValue] = array();
	                }

	                $options[$productAttributeId][$attributeValue][] = $productId;

	            }


				$attributeValue = Mage::getModel('catalog/product')->load($product->getId())->getAttributeText('custom_stock_availability');
				$stockAvailability[$productId]["custom_stock"] = $attributeValue;

				if($product->getStockItem()->getIsInStock()){
					$stockAvailability[$productId]["stock"] = 1;
				}else{
					$stockAvailability[$productId]["stock"] = 0;
					$stockAvailability[$productId]["outOfStock"] = 1;

				}

	        }

	        $this->_resPrices = array(
	            $this->_preparePrice($currentProduct->getFinalPrice())
	        );

	        foreach ($this->getAllowAttributes() as $attribute) {
	            $productAttribute = $attribute->getProductAttribute();
	            $attributeId = $productAttribute->getId();
	            $info = array(
	               'id'        => $productAttribute->getId(),
	               'code'      => $productAttribute->getAttributeCode(),
	               'label'     => $attribute->getLabel(),
	               'options'   => array()
	            );

	            $optionPrices = array();
	            $prices = $attribute->getPrices();
	            if (is_array($prices)) {
	                foreach ($prices as $value) {
	                    if(!$this->_validateAttributeValue($attributeId, $value, $options)) {
	                        continue;
	                    }
	                    $currentProduct->setConfigurablePrice(
	                        $this->_preparePrice($value['pricing_value'], $value['is_percent'])
	                    );
	                    $currentProduct->setParentId(true);
	                    Mage::dispatchEvent(
	                        'catalog_product_type_configurable_price',
	                        array('product' => $currentProduct)
	                    );
	                    $configurablePrice = $currentProduct->getConfigurablePrice();

	                    if (isset($options[$attributeId][$value['value_index']])) {
	                        $productsIndexOptions = $options[$attributeId][$value['value_index']];
	                        $productsIndex = array();
							$stockIndex = array();
	                        foreach ($productsIndexOptions as $productIndex) {
								$productsIndex[] = $productIndex;
								$stockIndex["custom_stock"][$productIndex] = $stockAvailability[$productIndex]["custom_stock"];
								$stockIndex["stockStatus"][$productIndex] = $stockAvailability[$productIndex]["stock"];
								isset($stockAvailability[$productIndex]["outOfStock"]) ? $stockIndex["outOfStock"][$productIndex] = $stockAvailability[$productIndex]["outOfStock"] : "";
	                        }
	                    } else {
	                        $productsIndex = array();
							$stockIndex = array();
	                    }

	                    $info['options'][] = array(
	                        'id'        => $value['value_index'],
	                        'label'     => $value['label'],
	                        'price'     => $configurablePrice,
	                        'oldPrice'  => $this->_prepareOldPrice($value['pricing_value'], $value['is_percent']),
	                        'products'  => $productsIndex,
							'stock' =>  $stockIndex
	                    );


	                    $optionPrices[] = $configurablePrice;
	                }
	            }
	            /**
	             * Prepare formated values for options choose
	             */
	            foreach ($optionPrices as $optionPrice) {
	                foreach ($optionPrices as $additional) {
	                    $this->_preparePrice(abs($additional-$optionPrice));
	                }
	            }
	            if($this->_validateAttributeInfo($info)) {
	               $attributes[$attributeId] = $info;
	            }

	            // Add attribute default value (if set)
	            if ($preconfiguredFlag) {
	                $configValue = $preconfiguredValues->getData('super_attribute/' . $attributeId);
	                if ($configValue) {
	                    $defaultValues[$attributeId] = $configValue;
	                }
	            }
	        }

	        $taxCalculation = Mage::getSingleton('tax/calculation');
	        if (!$taxCalculation->getCustomer() && Mage::registry('current_customer')) {
	            $taxCalculation->setCustomer(Mage::registry('current_customer'));
	        }

	        $_request = $taxCalculation->getDefaultRateRequest();
	        $_request->setProductClassId($currentProduct->getTaxClassId());
	        $defaultTax = $taxCalculation->getRate($_request);

	        $_request = $taxCalculation->getRateRequest();
	        $_request->setProductClassId($currentProduct->getTaxClassId());
	        $currentTax = $taxCalculation->getRate($_request);

	        $taxConfig = array(
	            'includeTax'        => $taxHelper->priceIncludesTax(),
	            'showIncludeTax'    => $taxHelper->displayPriceIncludingTax(),
	            'showBothPrices'    => $taxHelper->displayBothPrices(),
	            'defaultTax'        => $defaultTax,
	            'currentTax'        => $currentTax,
	            'inclTaxTitle'      => Mage::helper('catalog')->__('Incl. Tax')
	        );

	        $config = array(
	            'attributes'        => $attributes,
	            'template'          => str_replace('%s', '#{price}', $store->getCurrentCurrency()->getOutputFormat()),
	            'basePrice'         => $this->_registerJsPrice($this->_convertPrice($currentProduct->getFinalPrice())),
	            'oldPrice'          => $this->_registerJsPrice($this->_convertPrice($currentProduct->getPrice())),
	            'productId'         => $currentProduct->getId(),
	            'chooseText'        => Mage::helper('catalog')->__('Choose an Option...'),
	            'taxConfig'         => $taxConfig
	        );

	        if ($preconfiguredFlag && !empty($defaultValues)) {
	            $config['defaultValues'] = $defaultValues;
	        }

	        $config = array_merge($config, $this->_getAdditionalConfig());


	        return Mage::helper('core')->jsonEncode($config);
	    }

}
