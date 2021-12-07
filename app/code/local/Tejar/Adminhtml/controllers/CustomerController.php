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
 * Customer admin controller
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Adminhtml/controllers/CustomerController.php';
class Tejar_Adminhtml_CustomerController extends Mage_Adminhtml_CustomerController
{
	// public function massConfirmationAction()
    // {
		
    //     $customersIds = $this->getRequest()->getParam('customer');
    //     if(!is_array($customersIds)) {
    //          Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select customer(s).'));
    //     } else {
    //         try {
				
    //             foreach ($customersIds as $customerId) {
    //                 $customer = Mage::getModel('customer/customer')->load($customerId);
	// 				// check if it is inactive
	// 				if ($customer->getConfirmation()) {
	// 					$customer->sendNewAccountEmail('confirmation', '', $customer->getStoreId());
	// 				}
    //             }
				
    //             Mage::getSingleton('adminhtml/session')->addSuccess(
    //                 Mage::helper('adminhtml')->__('Total of %d record(s) were updated.', count($customersIds))
    //             );
    //         } catch (Exception $e) {
    //             Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
    //         }
    //     }

    //     $this->_redirect('*/*/index');
    // }

    public function massConfirmationAction()
    {
        $customersIds = $this->getRequest()->getParam('customer');
        if(!is_array($customersIds)) {
             Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select customer(s).'));
        } else {
            try {
				$remote_ips = array();
				$resource = Mage::getModel('core/resource');
				$readConnection = $resource->getConnection('core_read');
				$select  = $readConnection->select()->from($readConnection->getTableName('sales_flat_order'), array('remote_ip','customer_id'))->where('customer_id IN ('.join($customersIds,',').')');
				$result = $readConnection->fetchAll($select);
				foreach($result as $r){
					$remote_ips[$r['customer_id']] = $r['remote_ip'];
				}
                foreach ($customersIds as $customerId) {
                    $customer = Mage::getModel('customer/customer')->load($customerId);
					// check if it is inactive
					if ($customer->getConfirmation()) {
						if(array_key_exists($customerId,$remote_ips)){
							if($remote_ips[$customerId]){
								$customer->sendNewAccountEmail('confirmation', '', $customer->getStoreId());
							} else {
								$newPassword = $customer->generatePassword();
								$customer->changePassword($newPassword);
								$customer->sendNewAccountEmail('custom_confirmation', '', $customer->getStoreId(),$newPassword);
							}
						} else {
							$customer->sendNewAccountEmail('confirmation', '', $customer->getStoreId());
						}
					}
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were updated.', count($customersIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
}
