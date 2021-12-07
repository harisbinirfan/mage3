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
 * Product attributes grid
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Adminhtml_Block_Catalog_Product_Attribute_Grid extends Mage_Adminhtml_Block_Catalog_Product_Attribute_Grid
{
    /**
     * Prepare product attributes grid collection object
     *
     * @return Mage_Adminhtml_Block_Catalog_Product_Attribute_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter();
        $this->setCollection($collection);

        return Mage_Eav_Block_Adminhtml_Attribute_Grid_Abstract::_prepareCollection();
    }

    /**
     * Prepare product attributes grid columns
     *
     * @return Mage_Adminhtml_Block_Catalog_Product_Attribute_Grid
     */
    protected function _prepareColumns()
    {
        Mage_Eav_Block_Adminhtml_Attribute_Grid_Abstract::_prepareColumns();

        $this->addColumnAfter('is_visible', array(
            'header'=>Mage::helper('catalog')->__('Visible'),
            'sortable'=>true,
            'index'=>'is_visible_on_front',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'frontend_label');

        $this->addColumnAfter('is_global', array(
            'header'=>Mage::helper('catalog')->__('Scope'),
            'sortable'=>true,
            'index'=>'is_global',
            'type' => 'options',
            'options' => array(
                Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE =>Mage::helper('catalog')->__('Store View'),
                Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE =>Mage::helper('catalog')->__('Website'),
                Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL =>Mage::helper('catalog')->__('Global'),
            ),
            'align' => 'center',
        ), 'is_visible');

        $this->addColumn('is_searchable', array(
            'header'=>Mage::helper('catalog')->__('Quick Search'),
            'sortable'=>true,
            'index'=>'is_searchable',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'is_user_defined');
		
		$this->addColumn('is_visible_in_advanced_search', array(
            'header'=>Mage::helper('catalog')->__('Advanced Search'),
            'sortable'=>true,
            'index'=>'is_visible_in_advanced_search',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'is_user_defined');		
		
		$this->addColumn('is_filterable_in_search', array(
            'header'=>Mage::helper('catalog')->__('Search Results'),
            'sortable'=>true,
            'index'=>'is_filterable_in_search',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'is_user_defined');		
		
		$this->addColumn('is_used_for_promo_rules', array(
            'header'=>Mage::helper('catalog')->__('Promo Rule'),
            'sortable'=>true,
            'index'=>'is_used_for_promo_rules',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'is_user_defined');		
		
		$this->addColumn('used_in_product_listing', array(
            'header'=>Mage::helper('catalog')->__('Product Listing'),
            'sortable'=>true,
            'index'=>'used_in_product_listing',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'is_user_defined');		
		
		
		$this->addColumn('used_for_sort_by', array(
            'header'=>Mage::helper('catalog')->__('Sorting'),
            'sortable'=>true,
            'index'=>'used_for_sort_by',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'is_user_defined');		
		
		$this->addColumn('is_html_allowed_on_front', array(
            'header'=>Mage::helper('catalog')->__('Allow HTML Tags'),
            'sortable'=>true,
            'index'=>'is_html_allowed_on_front',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'is_user_defined');

        $this->addColumnAfter('is_filterable', array(
            'header'=>Mage::helper('catalog')->__('Layered Navigation'),
            'sortable'=>true,
            'index'=>'is_filterable',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Filterable (with results)'),
                '2' => Mage::helper('catalog')->__('Filterable (no results)'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'is_searchable');       

		$this->addColumnAfter('frontend_input', array(
            'header'=>Mage::helper('catalog')->__('Catalog Input Type'),
            'sortable'=>true,
            'index'=>'frontend_input',
            'type' => 'options',
            'options' => array(
                'text' => Mage::helper('catalog')->__('Text Field'),
                'textarea' => Mage::helper('catalog')->__('Text Area'),
                'date' => Mage::helper('catalog')->__('Date'),
				'boolean' => Mage::helper('catalog')->__('Yes/No'),
				'multiselect' => Mage::helper('catalog')->__('Multiple Select'),
				'select' => Mage::helper('catalog')->__('Dropdown'),
				'price' => Mage::helper('catalog')->__('Price'),
				'media_image' => Mage::helper('catalog')->__('Media Image'),
				'weee' => Mage::helper('catalog')->__('Fixed Product Tax'),
            ),
            'align' => 'center',
        ), 'is_filterable');		
		
		$frontendClassArray = array();
		foreach(Mage::helper('eav')->getFrontendClasses('catalog_product') as $row){
			$frontendClassArray[$row['value']] = $row['label'];
		}
		
		$this->addColumnAfter('frontend_class', array(
            'header'=>Mage::helper('catalog')->__('Input Validation'),
            'sortable'=>true,
            'index'=>'frontend_class',
            'type' => 'options',
            'options' => $frontendClassArray,
            'align' => 'center',
        ), 'frontend_input');
		
		
		
		$this->addColumnAfter('is_unique', array(
            'header'=>Mage::helper('catalog')->__('Unique Value'),
            'sortable'=>true,
            'index'=>'is_unique',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'frontend_class');
		

        $this->addColumnAfter('is_comparable', array(
            'header'=>Mage::helper('catalog')->__('Comparable'),
            'sortable'=>true,
            'index'=>'is_comparable',
            'type' => 'options',
            'options' => array(
                '1' => Mage::helper('catalog')->__('Yes'),
                '0' => Mage::helper('catalog')->__('No'),
            ),
            'align' => 'center',
        ), 'is_unique');

        return $this;
    }
}
