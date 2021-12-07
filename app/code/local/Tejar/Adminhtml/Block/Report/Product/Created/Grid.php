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
 * Adminhtml low stock products report grid block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Adminhtml_Block_Report_Product_Created_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
//    protected $_saveParametersInSession = true;

    public function __construct()
    {
        parent::__construct();
        $this->setId('gridCreated');
        $this->setUseAjax(false);
    }

    protected function _prepareCollection()
    {
        if ($this->getRequest()->getParam('website')) {
            $storeIds = Mage::app()->getWebsite($this->getRequest()->getParam('website'))->getStoreIds();
            $storeId = array_pop($storeIds);
        } else if ($this->getRequest()->getParam('group')) {
            $storeIds = Mage::app()->getGroup($this->getRequest()->getParam('group'))->getStoreIds();
            $storeId = array_pop($storeIds);
        } else if ($this->getRequest()->getParam('store')) {
            $storeId = (int)$this->getRequest()->getParam('store');
        } else {
            $storeId = '';
        }

        /** @var $collection Mage_Reports_Model_Resource_Product_Lowstock_Collection  */
		
		 // $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('*')
			->setStoreId($storeId)
			->setOrder('entity_id', Varien_Data_Collection::SORT_ORDER_DESC);
			
			
			
			
		$collection->joinField('types',
			'catalog/product_relation',
			'IF(count(at_types.parent_id) > 1, "multiple_associate" , IF(at_types.parent_id IS NULL, e.type_id, "associate"))',
			'child_id=entity_id',
			null,
        'left');
        
        $customQuery = "(SELECT entity_id, group_concat(Distinct category_id SEPARATOR ',') as category_ids FROM ( ";
        $customQuery .= "SELECT `e`.entity_id, `at_category_id`.`category_id` ";
		$customQuery .= "FROM `{$collection->getTable('catalog/product')}` AS `e` ";
		$customQuery .= "LEFT JOIN `{$collection->getTable('catalog/category_product')}` AS `at_category_id` ";
		$customQuery .= "ON (at_category_id.`product_id`=e.entity_id) ";
		$customQuery .= ")  sub_query ";
        $customQuery .= "GROUP BY entity_id)";
        
        $collection->joinField('category_ids',
        new Zend_Db_Expr($customQuery),
        'category_ids',
        'entity_id=entity_id',
            null,
        'left');

        if( $storeId ) {
			
			$adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
			
            $collection->addStoreFilter($storeId);
			$collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $adminStore
            );	

			$collection->joinAttribute(
					'manufacturer',
					'catalog_product/manufacturer',
					'entity_id',
					null,
					'inner',
					$storeId
			);
        }


	$collection->getSelect()->group('e.entity_id');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
		
		
		$this->addColumn('entity_id',
            array(
                'header'=> Mage::helper('catalog')->__('Entity'),
                'width' => '50px',
				'sortable'  =>false,
                'type'  => 'number',
                'index' => 'entity_id',
        ));
		
        $this->addColumn('name', array(
            'header'    =>Mage::helper('reports')->__('Name'),
            'sortable'  =>false,
            'index'     =>'name'
        ));


		
        $this->addColumn('sku', array(
            'header'    =>Mage::helper('reports')->__('SKU'),
            'sortable'  =>false,
            'index'     =>'sku'
        )); 
		
		$sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
            ->load()
            ->toOptionHash();	
			
			
		$this->addColumn('set_name',
            array(
                'header'=> Mage::helper('catalog')->__('Attrib. Set Name'),
                
                'index' => 'attribute_set_id',
                'type'  => 'options',
                'options' => $sets,
        ));
		
		
		$sets2 = array();
		$attributeOptions = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'manufacturer');
		$attributeOptions = $attributeOptions->getSource()->getAllOptions(false);
		foreach ($attributeOptions as $key=>$attributeOption) {
			$sets2[$attributeOption['value']] = $attributeOption['label'];
        }
        
		$_categories = Mage::helper('tejar_adminhtml')->getCategoryTree();
		$this->addColumn('category_ids',
            array(
                'header'=> Mage::helper('catalog')->__('Categories'),
                'width' => '200px',
				'filter_index' => 'category_id',
                'index' => 'category_ids',
                'type'  => 'options',
				'renderer' => 'tejar_adminhtml/catalog_product_edit_tab_category_grid_column_renderer_used',
                'options' => $_categories,
				'filter_condition_callback' => array($this, '_filterHasCategoryConditionCallback')
        ));
		
		$this->addColumn('manufacturer',
			array(
				'header'=> Mage::helper('catalog')->__('Manufacturer'),
				'width' => '100px',
				'index' => 'manufacturer',
				'type'  => 'options',
				'options' => $sets2,
		));

		$types = Mage::getSingleton('catalog/product_type')->getOptionArray();
		$types["associate"] = "Associated Product";
        $this->addColumn('type',
            array(
                'header'=> Mage::helper('catalog')->__('Type'),
                'width' => '130px',
                'index' => 'types',
				'filter_index' => 'type_id',
                'type'  => 'options',
                'options' => $types,
				'filter_condition_callback' => array($this, '_filterHasUrlConditionCallback')
        ));
		
		
		$this->addColumn('updated_at', array(
            'header'    =>Mage::helper('reports')->__('Updated At'),
            'width'     =>'215px',
            'align'     =>'right',
            'sortable'  =>false,
            // 'filter'    =>'adminhtml/widget_grid_column_filter_range',
            'index'     =>'updated_at',
            'type'      => 'datetime',
			'filter_time' => true,
			'gmtoffset' => true,
        ));

        $this->addColumn('created_at', array(
            'header'    =>Mage::helper('reports')->__('Created At'),
            'width'     =>'215px',
            'align'     =>'right',
            'sortable'  =>false,
            // 'filter'    =>'adminhtml/widget_grid_column_filter_range',
            'index'     =>'created_at',
            'type'      => 'datetime',
			'filter_time' => true,
			'gmtoffset' => true,
        ));
		
		

        $this->addExportType('*/*/exportCreatedCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportCreatedExcel', Mage::helper('reports')->__('Excel XML'));

        return parent::_prepareColumns();
    }

	protected function _filterHasCategoryConditionCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		if($value){
			$productIds = array();
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$select = $readAdapter->select()->from(array('e' => 'catalog_category_product'), array('product_id'))->where("(e.category_id = ".$value.")");
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['product_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		}
		return $this;
	}
	
	
	protected function _filterHasUrlConditionCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		
		if ($value === "associate") {
			$this->getCollection()->getSelect()->where(
				 "(e.type_id = 'simple') and (at_types.parent_id IS NOT NULL)");
		} else if($value === "simple") {
			$this->getCollection()->getSelect()->where(
				 "(e.type_id = 'simple') and (at_types.parent_id IS NULL)");
		} else if($value === "multiple_associate") {
			$this->getCollection()->getSelect()->where(
				 "(e.type_id = 'simple') and (at_types.parent_id IS NOT NULL)")->having("count(at_types.parent_id) > 1");
		} else {
			$this->getCollection()->getSelect()->where("(e.type_id = '".$value."')");
		}

		return $this;
    }
    
    public function getRowUrl($row)
    {
        return $this->getUrl('*/catalog_product/edit', array(
            'store'=>$this->getRequest()->getParam('store'),
            'id'=>$row->getId())
        );
    }
}
