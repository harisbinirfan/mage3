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
 * Catalog product related items block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Tejar_Catalog_Block_Product_List_Upsell extends Mage_Catalog_Block_Product_List_Upsell
{
    

    protected function _prepareData()
    {
        $product = Mage::registry('product');
        /* @var $product Mage_Catalog_Model_Product */
        $this->_itemCollection = $product->getUpSellProductCollection()
            ->setPositionOrder()
            ->addStoreFilter()
        ;
        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($this->_itemCollection,
                Mage::getSingleton('checkout/session')->getQuoteId()
            );

            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
		
		// Load Parent Product
		$productId = $product->getId();
        $parentIds = Mage::getSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
		if (!empty($parentIds)) {
			$this->_itemCollection->getSelect()->where("e.entity_id not in(?)",$parentIds);
		}
		
//        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($this->_itemCollection);
        // Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);
		// Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($this->_itemCollection);

        // if ($this->getItemLimit('upsell') > 0) {
        //     $this->_itemCollection->setPageSize($this->getItemLimit('upsell'));
        // }

        $this->_itemCollection->load();
		
		
        $storeId = $product->getStore()->getId();
		$categoryIds = $product->getCategoryIds();
		$finalPrice = $product->getFinalPrice();
		$attribute = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Category::ENTITY, 'collection_type');
		$attributeId = $attribute->getAttributeId();
		$attributeTable = $attribute->getBackendTable();
		
		$readAdapter = $this->_itemCollection->getConnection('core_read');
		$select = $readAdapter->select()->from(array('e' => 'catalog_product_index_price'), array('entity_id' => 'e.entity_id'))
					->joinInner(array('at_category_product' => 'catalog_category_product'),"(`e`.`entity_id` = `at_category_product`.`product_id` )", array())
					->joinInner(array('at_collection_type_default' => $attributeTable),"( `at_collection_type_default`.`entity_id` = `at_category_product`.`category_id` ) AND ( `at_collection_type_default`.`attribute_id` = '".$attributeId."' ) AND `at_collection_type_default`.`store_id` = 0", array())
					->joinLeft(array('at_collection_type' => $attributeTable),"(`at_collection_type`.`entity_id` = `at_category_product`.`category_id` ) AND ( `at_collection_type`.`attribute_id` = '".$attributeId."' ) AND ( `at_collection_type`.`store_id` = '".$storeId."')", array())
					->where("e.website_id = {$storeId}")
					->where("e.final_price > ?", $finalPrice)
					->where("at_category_product.category_id IN(?)", $categoryIds)
					->where("IF(at_collection_type.value_id > 0, at_collection_type.value, at_collection_type_default.value) IS NULL")
					->group("e.entity_id"); 
			
		$autoProductIds = array();
		foreach ($readAdapter->fetchAll($select) as $row) {
			$autoProductIds[] = $row['entity_id'];
		}
				
		$products = array();
		foreach($this->_itemCollection as $item){
			$products[] = $item->getId();
		}
		
		$ids = implode(',',$products);
		
		$products = array_merge($autoProductIds,$products);
		
		$collection = Mage::getModel('catalog/product')->getCollection();
		$collection->addAttributeToFilter('entity_id', array('in' => $products));
		$collection->getSelect()->joinLeft(array('parent_product' => $collection->getTable('catalog/product_super_link')),'(e.entity_id = parent_product.product_id)');
		
		$productIds = array();
		foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $row) {
			if(array_key_exists("parent_id",$row)){
				if($row['parent_id'] != NULL){
					$productIds[] = $row['parent_id'];	
				}  else {
					$productIds[] = $row['entity_id'];
				}
			} else {
				$productIds[] = $row['entity_id'];
			}
		}
		
		
		$collection = Mage::getModel('catalog/product')->getCollection();
		$collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes());
		$collection->addAttributeToFilter('entity_id', array('in' => $productIds));
		if($product->getId()){
			$collection->addAttributeToFilter('entity_id', array('nin' => array($product->getId())));
		}
        if(!empty($ids)){
		$collection->getSelect()->order(new Zend_Db_Expr("CASE WHEN e.entity_id IN({$ids}) THEN 0 ELSE 1 END"));
        }

		$this->_itemCollection = $collection;
		
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);
		Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($this->_itemCollection);
		
		
		if ($this->getItemLimit('upsell') > 0) {
            $this->_itemCollection->setPageSize($this->getItemLimit('upsell'));
        } else {
			$this->_itemCollection->setPageSize(20);
		}

        $this->_itemCollection->load();

        /**
         * Updating collection with desired items
         */
        Mage::dispatchEvent('catalog_product_upsell', array(
            'product'       => $product,
            'collection'    => $this->_itemCollection,
            'limit'         => $this->getItemLimit()
        ));

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }

   
}
