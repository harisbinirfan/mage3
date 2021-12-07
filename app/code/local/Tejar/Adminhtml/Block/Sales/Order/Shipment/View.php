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
 * Adminhtml shipment create
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Adminhtml_Block_Sales_Order_Shipment_View extends Mage_Adminhtml_Block_Sales_Order_Shipment_View
{

    public function __construct()
    {
        $this->_objectId    = 'shipment_id';
        $this->_controller  = 'sales_order_shipment';
        $this->_mode        = 'view';
		
		// var_dump("shariq shahab"); die;

        Mage_Adminhtml_Block_Widget_Form_Container::__construct();

        $this->_removeButton('reset');
        $this->_removeButton('delete');
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/emails')) {
            $this->_updateButton('save', 'label', Mage::helper('sales')->__('Send Tracking Information'));
            $confirmationMessage = Mage::helper('core')->jsQuoteEscape(
                Mage::helper('sales')->__('Are you sure you want to send Shipment email to customer?')
            );
            $this->_updateButton('save',
                'onclick', "deleteConfirm('" . $confirmationMessage . "', '" . $this->getEmailUrl() . "')"
            );
        }

        if ($this->getShipment()->getId()) {
            $this->_addButton('print', array(
                'label'     => Mage::helper('sales')->__('Print'),
                'class'     => 'save',
                'onclick'   => 'setLocation(\''.$this->getPrintUrl().'\')'
                )
            );
        }
		
		if ($this->getShipment()->getId()) {
			$confirmationMessage = Mage::helper('core')->jsQuoteEscape(
				Mage::helper('sales')->__('Are you sure you want it to be delivered?')
			);
			$this->_addButton('delivered', array(
				'label'     => Mage::helper('sales')->__('Delivered'),
				'class'     => 'delivered',
				'onclick'   => "confirmSetLocation('" . $confirmationMessage . "', '" . $this->getDeliveredUrl() . "')"
				)
			);
		}

        		//-- Zee Code: Add PDf print button 'Print Shipping Label' 
		if ($this->getShipment()->getId()) {
            $this->_addButton('printshippinglabel', array(
                'label'     => Mage::helper('sales')->__('Print Shipping Label'),
                'class'     => 'save',
                'onclick'   => 'setLocation(\''.$this->getPrintShippingLabelUrl().'\')'
                )
            );
        }
    }

    	/*
	* Zee Code --- Create ShippingLabel URL for Invoice..
	*/
	public function getPrintShippingLabelUrl()
    {
        return $this->getUrl('*/*/printshippinglabel', array(
            'shipment_id' => $this->getShipment()->getId()
        ));
    }

    /**
     * Retrieve shipment model instance
     *
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function getDeliveredUrl()
    {
        return $this->getUrl('*/sales_order_shipment/delivered', array('shipment_id'  => $this->getShipment()->getId()));
    }

}
