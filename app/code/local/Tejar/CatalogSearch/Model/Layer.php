<?php
/**
 * Intenso Premium Theme
 *
 * @category    Itactica
 * @package     Itactica_LayeredNavigation
 * @copyright   Copyright (c) 2014 Itactica (http://www.itactica.com)
 * @license     http://getintenso.com/license
 * @author      3SD CODE Shariq Shahab <shariqshahab2@gmail.com>
 */
/*********======================= ZEE CODE =========================********/
class Tejar_CatalogSearch_Model_Layer extends Itactica_LayeredNavigation_Model_CatalogSearch_Layer
{

	/**
     * Catalog Product Flat is enabled cache per store
     *
     * @var array
     */
    protected $_flatEnabled                 = array();


	/**
     * Retrieve Catalog Product Flat Helper object
     *
     * @return Mage_Catalog_Helper_Product_Flat
     */
    public function getFlatHelper()
    {
        return Mage::helper('catalog/product_flat');
    }

    /**
     * Retrieve is flat enabled flag
     * Return always false if magento run admin
     *
     * @return bool
     */
    public function isEnabledFlat()
    {
        // Flat Data can be used only on frontend
        if (Mage::app()->getStore()->isAdmin()) {
            return false;
        }
        $storeId = $this->getStoreId();
        if (!isset($this->_flatEnabled[$storeId])) {
            $flatHelper = $this->getFlatHelper();
            $this->_flatEnabled[$storeId] = $flatHelper->isAvailable() && $flatHelper->isBuilt($storeId);
        }
        return $this->_flatEnabled[$storeId];
    }


	/**
	* Get current layer product collection
	*
	* @return Mage_Catalog_Model_Resource_Eav_Resource_Product_Collection
	*/
    public function getProductCollection()
    {

        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        } else {
            $collection = Mage::getResourceModel('catalog/product_collection');
            $this->prepareProductCollection($collection);
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }

        return $collection;
    }



   /**
	* Prepare product collection
	*
	* @param Mage_Catalog_Model_Resource_Eav_Resource_Product_Collection $collection
	* @return Mage_Catalog_Model_Layer
	*/
    public function prepareProductCollection($collection)
    {
        $collection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())->setStore(Mage::app()->getStore())->addMinimalPrice()->addFinalPrice()->addTaxPercents()->addStoreFilter()->addUrlRewrite();
		$arrayResult 		= $this->prepareSearchTerms();
		$searchParamsArray 	= $arrayResult[0];
		$orderBy 			= $arrayResult[1];
		$queryOrder   		= Mage::app()->getRequest()->getParam('order');
		$queryOrderDir 		= Mage::app()->getRequest()->getParam('dir');
		$query				= Mage::helper('catalogsearch')->getQuery()->getQueryText();
		$getQuery 		= $this->getExprHelper()->addLikeEscape($query);

		if($this->isEnabledFlat()){

			//--- 3SD CODE CATALOG PRODUCT FLAT COLLECTION
			$collection->getSelect()
			->columns(array('cat_index_position' => 'cat_index.position'))
			->joinLeft(array('stock_status' => $collection->getTable('cataloginventory/stock_status')),'e.entity_id = stock_status.product_id AND stock_status.website_id='.Mage::app()->getWebsite()->getId())
			->joinInner(array('cat_index' => $collection->getTable('catalog/category_product_index')),'cat_index.product_id = e.entity_id AND cat_index.store_id='. Mage::app()->getStore()->getId() .' AND cat_index.visibility IN(3, 4) AND cat_index.category_id = 2')
			->Where($searchParamsArray)
			->order(new Zend_Db_Expr("((model = ".$getQuery.") OR (name = ".$getQuery.")) OR  (stock_status = 1) DESC"));

		} else {

			//--- 3SD CODE CATALOG PRODUCT COLLECTION
			$_model = Mage::getModel('eav/config')->getAttribute('catalog_product', 'model');
			$_name = Mage::getModel('eav/config')->getAttribute('catalog_product', 'name');
			$collection->getSelect()
			->columns(array('model' => 'IF(at_model.value_id > 0, at_model.value, at_model_default.value)', 'name' => 'IF(at_name.value_id > 0, at_name.value, at_name_default.value)', 'query_text' => 'IF(at_name.value_id > 0, at_name.value, at_name_default.value)'))
			->joinLeft(array('at_model_default' => 'catalog_product_entity_varchar'),"(`at_model_default`.`entity_id` = `e`.`entity_id`) AND (`at_model_default`.`attribute_id` = '".$_model->getId()."') AND `at_model_default`.`store_id` = 0")
			->joinLeft(array('at_model' => 'catalog_product_entity_varchar'),"(`at_model`.`entity_id` = `e`.`entity_id`) AND (`at_model`.`attribute_id` = '".$_model->getId()."') AND `at_model`.`store_id` = ".Mage::app()->getStore()->getId()."")
			->joinInner(array('at_name_default' => 'catalog_product_entity_varchar'),"(`at_name_default`.`entity_id` = `e`.`entity_id`) AND (`at_name_default`.`attribute_id` = '".$_name->getId()."') AND `at_name_default`.`store_id` = 0")
			->joinLeft(array('at_name' => 'catalog_product_entity_varchar'),"(`at_name`.`entity_id` = `e`.`entity_id`) AND (`at_name`.`attribute_id` = '".$_name->getId()."') AND `at_name`.`store_id` = ".Mage::app()->getStore()->getId()."")
			->joinLeft(array('stock_status' => $collection->getTable('cataloginventory/stock_status')),'e.entity_id = stock_status.product_id AND stock_status.website_id='.Mage::app()->getWebsite()->getId())
			->joinInner(array('cat_index' => $collection->getTable('catalog/category_product_index')),'cat_index.product_id = e.entity_id AND cat_index.store_id='. Mage::app()->getStore()->getId() .' AND cat_index.visibility IN(3, 4) AND cat_index.category_id = 2')
			->Where($searchParamsArray)
			->order(new Zend_Db_Expr("((model = ".$getQuery.") OR (name = ".$getQuery.")) OR  (stock_status = 1) DESC"));
		}

		//--- 3SD CODE DISABLED CLAUSE ATM...
	$table = "";
	$tablePrefix = "";
		if($queryOrder == "relevance" || !isset($queryOrder)){
			$collection->getSelect()->order(new Zend_Db_Expr($orderBy));
		} else {
			if($queryOrder == "price"){
				$tablePrefix = 'price_index';
			} elseif($queryOrder == "name"){
				$tablePrefix = $table;
			} elseif($queryOrder == "length"){
				$tablePrefix = $table;
			} elseif($queryOrder == "position"){
				$tablePrefix = 'catalog_category_product';
			}
			$myURLSearchOrderClause = $tablePrefix.'.'.$queryOrder;
			$myURLSearchOrderClause .= " ".$queryOrderDir;
		}


		// echo $collection->getSelect(); die;



        return $this;
    }

    /**
     * Retrieve collection of suggest queries
     *
     * @return Mage_CatalogSearch_Model_Resource_Query_Collection
     */
    public function getCategoryCollection()
    {
		$collection = $this->getData('category_collection');
        if (is_null($collection)) {
			$arrayResult = $this->prepareCategorySearchTerms();
			$searchParams = $arrayResult[0];
			$orderBy = $arrayResult[1];
			    // If Flat Data enabled then use it but only on frontend
			$flatHelper = Mage::helper('catalog/category_flat');
			if ($flatHelper->isAvailable() && !Mage::app()->getStore()->isAdmin() && $flatHelper->isBuilt(true) && !$this->getDisableFlat()) {
				$metaTitle = $this->getMetaTitle(Mage::app()->getStore(), "/");
				$metaTitle = explode('/',$metaTitle);
				$collection = Mage::getResourceModel('catalog/category_flat_collection');
				$collection->getSelect(array('name'))
				->columns(array('query_text' => "IF((collection_type = 1 && level >= 3),meta_title,name)"))
				->Where("nullCondition",$searchParams)
				->Where("(`is_active` = 1) AND (((`collection_type` = 1)) OR ((`collection_type` IS NULL))) AND (`level` > 1)")
				->order(array(new Zend_Db_Expr($orderBy)));
			} else {
				$collection = Mage::getResourceModel('catalog/category_collection');
				$nameId = $collection->getAttribute('name')->getAttributeId();
				$isActiveId = $collection->getAttribute('is_active')->getAttributeId();
				$collection->getSelect(array('name'))
				->columns(array('name' => 'IF(at_name.value_id > 0, at_name.value, at_name_default.value)','is_active' => 'IF(at_is_active.value_id > 0, at_is_active.value, at_is_active_default.value)','query_text' => 'IF(at_name.value_id > 0, at_name.value, at_name_default.value)'))
				->joinInner(array('at_is_active_default' => 'catalog_category_entity_int'),"( `at_is_active_default`.`entity_id` = `e`.`entity_id` ) AND ( `at_is_active_default`.`attribute_id` = '".$isActiveId."' ) AND `at_is_active_default`.`store_id` = 0")
				->joinLeft(array('at_is_active' => 'catalog_category_entity_int'),"( `at_is_active`.`entity_id` = `e`.`entity_id` ) AND ( `at_is_active`.`attribute_id` = '".$isActiveId."' ) AND ( `at_is_active`.`store_id` = ". Mage::app()->getStore()->getId() ." )")
				->joinInner(array('at_name_default' => 'catalog_category_entity_varchar'),"( `at_name_default`.`entity_id` = `e`.`entity_id` ) AND ( `at_name_default`.`attribute_id` = '".$nameId."' ) AND `at_name_default`.`store_id` = 0")
				->joinLeft(array('at_name' => 'catalog_category_entity_varchar'),"( `at_name`.`entity_id` = `e`.`entity_id` ) AND ( `at_name`.`attribute_id` = '".$nameId."' ) AND ( `at_name`.`store_id` = ". Mage::app()->getStore()->getId() ." )")
				->Where("nullCondition",$searchParams)
				->order(new Zend_Db_Expr($orderBy));
			}
			$this->setData('category_collection', $collection);
		}
		return $collection;
	}
	/*
	*@name        getMetaTitle
	*@parameters  $countryCode, $productTitle
	*@description to obtain Meta Title of a given product
	*@returns     String
	*/
	public function getMetaTitle($store, $productTitle){
		$storeId = $store->getId();
		$countryCode = Mage::getStoreConfig('general/country/default', $storeId);
		$country = Mage::getModel('directory/country')->loadByCode($countryCode);
		$countryName = $country->getName();
		$meta_title = Mage::getStoreConfig('design/product_meta/category_meta_title', $storeId);
		if($meta_title){
			$meta_title = str_replace("{{title}}",$productTitle,$meta_title);
			$meta_title = str_replace("{{country}}",$countryName,$meta_title);
			return $meta_title;
		}
		return;
	}

  public function prepareCategorySearchTerms()
	{
		$params  		= "";
  		$getQueryText	= Mage::helper('catalogsearch')->getQuery()->getQueryText();
  		$getQuery 		= $this->getExprHelper()->addLikeEscape($getQueryText);
  		$getQueryExp	= explode(' ', $getQueryText);
  		$getQueryArray	= array();
		$list 			= array();
  		foreach($getQueryExp as $query){
  			$getQueryArray[] = $this->getExprHelper()->addLikeEscape($query);
  		}
		$metaTitle = $this->getMetaTitle(Mage::app()->getStore(), "/");
		$metaTitle = explode('/',$metaTitle);
  		// $getQueryExp	= $getQueryArray;
		$length			=	count($getQueryExp);
		$_name			= "meta_title";
		$cases			= "CASE ";
		$count			= 1;
		$set 			=	$getQueryExp;
		$size 			= 	$length - 1;
		$perm 			= 	range(0, $size);
		$j				= 	0;
		$flatHelper = Mage::helper('catalog/category_flat');
		if ($flatHelper->isAvailable() && !Mage::app()->getStore()->isAdmin() && $flatHelper->isBuilt(true) && !$this->getDisableFlat()) {
			$_name			= "IF((collection_type = 1 && level >= 3),meta_title,name)";
			$_is_active		= "is_active";
		} else {
			$_name			= "IF(at_name.value_id > 0, at_name.value, at_name_default.value)";
			$_is_active		= "IF(at_is_active.value_id > 0, at_is_active.value, at_is_active_default.value)";
		}
  		if($length == 1){
  			$params .= "(";
  				$params .= "(" . $_name . " LIKE " . $getQuery . ")";
  					$params .= " OR ";
  				$params .= "(" . $_name . " LIKE CONCAT(". $getQuery . ",' %'))";
  					$params .= " OR ";
  				$params .= "(" . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %'))";
  					$params .= " OR ";
  				$params .= "(" . $_name . " LIKE CONCAT('% '," . $getQuery . "))";
  					$params .= " OR ";
  				$params .= "(" . $_name . " LIKE CONCAT(" . $getQuery . ",'%'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('% '," . $getQuery . ",'%'))";
					$params .= " OR ";
  				$params .= "(" . $_name . " LIKE CONCAT('%'," . $getQuery . ",' %'))";
  			$params .= ")";
  			$cases .= "WHEN " . $_name . " LIKE " . $getQuery . " THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",' %') AND collection_type IS NULL THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %') AND collection_type IS NULL THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ") AND collection_type IS NULL THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",'%') AND collection_type IS NULL THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",' %') AND collection_type = 1 AND level = 2 THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %') AND collection_type = 1 AND level = 2 THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ") AND collection_type = 1 AND level = 2 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",'%') AND collection_type = 1 AND level = 2 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",' %') AND collection_type = 1 AND level = 3 THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %') AND collection_type = 1 AND level = 3 THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ") AND collection_type = 1 AND level = 3 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",'%') AND collection_type = 1 AND level = 3 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",' %') THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %') THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ") THEN " . $count++ . " ";
  			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",'%') THEN " . $count++ . " ";
  		}
		if($length == 2){
			$params .= "(";
				$params .= "(" . $_name . " LIKE " . $getQuery . ")";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(". $getQuery . ",' %'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('% '," . $getQuery . "))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(" . join(",' % ',", $getQueryArray) . "))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(" . $getQuery . ",'%'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(" . join(",' % ',", $getQueryArray) . ",'%'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('% '," . $getQuery . ",'%'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('%'," . $getQuery . ",' %'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('%'," . $getQuery . ",'%'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(" . $getQueryArray[1] . ",'_'," . $getQueryArray[0] . "))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(". $getQueryArray[1] . ",'_'," . $getQueryArray[0] . ",' %'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('% '," . $getQueryArray[1] . ",'_'," . $getQueryArray[0]  . ",' %'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('% '," . $getQueryArray[1] . ",'_'," . $getQueryArray[0] . "))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(" . $getQueryArray[1] . ",' % '," . $getQueryArray[0] . "))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(" . $getQueryArray[1] . ",'_'," . $getQueryArray[0] . ",'%'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(" . $getQueryArray[1] . ",' % '," . $getQueryArray[0] . ",'%'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT(" . $getQueryArray[1] . ",'% '," . $getQueryArray[0] . ",'%'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('% '," . $getQueryArray[1] . ",'%'," . $getQueryArray[0] . ",'%'))";
					$params .= " OR ";
				$params .= "(" . $_name . " LIKE CONCAT('%'," . $getQueryArray[1] . ",'%'," . $getQueryArray[0] . ",'%'))";
			$params .= ")";
			$cases .= "WHEN " . $_name . " LIKE " . $getQuery . " THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",' %') AND collection_type IS NULL THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %') AND collection_type IS NULL THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ") AND collection_type IS NULL THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",'%') AND collection_type IS NULL THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",' %') AND collection_type = 1 AND level = 2 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %') AND collection_type = 1 AND level = 2 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ") AND collection_type = 1 AND level = 2 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",'%') AND collection_type = 1 AND level = 2 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",' %') AND collection_type = 1 AND level = 3 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %') AND collection_type = 1 AND level = 3 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ") AND collection_type = 1 AND level = 3 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",'%') AND collection_type = 1 AND level = 3 THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",' %') THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ",' %') THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT('% '," . $getQuery . ") THEN " . $count++ . " ";
			$cases .= "WHEN " . $_name . " LIKE CONCAT(" . $getQuery . ",'%') THEN " . $count++ . " ";
		}
		if($length > 2){
			$params .= "(";
			$list = array();
			foreach($getQueryArray as $query){
				$condition = "";
				$condition .= "(";
					$condition .= "(" . $_name." LIKE CONCAT(". $query . ",' %')) OR ";
					$condition .= "(" . $_name." LIKE CONCAT('% ',". $query . ",' %')) OR ";
					$condition .= "(" . $_name." LIKE CONCAT('% ',". $query . ")) OR ";
					$condition .= "(" . $_name." LIKE CONCAT(". $query . ",'%')) OR ";
					$condition .= "(" . $_name." LIKE CONCAT('% ',". $query . ",'%')) OR ";
					$condition .= "(" . $_name." LIKE CONCAT('%',". $query . ",' %'))  ";
				$condition .= ")";
				$list[] = $condition;
			}
			$params .= '('.join(') AND (', $list).')';
			$cases .= "WHEN " . $_name . " LIKE " . $getQuery . " THEN " . $count++ . " ";
			 $params .= ")";
		}
		$cases .= " ELSE ".$count." END, position ASC";
		return array($params, $cases);
	}


	/**
     * Retrieve Core addLikeEscape
     *
     * @return Mage_Core_Model_Resource_Helper_Mysql4
	*/
    public function getExprHelper()
    {
        return Mage::getResourceHelper('core');
    }

	/**
	* Prepare product condition and cases
	*
	* @param Mage_Catalog_Model_Resource_Eav_Resource_Product_Collection $collection
	* @return Mage_Catalog_Model_Layer
	*/
	public function prepareSearchTerms()
    {


		$params  		= "";
		$getQueryText	= Mage::helper('catalogsearch')->getQuery()->getQueryText();
		$getQuery 		= $this->getExprHelper()->addLikeEscape($getQueryText);
		$getQueryExp	= explode(' ', $getQueryText);
		$getQueryArray	= array();
		foreach($getQueryExp as $query){
			$getQueryArray[] 		= $this->getExprHelper()->addLikeEscape($query);
		}
		$length			= count($getQueryExp);
		$parentIds 		= "";
		$cases			= "";
		$count  		= 	1;
		$name			= "name";
		$set 			=	$getQueryExp;
		$size 			= 	$length - 1;
		$perm 			= 	range(0, $size);
		$j				= 	0;
		$queryOrder   	= Mage::app()->getRequest()->getParam('order');
		$queryOrderDir 	= Mage::app()->getRequest()->getParam('dir');

		if($this->isEnabledFlat()){
			$parentQuery = "SELECT catalog_product_super_link.parent_id FROM catalog_product_flat_".Mage::app()->getStore()->getId()." AS `e` ";
			$parentQuery .= "INNER JOIN `catalog_product_super_link` AS `catalog_product_super_link` ON e.entity_id = catalog_product_super_link.product_id ";
			$parentQuery .= "INNER JOIN `catalog_category_product_index` AS `cat_index` ON cat_index.product_id = e.entity_id AND cat_index.store_id=".Mage::app()->getStore()->getId()." AND cat_index.visibility = 1 AND cat_index.category_id = 2 ";
			$parentQuery .= "WHERE (e.sku = ".$getQuery.") OR (e.model = ".$getQuery.") OR (e.model LIKE ".$getQuery.")";
		} else {
			$_modelAttribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'model');
			$parentQuery = "SELECT catalog_product_super_link.parent_id FROM catalog_product_entity AS `e` ";
			$parentQuery .= "INNER JOIN `catalog_product_super_link` AS `catalog_product_super_link` ON e.entity_id = catalog_product_super_link.product_id ";
			$parentQuery .= "INNER JOIN `catalog_product_entity_varchar` AS `at_model_default` ON (`at_model_default`.`entity_id` = `e`.`entity_id`) AND (`at_model_default`.`attribute_id` = '".$_modelAttribute->getId()."') AND `at_model_default`.`store_id` = 0 ";
			$parentQuery .= "INNER JOIN `catalog_category_product_index` AS `cat_index` ON cat_index.product_id = e.entity_id AND cat_index.store_id=".Mage::app()->getStore()->getId()." AND cat_index.visibility = 1 AND cat_index.category_id = 2 ";
			$parentQuery .= "LEFT JOIN `catalog_product_entity_varchar` AS `at_model` ON (`at_model`.`entity_id` = `e`.`entity_id`) AND (`at_model`.`attribute_id` = '".$_modelAttribute->getId()."') AND `at_model`.`store_id` = " . Mage::app()->getStore()->getId() . " ";
			$parentQuery .= "WHERE (e.sku = ".$getQuery.") OR (IF(at_model.value_id > 0, at_model.value, at_model_default.value) = ".$getQuery.") OR (IF(at_model.value_id > 0, at_model.value, at_model_default.value) LIKE ".$getQuery.") ";
		}



		if($this->isEnabledFlat()){
			$_nameCond = "";
			$_name = "name";
			$_modelCond = "";
			$_model = "model";
		} else {
			$_nameCond = "IF(at_name.value_id > 0, at_name.value, at_name_default.value) = 'name')";
			$_name = "IF(at_name.value_id > 0, at_name.value, at_name_default.value)";
			$_modelCond = "(IF(at_model.value_id > 0, at_model.value, at_model_default.value) = 'model')";
			$_model = "IF(at_model.value_id > 0, at_model.value, at_model_default.value)";
		}

		$cases .= "CASE ";

		if($length > 1){

			//-- 3SD CODE ALL WORDS EQUAL
			$cases  .= "WHEN ".$_model." = ".$getQuery."  THEN " . $count++ . " ";
			$cases  .= "WHEN ".$_model." LIKE ".$getQuery."  THEN " . $count++ . " ";
			$cases  .= "WHEN ".$_name." = ".$getQuery."  THEN " . $count++ . " ";
			$cases  .= "WHEN ".$_name." LIKE ".$getQuery."  THEN " . $count++ . " ";


			if($length <= 5){
				// $perms = array();
				//-- 3SD GET ALL PERMUTATIONS OF QUERY ARRAY
				do {
					 foreach ($perm as $i) { $perms[$j][] = $set[$i]; }
				} while ($perm = $this->getPermutation($perm, $size) and ++$j);

				foreach ($perms as $p) {
						$joinName = $this->getExprHelper()->addLikeEscape(join(' ', $p));
						$cases  .= "WHEN ".$_name." = ".$joinName."  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT(".$joinName.")  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT(".$joinName.",' %')  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".$joinName.",' %')  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".$joinName.")  THEN " . $count++ . " ";



						// echo $this->getExprHelper()->addLikeEscape(join("','", $p)); die;

						// $joinName = join(' ', $p);
						//-- 3SD JOIN % BETWEEN WORD
						$rp = array();
						foreach($p as $pp){
							$rp[] = $this->getExprHelper()->addLikeEscape($pp);
						}


						// $arr = array('Hello','World!','Beautiful','Day!');
						// echo implode(" ",$p); die;


						// $p = array(apple,macbook);
						// $e = 'apple macbook'

						// join(

						// $this->getExprHelper()->addLikeEscape(

						$cases  .= "WHEN ".$_name." LIKE CONCAT(".join(",' % ',", $rp).")  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT(".join(",' % ',", $rp).",' %')  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".join(",' % ',", $rp).",' %')  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".join(",' % ',", $rp).")  THEN " . $count++ . " ";

						//-- 3SD MARGE WORD CHARACTOR
						$cases  .= "WHEN ".$_name." LIKE CONCAT(".$joinName.",'%')  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT(".join(",'% ',", $rp).",'%')  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT(".join(",' %',", $rp).")  THEN " . $count++ . " ";
						$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".join(",'%',", $rp).")  THEN " . $count++ . " ";
					}


			} else {

				//-- 3SD CODE WORDS
				$cases  .= "WHEN ".$_name." LIKE CONCAT(".$getQuery.")  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT(".$getQuery.",' %')  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".$getQuery.",' %')  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".$getQuery.")  THEN " . $count++ . " ";

				//-- 3SD CODE CHARACTORS
				$cases  .= "WHEN ".$_name." LIKE CONCAT(".$getQuery.",'%')  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".$getQuery.",'%')  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT('%',".$getQuery.",' %')  THEN " . $count++ . " ";

				//-- 3SD CODE WORDS JOIN CASES
				$cases  .= "WHEN ".$_name." LIKE CONCAT(".join(",' % ',", $getQueryArray).",' %')  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".join(",' % ',", $getQueryArray).",' %')  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".join(",' % ',", $getQueryArray).")  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".join(",' % ',", $getQueryArray).")  THEN " . $count++ . " ";

				//-- 3SD CODE CHARACTORS JOIN CASES
				$cases  .= "WHEN ".$_name." LIKE CONCAT(".join(",'%',", $getQueryArray).",'%')  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT('%',".join(",'%',", $getQueryArray).",' %')  THEN " . $count++ . " ";
				$cases  .= "WHEN ".$_name." LIKE CONCAT('%',".join(",'%',", $getQueryArray).")  THEN " . $count++ . " ";



			}



			$list = array();
			foreach($getQueryArray as $query){
				// $query = $this->getExprHelper()->addLikeEscape($query);
				$condition = "";
				$condition .= "(";
					$condition .= "(" . $_name." LIKE CONCAT(". $query . ")) OR ";
					$condition .= "(" . $_name." LIKE CONCAT(". $query . ",' %')) OR ";
					$condition .= "(" . $_name." LIKE CONCAT('% ',". $query . ",' %')) OR ";
					$condition .= "(" . $_name." LIKE CONCAT('% ',". $query . ")) ";
					// $condition .= "(" . $_name." LIKE '". $query . "%') OR ";
					// $condition .= "(" . $_name." LIKE '%". $query . "%') OR ";
					// $condition .= "(" . $_name." LIKE '%". $query . "') ";
				$condition .= ")";
				$list[] = $condition;
			}


			$result = array();
			foreach ($this->pc_array_power_set($getQueryExp) as $combination) {
				if ($length-1 == count($combination)) {
					$result[] = $combination;
				}
			}

			$params  .= "(";
				$params .= "(";
					$params .= "(";
						$params .= "((e.sku != " . $getQuery . ") OR (".$_model." != " . $getQuery . "))";
						$params .= " AND ";
							$params .= "(";
								$params .= "(";
									$params .= '('.join(') AND (', $list).')';
									foreach ($result as $r) {
										$array = array();
										$array_c = array();
											$params .= ") OR (";
											foreach ($r as $t) {
													$t = $this->getExprHelper()->addLikeEscape($t);
													$conditions = "";
													$conditions .= "(";
														$conditions .= "(" . $_name." LIKE CONCAT(". $t . ")) OR ";
														$conditions .= "(" . $_name." LIKE CONCAT(". $t . ",' %')) OR ";
														$conditions .= "(" . $_name." LIKE CONCAT('% ',". $t . ",' %')) OR ";
														$conditions .= "(" . $_name." LIKE CONCAT('% ',". $t . ")) ";
													$conditions .= ")";
													$array[] = $conditions;
											}
										$params .= '('.join(') AND (', $array).')';
										$joinName = $this->getExprHelper()->addLikeEscape(join(' ', $r));
										$cases .= "WHEN " .  $_name . " LIKE CONCAT(" . $joinName . ",' %') THEN " . $count++ . " ";
										$cases .= "WHEN " .  $_name . " LIKE CONCAT('% '," . $joinName . ",' %') THEN " . $count++ . " ";
										// implode("," $r)
										// $joinName = $this->getExprHelper()->addLikeEscape(join(' ', $r));
										$rp = array();
										foreach($r as $pp){
											$rp[] = $this->getExprHelper()->addLikeEscape($pp);
										}
										$cases .= "WHEN " .  $_name . " LIKE CONCAT(" . join(",' % ',", $rp) . ") THEN " . $count++ . " ";
										$cases .= "WHEN " .  $_name . " LIKE CONCAT(" . join(",' % ',", $rp) . ",' %') THEN " . $count++ . " ";
										$cases .= "WHEN ".   $_name . " LIKE CONCAT('% ',".join(",' % ',", $rp) . ",' %')  THEN " . $count++ . " ";
										$cases .= "WHEN ".   $_name . " LIKE CONCAT('% ',".join(",' % ',", $rp) . ")  THEN " . $count++ . " ";
									}
									$params .= ")";
							$params .= ")";
						$params .= ")";
				$params .= ")";
				$params .= " OR ";
					$params .= "(";
						$params .= "((((e.sku = 'sku') OR (e.sku = " . $getQuery . ")) OR ((".$_model." = " . $getQuery . ") OR (".$_model." LIKE " . $getQuery . "))) OR (e.entity_id IN(".$parentQuery.")))";
					$params .= ")";
			$params .= ")";



		}

		if($length == 1){

			//-- 3SD  SINGAL WORD CONDITIONS
			$params  .= "(";
				$params .= "(";
					$params .= "((e.sku != " . $getQuery . ") OR (".$_model." != " . $getQuery . ") OR (".$_model." NOT LIKE " . $getQuery . "))";
					$params .= " AND ";
						$params .= " ( ";
							$params .= "(";
								$params .= "(" . $_name." = ". $getQuery . ") OR ";
								$params .= "(" . $_name." LIKE ". $getQuery . ") OR ";
								$params .= "(" . $_name." LIKE CONCAT(". $getQuery . ",' %')) OR ";
								$params .= "(" . $_name." LIKE CONCAT('% ',". $getQuery . ",' %')) OR ";
								$params .= "(" . $_name." LIKE CONCAT('% ',". $getQuery . "))";
							$params .= ")";
						$params .= ")";
				$params .= ")";
				$params .= " OR ";
				$params .= "(";
					$params .= "((((e.sku = 'sku') OR (e.sku = " . $getQuery . ")) OR ((".$_model." = " . $getQuery . ") OR (".$_model." LIKE " . $getQuery . "))) OR (e.entity_id IN(".$parentQuery.")))";
				$params .= ")";
			$params .= ")";

			//-- 3SD  SINGAL WORD CASES
			$cases  .= "WHEN ".$_name." = ".$getQuery."  THEN " . $count++ . " ";
			$cases  .= "WHEN ".$_name." LIKE ".$getQuery."  THEN " . $count++ . " ";
			$cases  .= "WHEN ".$_name." LIKE CONCAT(".$getQuery.",' %')  THEN " . $count++ . " ";
			$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".$getQuery.",' %')  THEN " . $count++ . " ";
			$cases  .= "WHEN ".$_name." LIKE CONCAT('% ',".$getQuery.")  THEN " . $count++ . " ";
		}

		$cases .= " ELSE ".$count." END, ".$_name;


		// var_dump($cases); die;

		return array($params , $cases);
	}


	protected function getPermutation($p, $size) {
		//-- 3SD CODE slide down the array looking for where we're smaller than the next guy
    $p[-1] = "";
    for ($i = $size - 1; $p[$i] >= $p[$i+1]; --$i) { }
    unset($p[-1]);

		//-- 3SD CODE if this doesn't occur, we've finished our permutations
		//-- 3SD CODE the array is reversed: (1, 2, 3, 4) => (4, 3, 2, 1)
		if ($i == -1) { return false; }

		//-- 3SD CODE slide down the array looking for a bigger number than what we found before
		for ($j = $size; $p[$j] <= $p[$i]; --$j) { }

		//-- 3SD CODE swap them
		$tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;

		//-- 3SD CODE now reverse the elements in between by swapping the ends
		for (++$i, $j = $size; $i < $j; ++$i, --$j) {
			 $tmp = $p[$i]; $p[$i] = $p[$j]; $p[$j] = $tmp;
		}

		return $p;
	}


	protected function pc_array_power_set($array) {
		//-- 3SD CODE INITIALIZE BY ADDING THE EMPTY SET
		$results = array(array( ));
		foreach ($array as $element)
			foreach ($results as $combination)
				array_push($results, array_merge(array($element), $combination));

		return $results;
	}
}
