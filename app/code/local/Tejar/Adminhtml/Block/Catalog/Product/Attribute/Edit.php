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
 * Product attribute edit page
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Tejar_Adminhtml_Block_Catalog_Product_Attribute_Edit extends Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit
{

    public function __construct()
    {
        $this->_objectId = 'attribute_id';
        $this->_controller = 'catalog_product_attribute';

        Mage_Adminhtml_Block_Widget_Form_Container::__construct();

        if($this->getRequest()->getParam('popup')) {
            $this->_removeButton('back');
            $this->_addButton(
                'close',
                array(
                    'label'     => Mage::helper('catalog')->__('Close Window'),
                    'class'     => 'cancel',
                    'onclick'   => 'window.close()',
                    'level'     => -1
                )
            );
        } else {
            $this->_addButton(
                'save_and_edit_button',
                array(
                    'label'     => Mage::helper('catalog')->__('Save and Continue Edit'),
                    'onclick'   => 'saveAndContinueEdit()',
                    'class'     => 'save'
                ),
                100
            );
        }

        $this->_updateButton('save', 'label', Mage::helper('catalog')->__('Save Attribute'));
        $this->_updateButton('save', 'onclick', 'saveAttribute()');

		
        if (! Mage::registry('entity_attribute')->getIsUserDefined()) {
            $this->_removeButton('delete');
        } else {
            $this->_updateButton('delete', 'label', Mage::helper('catalog')->__('Delete Attribute'));
        }
		
		if(!$this->_isAllowedAction('delete')){
			$this->_removeButton('delete');	
		}
		

    }
	
	protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/attributes/attributes/' . $action);
    }

}
