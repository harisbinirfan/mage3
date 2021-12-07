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
 * @author     Magento Core Team <core@magentocommerce.com>
 */

class Tejar_Catalog_Block_Product_List_Crosssell extends Mage_Catalog_Block_Product_List_Crosssell
{
  
    /**
     * Prepare crosssell items data
     *
     * @return Mage_Catalog_Block_Product_List_Crosssell
     */
    protected function _prepareData()
    {
        $product = Mage::registry('product');
        /* @var $product Mage_Catalog_Model_Product */

        $this->_itemCollection = $product->getCrossSellProductCollection()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->setPositionOrder()
            ->addStoreFilter();
			
		// Load Parent Product
		$productId = $product->getId();
        $parentIds = Mage::getSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
		if (!empty($parentIds)) {
			$this->_itemCollection->getSelect()->where("e.entity_id not in(?)",$parentIds);
		}
		
		
		

//        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($this->_itemCollection);
                // Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);
		// Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($this->_itemCollection);

		$this->_itemCollection->load();
		
		
		$products = array();
		foreach($this->_itemCollection as $item){
			$products[] = $item->getId();
		}
		
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
		
		$this->_itemCollection = $collection;
		
		Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($this->_itemCollection);
		Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($this->_itemCollection);
		
        $this->_itemCollection->load();

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }



}
