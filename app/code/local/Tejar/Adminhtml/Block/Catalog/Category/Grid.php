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
 * @package     Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml customer grid block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Adminhtml_Block_Catalog_Category_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('categoriesGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('category_filter');

    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

    protected function _prepareCollection()
    {
		
		
		
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('name')
			->addAttributeToSelect('children_count')
            ->addAttributeToSelect('collection_type')
            ->addAttributeToSelect('is_active');
			// ->setLoadProductCount(true);
			
		
        if ($store->getId()) {
            //$collection->setStoreId($store->getId());
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            $collection->joinAttribute(
                'name',
                'catalog_category/name',
                'entity_id',
                null,
                'inner',
                $adminStore
            );
			
            $collection->joinAttribute(
                'custom_name',
                'catalog_category/name',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
			
			
			$collection->joinAttribute(
                'collection_type',
                'catalog_category/collection_type',
                'entity_id',
                null,
                'left',
                $store->getId()
            );

			$collection->joinAttribute(
                'include_in_menu',
                'catalog_category/include_in_menu',
                'entity_id',
                null,
                'left',
                $store->getId()
            );

			$collection->joinAttribute(
                'is_anchor',
                'catalog_category/is_anchor',
                'entity_id',
                null,
                'left',
                $store->getId()
            );		
			$collection->joinAttribute(
                'landing_page',
                'catalog_category/landing_page',
                'entity_id',
                null,
                'left',
                $store->getId()
            );				
			$collection->joinAttribute(
                'default_sort_by',
                'catalog_category/default_sort_by',
                'entity_id',
                null,
                'left',
                $store->getId()
            );		
			$collection->joinAttribute(
                'display_mode',
                'catalog_category/display_mode',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
			
			
		
        }
        else {
            
			$collection->joinAttribute('name', 'catalog_category/name', 'entity_id', null, 'inner');
			$collection->joinAttribute('collection_type', 'catalog_category/collection_type', 'entity_id', null, 'inner');
			$collection->joinAttribute('include_in_menu', 'catalog_category/include_in_menu', 'entity_id', null, 'inner');
			$collection->joinAttribute('landing_page', 'catalog_category/landing_page', 'entity_id', null, 'inner');
			$collection->joinAttribute('default_sort_by', 'catalog_category/default_sort_by', 'entity_id', null, 'left');
			$collection->joinAttribute('is_anchor', 'catalog_category/is_anchor', 'entity_id', null, 'inner');
			$collection->joinAttribute('display_mode', 'catalog_category/display_mode', 'entity_id', null, 'inner');
			
        }

		$collection->getSelect()->columns(array("collection_type_value" => "(IF(at_collection_type.value,at_collection_type.value,10))"));
		
		$collection->getSelect()->columns(array("collection_type_value" => "(IF(at_collection_type.value,at_collection_type.value,10))","product_count" => new Zend_Db_Expr("(SELECT count(p_c.product_id) FROM {$collection->getTable('catalog/category_product')} as p_c WHERE p_c.category_id = e.entity_id)")));		

        $this->setCollection($collection);

        parent::_prepareCollection();
        return $this;
    }

    protected function _addColumnFilterToCollection($column)
    {
        return parent::_addColumnFilterToCollection($column);
    }

    protected function _prepareColumns()
    {
		
		$store = $this->_getStore();
		
        $this->addColumn('entity_id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'entity_id',
        ));
        
		$this->addColumn('name',
            array(
				'header'=> Mage::helper('catalog')->__('Name'),
                'index' => 'name',
			)
		);
		
		
		if($store->getId()){
			$this->addColumn('custom_name',
				array(
					'header'=> Mage::helper('catalog')->__('Name in %s', $store->getName()),
					'index' => 'custom_name',
				)
			);
		}
		
		
		$customFilterArray = array();
		$customFilterArray["category_have_disabled_product"] = "Category with Disabled Products";
		$customFilterArray["category_have_no_product"] = "Empty Category";
		$customFilterArray["check_unchecked_fields"] = "Unchecked Attributes Store";
		$this->addColumn('custom_filter',
            array(
                'header'=> Mage::helper('catalog')->__('Filter'),
                'width' => '150px',
                'type'  => 'options',
                'options' => $customFilterArray,
				'filter_condition_callback' => array($this, '_CustomFilterCallback')
        ));
		
		
		$this->addColumn('level',
            array(
                'header'=> Mage::helper('catalog')->__('Level'),
                'index' => 'level',
				'width' => '100px',
        ));

		$this->addColumn('children_count',
		array(
			'header'=> Mage::helper('catalog')->__('Children count'),
			'width' => '70px',
			'index' => 'children_count',
			'type'  => 'number'
	));
		
	$this->addColumn('product_count',
	array(
		'header'=> Mage::helper('catalog')->__('Product count'),
		'width' => '70px',
		'index' => 'product_count',
		'type'  => 'number',
		'filter_condition_callback' => array($this, '_CategoryProductCallback')
));
		
		$this->addColumn('is_active',
            array(
                'header'=> Mage::helper('catalog')->__('Status'),
                'width' => '70px',
                'index' => 'is_active',
                'type'  => 'options',
                'options' => array(0 => "Inactive",1 => "Active"),
        ));		
				
		$this->addColumn('include_in_menu',
            array(
                'header'=> Mage::helper('catalog')->__('Include in Navigation'),
                'width' => '70px',
                'index' => 'include_in_menu',
                'type'  => 'options',
                'options' => array(0 => "No",1 => "Yes"),
        ));
		
		$this->addColumn('collection_type',
		array(
			'header'=> Mage::helper('catalog')->__('Collection type'),
			'width' => '70px',
			'index' => 'collection_type_value',
			'type'  => 'options',
			'options' => array(10 => "Category", 1 => "Brand",2 => "Deals",3 => "Best Seller",4 => "Most Viewed",5 => "New Arrival", 6 => "Latest", 7 => "Featured"),
			'filter_condition_callback' => array($this, '_CategoryCallback')
		));

		$this->addColumn('display_mode',
		array(
			'header'=> Mage::helper('catalog')->__('Display Mode'),
			'width' => '150px',
			'index' => 'display_mode',
			'type'  => 'options',
			'options' => array("PRODUCTS" => "Products only","PAGE" => "Static block only","PRODUCTS_AND_PAGE" => "Static block and products"),
	));	

	$landingPageOption = array();
	$landingPageOptions = Mage::getResourceModel('cms/block_collection')->load()->toOptionArray();
	array_unshift($this->_options, array('value'=>'', 'label'=>Mage::helper('catalog')->__('Please select a static block ...')));
	foreach($landingPageOptions as $option){
		$landingPageOption[$option['value']] = $option['label'];
	}
	$this->addColumn('landing_page',
		array(
			'header'=> Mage::helper('catalog')->__('CMS Block'),
			'width' => '150px',
			'index' => 'landing_page',
			'type'  => 'options',
			'options' => $landingPageOption,
	));	

		$this->addColumn('is_anchor',
		array(
			'header'=> Mage::helper('catalog')->__('Is Anchor'),
			'width' => '70px',
			'index' => 'is_anchor',
			'type'  => 'options',
			'options' => array(0 => "No",1 => "Yes"),
	));	

	$config = Mage::getSingleton('catalog/config');
	$default_sort_by = array(
		'position' => Mage::helper('catalog')->__('Best Value'),
	);

	foreach ($config->getAttributesUsedForSortBy() as $attribute) {
		$default_sort_by[$attribute['attribute_code']] = Mage::helper('catalog')->__($attribute['frontend_label']);
	}
	$this->addColumn('default_sort_by',
		array(
			'header'=> Mage::helper('catalog')->__('Default Product Listing Sort By'),
			'width' => '70px',
			'index' => 'default_sort_by',
			'type'  => 'options',
			'options' => $default_sort_by,
	));			
		
		
		$this->addColumn('updated_at',
			array(
					'type'      => 'datetime',
					'filter_time' => true,
					'gmtoffset' => true,
					'header'=> Mage::helper('catalog')->__('Updated at'),
					'width' => '100px',
					'index' => 'updated_at',
			)
		);
		
		
		$this->addColumn('created_at',
			array(
					'type'      => 'datetime',
					'filter_time' => true,
					'gmtoffset' => true,
					'header'=> Mage::helper('catalog')->__('Created at'),
					'width' => '100px',
					'index' => 'updated_at',
			)
		);


        return parent::_prepareColumns();
    }

	protected function _CategoryProductCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		$innerQuery = new Zend_Db_Expr("(SELECT count(p_c.product_id) FROM {$collection->getTable('catalog/category_product')} as p_c WHERE p_c.category_id = e.entity_id)");
		$from = $value['from'];
		$to = $value['to'];
		$collection->getSelect()->where("{$innerQuery} > {$from}")->where("{$innerQuery} < {$to}"); 
	}		

	protected function _CategoryCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		if($value == 10) {
			$collection->addAttributeToFilter(
				array(
					array('attribute' => 'collection_type', 'is' =>new Zend_Db_Expr('null'))
				)
			);
		} else {
			$collection->addAttributeToFilter(
				array(
					array('attribute' => 'collection_type', 'eq' => new Zend_Db_Expr($value))
				)
			);
		}
	}
	
	protected function _CustomFilterCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		
		
		
		$store = $this->_getStore();
		$adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
		$storeId = $store->getId();
		if($value == "category_have_no_product") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$mainTable = $this->getCollection()->getTable('catalog/category');
			$secondTable = $this->getCollection()->getTable('catalog/category_product');
			$select = $readAdapter->select()->from(array('e' => $mainTable), array('entity_id'))
				->joinLeft(array('at_category_product' => $secondTable),"(`e`.`entity_id` = `at_category_product`.`category_id` )", array())
				->group('e.entity_id')
				->having('count(at_category_product.product_id) = 0');
			$categoryIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				$categoryIds[] = $row['entity_id'];
			}
			if(!empty($categoryIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$categoryIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		} else if($value == "category_have_disabled_product") {
				$readAdapter = $this->getCollection()->getConnection('core_read');
				$mainTable = $this->getCollection()->getTable('catalog/category');
				$secondTable = $this->getCollection()->getTable('catalog/category_product');
				$_productStatusAttribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'status');
				$statusAttributeId = $_productStatusAttribute->getAttributeId();
				$statusAttributeTable = $_productStatusAttribute->getBackend()->getTable();
				$select = $readAdapter->select()->from(array('e' => $mainTable), array('entity_id'))
				->joinLeft(array('at_category_product' => $secondTable),"(`e`.`entity_id` = `at_category_product`.`category_id` )", array())
				->joinInner(array('at_status_default' => $statusAttributeTable),"(`at_status_default`.`entity_id` = `at_category_product`.`product_id`) AND (`at_status_default`.`attribute_id` = {$statusAttributeId}) AND `at_status_default`.`store_id` = {$adminStore}",array())
				->joinLeft(array('at_status' => $statusAttributeTable),"(`at_status`.`entity_id` = `at_category_product`.`product_id`) AND (`at_status`.`attribute_id` = {$statusAttributeId}) AND `at_status`.`store_id` = {$storeId}", array())
				->group('e.entity_id')
				->having('count(at_category_product.product_id) > 0 AND SUM(IF(IF(at_status.value_id > 0, at_status.value, at_status_default.value) = 2,0,1)) = 0');
				$categoryIds = array();
				foreach ($readAdapter->fetchAll($select) as $row) {
					$categoryIds[] = $row['entity_id'];
				}
				if(!empty($categoryIds)){
					$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$categoryIds) ."))");
				} else {
					$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
				}
		} else if($value == "check_unchecked_fields") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$excludeAttribute = array();
			// $excludeAttribute = array("meta_title","meta_keywords","meta_description","custom_thumbnail","thumbnail","image","url_key","url_path");
			$excludeAttribute = array("description","is_packery","custom_thumbnail","thumbnail","image","url_key","is_active","url_path","intenso_menu_bottom_block","intenso_menu_top_block","intenso_menu_right_block","intenso_menu_right_block_width","intenso_menu_columns_medium","intenso_menu_columns_large","intenso_menu_style");
			$attributes = Mage::getSingleton('eav/config')
			->getEntityType(Mage_Catalog_Model_Category::ENTITY)
					->getAttributeCollection()
					->addSetInfo();
			$attributes->getSelect()->where('attribute_code NOT IN(?)',$excludeAttribute);
			$storeAttributes    = array();
			$selectAttributes 	= array();
			$websiteAttributes  = array();
			$store = $this->_getStore();
			$storeId = $store->getStoreId();
			if($storeId){
				$model = Mage::getModel('catalog/category');
				$entityIdField      = $model->getResource()->getEntityIdField();
				foreach($attributes as $attribute){
					$selectAttributes[$attribute->getBackend()->getTable()][] = array(
						'attribute_id'  => $attribute->getAttributeId(),
						'value_id'      => $attribute->getBackend()->getEntityValueId($product)
					);
					
					if ($attribute->isScopeStore()) {
						$websiteAttributes[$attribute->getBackend()->getTable()][] = (int)$attribute->getAttributeId();
						$storeAttributes[$attribute->getAttributeId()] = $attribute->getAttributeCode();
					} elseif ($attribute->isScopeWebsite()) {
						$websiteAttributes[$attribute->getBackend()->getTable()][] = (int)$attribute->getAttributeId();
						$storeAttributes[$attribute->getAttributeId()] = $attribute->getAttributeCode();
					}				
				}
				

				
				if(!empty($selectAttributes)){
					$productIds = array();
					foreach($selectAttributes as $tableName => $dAttr){ 
						if (!empty($websiteAttributes) && array_key_exists($tableName,$websiteAttributes)) {
							$storeIds = array($storeId);
							if (!empty($storeIds) && !empty($websiteAttributes[$tableName])) {
								$select = $readAdapter->select()
								->from($tableName, $entityIdField)
								->where('attribute_id IN(?)',$websiteAttributes[$tableName])
								->where('store_id IN(?)',$storeIds);
								foreach ($readAdapter->fetchAll($select) as $row) {
									$productIds[] = $row['entity_id'];
								}
							}
						}
					}
				}	
				

				if(!empty($productIds)){
					$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
				} else {
					$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
				}	
			}
				
		}
		
		
		
		
	}


    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/catalog_category/edit', array(
            'store'=>$this->getRequest()->getParam('store'),
            'id'=>$row->getId())
        );
    }
}
