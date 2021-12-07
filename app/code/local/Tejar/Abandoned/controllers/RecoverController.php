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
 * @package     Mage_Customer
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Customer account controller
 *
 * @category   Mage
 * @package    Mage_Customer
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'AdjustWare/Cartalert/controllers/RecoverController.php';
class Tejar_Abandoned_RecoverController extends AdjustWare_Cartalert_RecoverController
{
	 public function cartAction()
    {
		
		
        $code = (string) $this->getRequest()->getParam('code');
        $id   = (int) $this->getRequest()->getParam('id');
        $allow_auto_login = Mage::getStoreConfig('catalog/adjcartalert/allow_auto_login');
		
		$history = Mage::getModel('adjcartalert/history')->load($id);
        if (!$history->getId() || $history->getRecoverCode() != $code){
            $this->_redirect('/');
            return;
        }

		
		if($allow_auto_login){
			
			$s = Mage::getSingleton('customer/session');
			if ($s->isLoggedIn()){
				if ($history->getCustomerId() == $s->getCustomerId()){
					$this->_redirectToCart($history);
					return;
				}
				else 
					$s->logout();
			}
			// customer. login
			if ($history->getCustomerId()){
				$customer = Mage::getModel('customer/customer')->load($history->getCustomerId());
				if ($customer->getId())
					$s->setCustomerAsLoggedIn($customer);
			}
			
			
		}
        
        $this->_redirectToCart($history);
    }
    
    /**
     * @param AdjustWare_Cartalert_Model_History $history
     */
    protected function _redirectToCart($history){
		
		$allow_auto_login = Mage::getStoreConfig('catalog/adjcartalert/allow_auto_login');
        if (!is_null($history)){
            $history->setRecoveredAt(now());
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $history->setRecoveredFrom($_SERVER['REMOTE_ADDR']);
            }
            if (Mage::getStoreConfig('catalog/adjcartalert/stop_after_visit')){
                $cartalert = Mage::getResourceModel('adjcartalert/cartalert')
                    ->cancelAlertsFor($history->getCustomerEmail());
            }
            $history->save();
        }
  
        Mage::dispatchEvent('adjustware_cartalert_cart_recovery', array('quote'=>Mage::getModel('sales/quote')->load($history->getQuoteId())));   

        $quoteId = $history->getQuoteId();
		
        if($history->getCustomerId() == 0 || $allow_auto_login == 0 || $allow_auto_login == ""){
			
			
            if($history->getQuoteIsActive() == 0){
                $orderId = Mage::getResourceModel('adjcartalert/history')->getOrderId($quoteId);
                $this->_reorderGuest($orderId);
                return;
            }

            $quote = Mage::getModel('sales/quote')->load($history->getQuoteId());
            if ($quote){
                Mage::getSingleton('checkout/session')->replaceQuote($quote);
            }

            $this->_redirect('checkout/cart/' . Mage::getStoreConfig('catalog/adjcartalert/cart_recovery_link'));
            return;
        }
        
        if($history->getQuoteIsActive() == 0){
            $reorderUrl = Mage::getResourceModel('adjcartalert/history')->getReorderUrl($quoteId);
            $this->_redirect($reorderUrl);
            return;
        }

        $this->_redirect('checkout/cart/' . Mage::getStoreConfig('catalog/adjcartalert/cart_recovery_link'));
        return;
    }
	
}

