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
 * Product in category grid
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Adminhtml_Block_Catalog_Category_Tab_Product extends Mage_Adminhtml_Block_Catalog_Category_Tab_Product
{

  

    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in category flag
		$filter = $this->getRequest()->getParam('filter');
		$data = $this->helper('adminhtml')->prepareFilterString($filter);
		$productIds = $this->_getSelectedProducts();
		if (empty($productIds)) {
			$productIds = 0;
		}
		
        if ($column->getId() == 'in_category') {
            
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
            }
			
            elseif(!empty($productIds)) {
                $this->getCollection()->addFieldToFilter('entity_id', array('nin'=>$productIds));
            }
        }
		if($column->getId() == 'category_id'){
			
			if(isset($data['in_category']) && $data['in_category'] == 1){
				$this->getCollection()->getSelect()->where('at_category.category_id IN('. $column->getFilter()->getValue() .')');
			} elseif(isset($data['in_category']) && $data['in_category'] == 0){
				$this->getCollection()->getSelect()->where('(at_category.category_id ='.(int) $this->getRequest()->getParam('id', 0).') OR (at_category.category_id ='.$column->getFilter()->getValue().')');
			} elseif(!isset($data['in_category'])) {
				$otherCategory = Mage::getModel('catalog/category')->load($data['category_id']);
				$otherCategoryProducts = $otherCategory->getProductsPosition();
				$otherCategoryProductKeys = array_keys($otherCategoryProducts);
				$result = array_intersect($productIds,$otherCategoryProductKeys);
				if(empty($result)){
					$result = array(0);
				}
				$this->getCollection()->getSelect()->where('(at_category.category_id ='.(int) $this->getRequest()->getParam('id', 0).') OR ((at_category.category_id ='.$column->getFilter()->getValue().') AND (at_category.product_id NOT IN('.join(",",$result).')))');
			}
		}
		
        else {
            Mage_Adminhtml_Block_Widget_Grid::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    protected function _prepareCollection()
    {
        if ($this->getCategory()->getId()) {
            $this->setDefaultFilter(array('in_category'=>1));
        }
		
		$filter = $this->getRequest()->getParam('filter');
		$data = $this->helper('adminhtml')->prepareFilterString($filter);
				
        $collection = Mage::getModel('catalog/product')->getCollection()
			->distinct(true)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('price')
            ->addStoreFilter($this->getRequest()->getParam('store'));
			
			
			if(isset($data['category_id'])){
				$categoryId = $data['category_id'];
				if(empty($categoryId)) $categoryId = 0;
				$collection->getSelect()->joinLeft(array('at_category' => 'catalog_category_product'),"(`at_category`.`product_id` = `e`.`entity_id`) AND (`at_category`.`category_id` IN(".(int) $this->getRequest()->getParam('id', 0).",".$categoryId."))");
			} else {
				$collection->joinField('position',
                'catalog/category_product',
                'position',
                'product_id=entity_id',
                'category_id='.(int) $this->getRequest()->getParam('id', 0),
                'left');
			}
				
        $this->setCollection($collection);
		
        if ($this->getCategory()->getProductsReadonly()) {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
        }

        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        if (!$this->getCategory()->getProductsReadonly()) {
            $this->addColumn('in_category', array(
                'header_css_class' => 'a-center',
                'type'      => 'checkbox',
                'name'      => 'in_category',
                'values'    => $this->_getSelectedProducts(),
                'align'     => 'center',
                'index'     => 'entity_id'
            ));
        }
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('catalog')->__('ID'),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'entity_id'
        ));
        $this->addColumn('name', array(
            'header'    => Mage::helper('catalog')->__('Name'),
            'index'     => 'name'
        ));
        $this->addColumn('sku', array(
            'header'    => Mage::helper('catalog')->__('SKU'),
            'width'     => '80',
            'index'     => 'sku'
        ));
        $this->addColumn('price', array(
            'header'    => Mage::helper('catalog')->__('Price'),
            'type'  => 'currency',
            'width'     => '1',
            'currency_code' => (string) Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
            'index'     => 'price'
        ));
		
		
		$_categories = Mage::helper('tejar_adminhtml')->getCategoryTree();
		
		
		$this->addColumn('category_id',
            array(
                'header'=> Mage::helper('catalog')->__('Other Category'),
                'width' => '180px',
                'index' => 'category_id',
                'type'  => 'options',
                'options' => $_categories,
        ));
		
        $this->addColumn('position', array(
            'header'    => Mage::helper('catalog')->__('Position'),
            'width'     => '1',
            'type'      => 'number',
            'index'     => 'position',
            'editable'  => !$this->getCategory()->getProductsReadonly()
            //'renderer'  => 'adminhtml/widget_grid_column_renderer_input'
        ));

        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }


}

