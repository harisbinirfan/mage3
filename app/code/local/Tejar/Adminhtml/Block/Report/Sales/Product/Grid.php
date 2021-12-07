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
class Tejar_Adminhtml_Block_Report_Sales_Product_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
	
	
	protected $_dateFilterVisibility = true;
	
	protected $_saveParametersInSession = true;
	
	protected $_filters = array();
	
	protected $_defaultFilters = array(
		'report_from' => '',
		'report_to' => ''
	);


    public function __construct()
    {
        parent::__construct();
        $this->setId('productsReportGrid');
		$this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
		$this->setTemplate('tejar_report/grid.phtml');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(false);
		$this->setDefaultDir('DESC');
    }
	
	
	protected function _setFilterValues($data)
    {
		
		if(array_key_exists('report_from',$data) || array_key_exists('report_to',$data)){
			foreach ($data as $name => $value) {
				if (isset($data[$name])) {
					$this->setFilter($name, $data[$name]);
				}
			}
		}
	
        foreach ($this->getColumns() as $columnId => $column) {
            if (isset($data[$columnId])
                && (!empty($data[$columnId]) || strlen($data[$columnId]) > 0)
                && $column->getFilter()
            ) {
                $column->getFilter()->setValue($data[$columnId]);
                $this->_addColumnFilterToCollection($column);
            }
        }
        return $this;
    }

	
	public function setFilter($name, $value)
    {
        if ($name) {
            $this->_filters[$name] = $value;
        }
    }

    public function getFilter($name)
    {
        if (isset($this->_filters[$name])) {
            return $this->_filters[$name];
        } else {
            return ($this->getRequest()->getParam($name))
                    ?htmlspecialchars($this->getRequest()->getParam($name)):'';
        }
    }
	
	
	/**
     * Retrieve locale
     *
     * @return Mage_Core_Model_Locale
     */
    public function getLocale()
    {
        if (!$this->_locale) {
            $this->_locale = Mage::app()->getLocale();
        }
        return $this->_locale;
    }
	
	/**
     * Set visibility of date filter
     *
     * @param boolean $visible
     */
    public function setDateFilterVisibility($visible=true)
    {
        $this->_dateFilterVisibility = $visible;
    }
	
	
	public function getDateFormat()
    {
        return $this->getLocale()->getDateStrFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    }
	
	
	/**
     * Return visibility of date filter
     *
     * @return boolean
     */
    public function getDateFilterVisibility()
    {
        return $this->_dateFilterVisibility;
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
		
		
		$filter = $this->getParam($this->getVarNameFilter(), null);
		
		if (is_null($filter)) {
            $filter = $this->_defaultFilter;
        }
		
		
		
		
		
		if (is_string($filter)) {
            $data = array();
            $filter = base64_decode($filter);
            parse_str(urldecode($filter), $data);
			
			// var_dump($data); die;

            if (!isset($data['report_from'])) {
                // getting all reports from 2001 year
                $date = new Zend_Date(mktime(0,0,0,1,1,2001));
                $data['report_from'] = $date->toString($this->getLocale()->getDateFormat('short'));
            }

            if (!isset($data['report_to'])) {
                // getting all reports from 2001 year
                $date = new Zend_Date();
                $data['report_to'] = $date->toString($this->getLocale()->getDateFormat('short'));
            }
			
			$this->_setFilterValues($data);
			
		} else if ($filter && is_array($filter)) {
            $this->_setFilterValues($filter);
        } else if(0 !== sizeof($this->_defaultFilter)) {
            $this->_setFilterValues($this->_defaultFilter);
        }

        /** @var $collection Mage_Reports_Model_Resource_Product_Lowstock_Collection  */
		
		 // $store = $this->_getStore();
		 
		 
		 
        $collection = Mage::getModel('sales/order_item')->getCollection();
			
		$collection->getSelect()->columns(
			array(
				"qty_ordered" => "SUM(qty_ordered)",
				"qty_canceled" => "SUM(qty_canceled)",
				"qty_invoiced" => "SUM(qty_invoiced)",
				"qty_refunded" => "SUM(qty_refunded)",
				"qty_shipped" => "SUM(qty_shipped)"
			)
		);
		
		if($storeId){
			$collection->addFieldToFilter("main_table.store_id", $storeId);
		}
		
		
		$_manufacturer = Mage::getModel('eav/config')->getAttribute('catalog_product', 'manufacturer');
		if($_manufacturer){
			$_manufacturerTable = $_manufacturer->getBackend()->getTable();
			$collection->getSelect()
			->joinInner(array('at_manufacturer_default' => "{$_manufacturerTable}"),"(`at_manufacturer_default`.`entity_id` = `main_table`.`product_id`) AND (`at_manufacturer_default`.`attribute_id` = '".$_manufacturer->getId()."') AND `at_manufacturer_default`.`store_id` = 0", array())
			->joinLeft(array('at_manufacturer' => "{$_manufacturerTable}"),"(`at_manufacturer`.`entity_id` = `main_table`.`product_id`) AND (`at_manufacturer`.`attribute_id` = '".$_manufacturer->getId()."') AND `at_manufacturer`.`store_id` = main_table.store_id", array());
			$collection->getSelect()->columns(
				array(
					"manufacturer" => "IF(at_manufacturer.value_id > 0, at_manufacturer.value, at_manufacturer_default.value)"
				)
			);
						
		}
		
		
		 if ($this->getFilter('report_from') && $this->getFilter('report_to')) {
            /**
             * Validate from and to date
             */
            try {
                $from = $this->getLocale()->date($this->getFilter('report_from'), Zend_Date::DATE_SHORT, null, false);
                $to   = $this->getLocale()->date($this->getFilter('report_to'), Zend_Date::DATE_SHORT, null, false);

				$start_date_formatted = date('Y-m-d', strtotime($from)).' 00:00:00';          
				$end_date_formatted = date('Y-m-d', strtotime($to)).' 00:00:00';
				
				$collection->getSelect()->joinInner(array('order_table' => "sales_flat_order_grid"),"(`order_table`.`entity_id` = `main_table`.`order_id`)", array());
				$collection->getSelect()->where("order_table.created_at >= ?",  $start_date_formatted)
										->where("order_table.created_at <= ?",  $end_date_formatted);
                // $collection->setInterval($from, $to);
				
            }
            catch (Exception $e) {
                $this->_errors[] = Mage::helper('reports')->__('Invalid date specified.');
            }
        }
		
		
		$collection->getSelect()->group('main_table.product_id');
        $this->setCollection($collection);

        Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
        // $this->getCollection()->addWebsiteNamesToResult();
        return $this;
        // return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
		
		
		$this->addColumn('product_id',
            array(
                'header'=> Mage::helper('catalog')->__('Product Id'),
                'width' => 50,
                'type'  => 'number',
                'index' => 'product_id',
        ));
		
		$this->addColumn('name', array(
            'header'    => Mage::helper('catalog')->__('Name'),
            'index'     => 'name'
        ));
		
		$sets2 = array();
		$attributeOptions = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'manufacturer');
		$attributeOptions = $attributeOptions->getSource()->getAllOptions(false);
		foreach ($attributeOptions as $key=>$attributeOption) {
			$sets2[$attributeOption['value']] = $attributeOption['label'];
        }
        
		$this->addColumn('manufacturer',
			array(
				'header'=> Mage::helper('catalog')->__('Manufacturer'),
				'width' => '100px',
				'index' => 'manufacturer',
				'filter_index' => new Zend_Db_Expr("(IF(at_manufacturer.value_id > 0, at_manufacturer.value, at_manufacturer_default.value))"),
				'type'  => 'options',
				'options' => $sets2,
		));
		
		$this->addColumn('qty_ordered',
            array(
                'header'=> Mage::helper('catalog')->__('Ordered Qty'),
                'width' => 50,
                'type'  => 'number',
                'index' => 'qty_ordered',
        ));
		
		$this->addColumn('qty_canceled',
            array(
                'header'=> Mage::helper('catalog')->__('Canceled Qty'),
                'width' => 50,
                'type'  => 'number',
                'index' => 'qty_canceled',
        ));
		
		$this->addColumn('qty_invoiced',
            array(
                'header'=> Mage::helper('catalog')->__('Invoiced Qty'),
                'width' => 50,
                'type'  => 'number',
                'index' => 'qty_invoiced',
        ));


		$this->addColumn('qty_refunded',
            array(
                'header'=> Mage::helper('catalog')->__('Refunded Qty'),
                'width' => 50,
                'type'  => 'number',
                'index' => 'qty_refunded',
        ));


		$this->addColumn('qty_shipped',
            array(
                'header'=> Mage::helper('catalog')->__('Shipped Qty'),
                'width' => 50,
                'type'  => 'number',
                'index' => 'qty_shipped',
        ));
		
		
		
		
		
		
		/*
        $this->addColumn('name', array(
            'header'    =>Mage::helper('reports')->__('Name'),
            'index'     =>'name'
        ));
		
		$sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
            ->load()
            ->toOptionHash();	
		
		$sets2 = array();
		$attributeOptions = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'manufacturer');
		$attributeOptions = $attributeOptions->getSource()->getAllOptions(false);
		foreach ($attributeOptions as $key=>$attributeOption) {
			$sets2[$attributeOption['value']] = $attributeOption['label'];
        }
        
		$this->addColumn('manufacturer',
			array(
				'header'=> Mage::helper('catalog')->__('Manufacturer'),
				'width' => '100px',
				'index' => 'manufacturer',
				'type'  => 'options',
				'options' => $sets2,
		));
*/

        $this->addExportType('*/*/exportCreatedCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportCreatedExcel', Mage::helper('reports')->__('Excel XML'));

        return parent::_prepareColumns();
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
