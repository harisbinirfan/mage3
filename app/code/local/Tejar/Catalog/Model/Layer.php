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
 * Catalog view layer model
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Catalog_Model_Layer extends Mage_Catalog_Model_Layer
{

	protected $_productIds = array();
	public function productIds(){
		return $this->_productIds;
	}

  /**
   * Retrieve current layer product collection
   *
   * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
   */

public function getProductCollection()
{


    if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
      $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
    } else {

      $category = Mage::getModel('catalog/category')->load($this->getCurrentStore()->getRootCategoryId());

      if(!$this->getCurrentCategory()->getCollectionType() || $this->getCurrentCategory()->getCollectionType() == 1){

        // 3SD CODE DEFAULT CATAGORY COLLECTION
        $collection = $this->getCurrentCategory()->getProductCollection();
        /********************** QUERY TO DISPLAY OUT OF STOCK PRODUCTS TO THE END OF LIST IN CATEGORY PAGE ***********************/
        $collection->getSelect()->joinLeft(array('stock_status' => $collection->getTable('cataloginventory/stock_status')),'e.entity_id = stock_status.product_id AND stock_status.website_id='.Mage::app()->getWebsite()->getId());
        $collection->getSelect()->order('stock_status DESC');

      } elseif($this->getCurrentCategory()->getCollectionType() == 2) {

			//-- 3SD CODE DEALS CATAGORY COLLECTION
			$storeId  = Mage::app()->getStore()->getId();
			$todayStartOfDayDate  = Mage::app()->getLocale()->date()->setTime('00:00:00')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
			$todayEndOfDayDate  = Mage::app()->getLocale()->date()->setTime('23:59:59')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
			$tomorrow = mktime(0, 0, 0, date('m'), date('d')+1, date('y'));
			$collection = $category->getProductCollection();
			$collection->addAttributeToFilter(
			  array(
				array('attribute' => 'special_price', 'is'=>new Zend_Db_Expr('not null'))
			  )
			)
			->addAttributeToFilter(
				array(
				  array('attribute' => 'price', 'is'=>new Zend_Db_Expr('not null'))
				)
			  )
			->addAttributeToFilter('special_from_date', array('or'=> array(
			0 => array('date' => true, 'to' => $todayEndOfDayDate),
			1 => array('is' => new Zend_Db_Expr('null')))
			), 'left')
			->addAttributeToFilter('special_to_date', array('or'=> array(
			0 => array('date' => true, 'from' => $todayStartOfDayDate),
			1 => array('is' => new Zend_Db_Expr('null')))
			), 'left');

			if($collection->isEnabledFlat()){
				$collection->getSelect()->Where('e.special_price < e.price');
			} else {
				$collection->getSelect()->Where('(IF(at_special_price.value_id > 0, at_special_price.value, at_special_price_default.value)) < (IF(at_price.value_id > 0, at_price.value, at_price_default.value))');
			}

			$event =  Mage::app()->getRequest()->getParam('event');
		$matchEvent = preg_match('/national/i',$event);
		if($event && $matchEvent){
			$sourcingOptionIds = array();
			$sourcingAttribute = $collection->getAttribute('sourcing');
			foreach($sourcingAttribute->getSource()->getAllOptions() as $option){
				if($option['label']){
					if(preg_match('/^PK/i',$option['label'])){
						$sourcingOptionIds[] = $option['value'];
					}
				}
			}
			$collection->addAttributeToFilter(
			  array(
				array('attribute' => 'sourcing', 'IN' => $sourcingOptionIds)
			  )
			);
		}

			//-- 3SD CODE GET CONFIGURABLE PRODUCTS
			$collection->getSelect()->joinLeft(array('parent_product' => $collection->getTable('catalog/product_super_link')),'e.entity_id = parent_product.product_id');
      // Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
			$productIds = "";
			foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $row) {
				$productIds[]=$row['entity_id'];
				isset($row['parent_id'])&&($productIds[] = $row['parent_id']);
			}

			$this->_productIds = $productIds;

			//-- 3SD CODE ATTACHED SIMPLE AND CONFIGURABLE PRODUCTS
			$collection = $category->getProductCollection();
			$collection->addAttributeToFilter('entity_id', array('in' => $productIds));

			//-- 3SD CODE Set Order based on the Views Count, same order in maxQty Array..
			$sortOrderRequest =  Mage::app()->getRequest()->getParam('order');
			if(isset($sortOrderRequest)==null){
			   $collection->setOrder('updated_at', 'DESC');
			}

			$matchDealCategoryUrl = preg_match('/deals\/filter\/category/i',Mage::app()->getRequest()->getRequestString());
			if($matchDealCategoryUrl){
				$replaceUrl = Mage::app()->getRequest()->getRequestString();
				$replaceUrl = preg_replace('/deals\/filter\/category\//i','',Mage::app()->getRequest()->getRequestString());
				preg_match('!\d+!', $replaceUrl, $matches);				
				if(!empty($matches)){
					$collection->getSelect()->joinInner(array('dup_cat' => $collection->getTable('catalog/category_product_index')),'e.entity_id = dup_cat.product_id AND dup_cat.store_id="'.$storeId.'" AND dup_cat.category_id = "'.$matches[0].'" AND dup_cat.visibility IN('.join(',',Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds()).')');
				}
			}
			
			$matchFeaturedCategoryUrl = preg_match('/deals\/filter\/categories/i',Mage::app()->getRequest()->getRequestString());
			if($matchFeaturedCategoryUrl){
				$replaceUrl = Mage::app()->getRequest()->getRequestString();
				$replaceUrl = preg_replace('/deals\/filter\/categories\//i','',Mage::app()->getRequest()->getRequestString());
				preg_match('!\d+!', $replaceUrl, $matches);
				if(!empty($matches)){
					    $resource = Mage::getSingleton('core/resource');
						$readConnection = $resource->getConnection('core_read');
						$query = 'SELECT category_id FROM ' . $resource->getTableName('itactica_featuredcategories/slider_category') . ' where slider_id = ' . $matches[0];
						$results = $readConnection->fetchAll($query);
						$categoryIds = array();
						foreach($results as $result){
							$categoryIds[] = $result['category_id'];
						}
						$collection->getSelect()->joinInner(array('dup_cat' => $collection->getTable('catalog/category_product_index')),'e.entity_id = dup_cat.product_id AND dup_cat.store_id="'.$storeId.'" AND dup_cat.category_id IN ('.join(',',$categoryIds).') AND dup_cat.visibility IN('.join(',',Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds()).')');
				}
			}

			// Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);

      /********************** QUERY TO DISPLAY OUT OF STOCK PRODUCTS TO THE END OF LIST IN CATEGORY PAGE ***********************/
      $collection->getSelect()->joinLeft(array('stock_status' => $collection->getTable('cataloginventory/stock_status')),'e.entity_id = stock_status.product_id AND stock_status.website_id='.Mage::app()->getWebsite()->getId());
      $collection->getSelect()->order('stock_status DESC');

      } elseif($this->getCurrentCategory()->getCollectionType() == 3) {

			// 3SD CODE BESTSELLER CATAGORY COLLECTION
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$storeId    = Mage::app()->getStore()->getId();
			$from  = Mage::app()->getLocale()->date()->setDate(date("m-d-Y", strtotime("-1 years")))->setTime('00:00:00')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
			$to    = Mage::app()->getLocale()->date()->setTime('00:00:00')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);

			$resourceCollection = Mage::getResourceModel('sales/report_bestsellers_collection')
          	->setModel('catalog/product')
          	->setPeriod('day')
          	->setDateRange($from, $to)
          	->addStoreFilter($storeId);

          	$maxQty = array();
          	foreach ($resourceCollection as $item) {
          		$maxQty[] = $item->getProductId();
          	}

			$collection = $category->getProductCollection()->addAttributeToFilter('entity_id', array('in' => $maxQty));

			//-- 3SD CODE GET CONFIGURABLE PRODUCTS
			$collection->getSelect()->joinLeft(array('parent_product' => $collection->getTable('catalog/product_super_link')),'e.entity_id = parent_product.product_id');
      Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
			$productIds = "";
			foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $row) {
				$productIds[]=$row['entity_id'];
				isset($row['parent_id'])&&($productIds[] = $row['parent_id']);
			}

			//-- 3SD CODE ATTACHED SIMPLE AND CONFIGURABLE PRODUCTS
			$collection = $category->getProductCollection();
			$collection->addAttributeToFilter('entity_id', array('in' =>  $productIds));

			if(!empty($productIds)){
				$sortOrderRequest =  Mage::app()->getRequest()->getParam('order');
				if(isset($sortOrderRequest)==null){
					$collection->getSelect()->order(new Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $productIds).')'));
				}
			}


			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);

      } elseif($this->getCurrentCategory()->getCollectionType() == 4) {

			// 3SD CODE MOSTVIEWED CATAGORY COLLECTION
			$storeId    = Mage::app()->getStore()->getId();
			$from  = Mage::app()->getLocale()->date()->setDate(date("m-d-Y", strtotime("-3 months")))->setTime('00:00:00')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
			$to    = Mage::app()->getLocale()->date()->setTime('00:00:00')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');

			$resourceCollection = Mage::getResourceModel('reports/report_product_viewed_collection')->setPeriod('day')->setDateRange($from, $to)->addStoreFilter($storeId);

			//-- 3SD CODE GET CONFIGURABLE PRODUCTS
			$maxQty = array();
			foreach ($resourceCollection as $item) {
				$maxQty[] = $item->getProductId();
			}

			$collection = $category->getProductCollection()->addAttributeToFilter('entity_id', array('in' => $maxQty));

			//-- 3SD CODE GET CONFIGURABLE PRODUCTS
			$collection->getSelect()->joinLeft(array('parent_product' => $collection->getTable('catalog/product_super_link')),'e.entity_id = parent_product.product_id');
      Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
			$productIds = "";
			foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $row) {
				$productIds[]=$row['entity_id'];
				isset($row['parent_id'])&&($productIds[] = $row['parent_id']);
			}

			//-- 3SD CODE ATTACHED SIMPLE AND CONFIGURABLE PRODUCTS
			$collection = $category->getProductCollection();
			$collection->addAttributeToFilter('entity_id', array('in' => $productIds));

			if(!empty($productIds)){
				$sortOrderRequest =  Mage::app()->getRequest()->getParam('order');
				if(isset($sortOrderRequest)==null){
					$collection->getSelect()->order(new Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', $productIds).')'));
				}
			}

			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);

      } elseif($this->getCurrentCategory()->getCollectionType() == 5){

			// 3SD CODE NEW ARRIVAL CATAGORY COLLECTION
			$todayStartOfDayDate  = Mage::app()->getLocale()->date()->setTime('00:00:00')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
			$todayEndOfDayDate  = Mage::app()->getLocale()->date()->setTime('23:59:59')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
			$collection = $category->getProductCollection();
			$collection->addAttributeToFilter('news_from_date', array('and'=> array(
			0 => array('date' => true, 'to' => $todayEndOfDayDate),
			1 => array('is' => new Zend_Db_Expr('not null')))
			), 'left')
			->addAttributeToFilter('news_to_date', array('or'=> array(
			0 => array('date' => true, 'from' => $todayStartOfDayDate),
			1 => array('is' => new Zend_Db_Expr('null')))
			), 'left');

			//-- 3SD CODE GET CONFIGURABLE PRODUCTS
			$collection->getSelect()->joinLeft(array('parent_product' => $collection->getTable('catalog/product_super_link')),'e.entity_id = parent_product.product_id');
      // Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
			$productIds = "";
			foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $row) {
				$productIds[]=$row['entity_id'];
				isset($row['parent_id'])&&($productIds[] = $row['parent_id']);
			}

			//-- 3SD CODE ATTACHED SIMPLE AND CONFIGURABLE PRODUCTS
			$collection = $category->getProductCollection();
			$collection->addAttributeToFilter('entity_id', array('in' => $productIds));

			//-- 3SD CODE Set Order based on the Views Count, same order in maxQty Array..
			$sortOrderRequest =  Mage::app()->getRequest()->getParam('order');
			if(isset($sortOrderRequest)==null){
			   $collection->setOrder('updated_at', 'DESC');
			}

			// Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);

      /********************** QUERY TO DISPLAY OUT OF STOCK PRODUCTS TO THE END OF LIST IN CATEGORY PAGE ***********************/
      $collection->getSelect()->joinLeft(array('stock_status' => $collection->getTable('cataloginventory/stock_status')),'e.entity_id = stock_status.product_id AND stock_status.website_id='.Mage::app()->getWebsite()->getId());
      $collection->getSelect()->order('stock_status DESC');

      } elseif($this->getCurrentCategory()->getCollectionType() == 6){

			// 3SD CODE LATEST CATAGORY COLLECTION
			$todayStartOfDayDate  = Mage::app()->getLocale()->date()->setDate(date("m-d-Y", strtotime("-1 months")))->setTime('00:00:00')->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
			$collection = $category->getProductCollection();
			$collection->addAttributeToFilter('created_at', array('and'=> array(
			0 => array('date' => true, 'from' => $todayStartOfDayDate))
			), 'left')
			->addAttributeToSort('created_at', 'desc');

			//-- 3SD CODE GET CONFIGURABLE PRODUCTS
			$collection->getSelect()->joinLeft(array('parent_product' => $collection->getTable('catalog/product_super_link')),'e.entity_id = parent_product.product_id');
      Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
			$productIds = "";
			foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $row) {
				$productIds[]=$row['entity_id'];
				isset($row['parent_id'])&&($productIds[] = $row['parent_id']);
			}

			//-- 3SD CODE ATTACHED SIMPLE AND CONFIGURABLE PRODUCTS
			$collection = $category->getProductCollection();
			$collection->addAttributeToFilter('entity_id', array('in' => $productIds))->addAttributeToSort('created_at', 'desc');
			Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);

      } elseif($this->getCurrentCategory()->getCollectionType() == 7){

			// 3SD CODE FEATURED CATAGORY COLLECTION
			$sortOrderRequest =  Mage::app()->getRequest()->getParam('order');
			$collection = $category->getProductCollection()->addAttributeToFilter(array(array('attribute' => 'featured', 'eq' => '1')));

			//-- 3SD CODE GET CONFIGURABLE PRODUCTS
			$collection->getSelect()->joinLeft(array('parent_product' => $collection->getTable('catalog/product_super_link')),'e.entity_id = parent_product.product_id');
      // Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
			$productIds = "";
			foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $row) {
				$productIds[]=$row['entity_id'];
				isset($row['parent_id'])&&($productIds[] = $row['parent_id']);
			}

			//-- 3SD CODE ATTACHED SIMPLE AND CONFIGURABLE PRODUCTS
			$collection = $category->getProductCollection();
			$collection->addAttributeToFilter('entity_id', array('in' => $productIds));

			if(isset($sortOrderRequest)==null){
			   $collection->setOrder('updated_at', 'DESC');
			}

			// Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);

      /********************** QUERY TO DISPLAY OUT OF STOCK PRODUCTS TO THE END OF LIST IN CATEGORY PAGE ***********************/
      $collection->getSelect()->joinLeft(array('stock_status' => $collection->getTable('cataloginventory/stock_status')),'e.entity_id = stock_status.product_id AND stock_status.website_id='.Mage::app()->getWebsite()->getId());
      $collection->getSelect()->order('stock_status DESC');

      }

	  if($this->getCurrentCategory()->getCollectionType() != 2){
		$event =  Mage::app()->getRequest()->getParam('event');
		$matchEvent = preg_match('/national/i',$event);
		if($event && $matchEvent){
			$sourcingOptionIds = array();
			$sourcingAttribute = $collection->getAttribute('sourcing');
			foreach($sourcingAttribute->getSource()->getAllOptions() as $option){
				if($option['label']){
					if(preg_match('/^PK/i',$option['label'])){
						$sourcingOptionIds[] = $option['value'];
					}
				}
			}
			$collection->addAttributeToFilter(
			  array(
				array('attribute' => 'sourcing', 'IN' => $sourcingOptionIds)
			  )
			);
		}
	}

	//   $event =  Mage::app()->getRequest()->getParam('event');
	//   if($event && $event == "national"){
	// 	$sourcingOptionIds = array();
	// 	$sourcingAttribute = $collection->getAttribute('sourcing');
	// 	foreach($sourcingAttribute->getSource()->getAllOptions() as $option){
	// 		if($option['label']){
	// 			if(preg_match('/^PK/i',$option['label'])){
	// 				$sourcingOptionIds[] = $option['value'];
	// 			}
	// 		}
	// 	}
	// 	$collection->addAttributeToFilter(
	// 	  array(
	// 		array('attribute' => 'sourcing', 'IN' => $sourcingOptionIds)
	// 	  )
	// 	);
	// 	if($this->getCurrentCategory()->getCollectionType() == 2){
	// 		$productIds = "";
	// 		foreach ($collection->getConnection()->fetchAll($collection->getSelect()) as $row) {
	// 			$productIds[]=$row['entity_id'];
	// 			isset($row['parent_id'])&&($productIds[] = $row['parent_id']);
	// 		}
	// 		$this->_productIds = $productIds;
	// 	}
	//   }

      $this->prepareProductCollection($collection);
      $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
    }

  return $collection;
}


}
