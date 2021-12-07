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
class Tejar_Adminhtml_Block_Customer_Grid extends Mage_Adminhtml_Block_Customer_Grid
{


    protected function _prepareCollection()
    {
        $collection = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->addAttributeToSelect('group_id')
            ->joinAttribute('confirmation', 'customer/confirmation', 'entity_id', null, 'left')
            ->joinAttribute('tejar_socialconnect_ftoken', 'customer/tejar_socialconnect_ftoken', 'entity_id', null, 'left')
			->joinAttribute('tejar_socialconnect_gtoken', 'customer/tejar_socialconnect_gtoken', 'entity_id', null, 'left')
            ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
            ->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
            ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
            ->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
            ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left');
		$collection->getSelect()->columns(array('social_login' => 'IF(at_tejar_socialconnect_gtoken.value IS NOT NULL && at_tejar_socialconnect_ftoken.value IS NOT NULL,1,(IF(at_tejar_socialconnect_gtoken.value IS NOT NULL,2,(IF(at_tejar_socialconnect_ftoken.value IS NULL,3,0)))))'));
        $this->setCollection($collection);

        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => Mage::helper('customer')->__('ID'),
            'width'     => '50px',
            'index'     => 'entity_id',
            'type'  => 'number',
        ));
        /*$this->addColumn('firstname', array(
            'header'    => Mage::helper('customer')->__('First Name'),
            'index'     => 'firstname'
        ));
        $this->addColumn('lastname', array(
            'header'    => Mage::helper('customer')->__('Last Name'),
            'index'     => 'lastname'
        ));*/
        $this->addColumn('name', array(
            'header'    => Mage::helper('customer')->__('Name'),
            'index'     => 'name'
        ));
        $this->addColumn('email', array(
            'header'    => Mage::helper('customer')->__('Email'),
            'width'     => '150',
            'index'     => 'email'
        ));

        $groups = Mage::getResourceModel('customer/group_collection')
            ->addFieldToFilter('customer_group_id', array('gt'=> 0))
            ->load()
            ->toOptionHash();

        $this->addColumn('group', array(
            'header'    =>  Mage::helper('customer')->__('Group'),
            'width'     =>  '100',
            'index'     =>  'group_id',
            'type'      =>  'options',
            'options'   =>  $groups,
        ));

        $this->addColumn('Telephone', array(
            'header'    => Mage::helper('customer')->__('Telephone'),
            'width'     => '100',
            'index'     => 'billing_telephone'
        ));

        $this->addColumn('billing_postcode', array(
            'header'    => Mage::helper('customer')->__('ZIP'),
            'width'     => '90',
            'index'     => 'billing_postcode',
        ));

        $this->addColumn('billing_country_id', array(
            'header'    => Mage::helper('customer')->__('Country'),
            'width'     => '100',
            'type'      => 'country',
            'index'     => 'billing_country_id',
        ));

        $this->addColumn('billing_region', array(
            'header'    => Mage::helper('customer')->__('State/Province'),
            'width'     => '100',
            'index'     => 'billing_region',
        ));

        $this->addColumn('customer_since', array(
            'header'    => Mage::helper('customer')->__('Customer Since'),
            'type'      => 'datetime',
            'align'     => 'center',
            'index'     => 'created_at',
            'gmtoffset' => true
        ));

        $this->addColumn('confirmation', array(
            'header'    => Mage::helper('customer')->__('Confirmation'),
            'width'     => '150',
			'type'      => 'options',
			'renderer'  => 'tejar_adminhtml/customer_edit_renderer_confirmation',
			'options'   => array(1 => 'Confirmed', 2 => 'Not confirmed'),
			'filter_condition_callback' => array($this, '_filterConfirmationCallback'),
            'index'     => 'confirmation'
        ));

        $this->addColumn('social_login', array(
            'header'    => Mage::helper('customer')->__('Social Login'),
            'width'     => '150',
			'type'      => 'options',
			'renderer'  => 'tejar_adminhtml/customer_edit_renderer_social',
			'options'   => array(1 => 'Both', 2 => 'Google',3 => 'Facebook'),
			'filter_condition_callback' => array($this, '_filterSocialCallback'),
            'index'     => 'social_login'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
			
            $this->addColumn('website_id', array(
                'header'    => Mage::helper('customer')->__('Website'),
                'align'     => 'center',
                'width'     => '80px',
                'type'      => 'options',
                'options'   => Mage::getSingleton('adminhtml/system_store')->getWebsiteOptionHash(true),
                'index'     => 'website_id',
            ));
       
		
		
			$this->addColumn('store_id', array(
				'header'    => Mage::helper('customer')->__('Store'),
				'align'     => 'center',
				'width'     => '80px',
				'type'      => 'options',
				'options'   => Mage::getSingleton('adminhtml/system_store')->getStoreOptionHash(true),
				'index'     => 'store_id',
			));

		}
		
		
        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('customer')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('customer')->__('Edit'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));

        $this->addExportType('*/*/exportCsv', Mage::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('customer')->__('Excel XML'));
        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }

    protected function _filterConfirmationCallback($collection, $column){
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		if ($value == 1) {
			$this->getCollection()->getSelect()->where("at_confirmation.value IS NULL");
		} else if ($value == 2) {
			$this->getCollection()->getSelect()->where("at_confirmation.value IS NOT NULL");
		}
		return $this;
	}

    protected function _filterSocialCallback($collection, $column){
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		if ($value == 1) {
			$this->getCollection()->getSelect()->where("at_tejar_socialconnect_gtoken.value IS NOT NULL && at_tejar_socialconnect_ftoken.value IS NOT NULL");
		} else if ($value == 2) {
			$this->getCollection()->getSelect()->where("at_tejar_socialconnect_gtoken.value IS NOT NULL");
		} else if ($value == 3) {
			$this->getCollection()->getSelect()->where("at_tejar_socialconnect_ftoken.value IS NOT NULL");
		} 
		return $this;
	}

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('customer');
        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('customer')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('customer')->__('Are you sure?')
        ));
        $this->getMassactionBlock()->addItem('newsletter_subscribe', array(
             'label'    => Mage::helper('customer')->__('Subscribe to Newsletter'),
             'url'      => $this->getUrl('*/*/massSubscribe')
        ));
        $this->getMassactionBlock()->addItem('newsletter_unsubscribe', array(
             'label'    => Mage::helper('customer')->__('Unsubscribe from Newsletter'),
             'url'      => $this->getUrl('*/*/massUnsubscribe')
        ));
		$this->getMassactionBlock()->addItem('mass_confirmation', array(
             'label'    => Mage::helper('customer')->__('Send Confirmation'),
             'url'      => $this->getUrl('*/*/massConfirmation')
        ));
        $groups = $this->helper('customer')->getGroups()->toOptionArray();
        array_unshift($groups, array('label'=> '', 'value'=> ''));
        $this->getMassactionBlock()->addItem('assign_group', array(
             'label'        => Mage::helper('customer')->__('Assign a Customer Group'),
             'url'          => $this->getUrl('*/*/massAssignGroup'),
             'additional'   => array(
                'visibility'    => array(
                     'name'     => 'group',
                     'type'     => 'select',
                     'class'    => 'required-entry',
                     'label'    => Mage::helper('customer')->__('Group'),
                     'values'   => $groups
                 )
            )
        ));
        return $this;
    }
}
