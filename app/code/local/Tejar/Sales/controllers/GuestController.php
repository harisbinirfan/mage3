<?php


/**
 * Sales orders controller
 *
 * @category   Mage
 * @package    MAbout This Orderage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Sales/controllers/GuestController.php';
class Tejar_Sales_GuestController extends Mage_Sales_GuestController
{
	/**
     * Order view form page
     */
    public function formAction()
    {
	
		$pageTitleAjax = Mage::getStoreConfig('intenso/guest_order/guest_order_title', Mage::app()->getStore());	
		$pageKeywordsAjax = Mage::getStoreConfig('intenso/guest_order/guest_order_keywords', Mage::app()->getStore());
		$pageDescriptionAjax = Mage::getStoreConfig('intenso/guest_order/guest_order_description', Mage::app()->getStore());
		
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->_redirect('customer/account/');
            return;
        }
		
        $this->loadLayout();
		
		if($pageTitleAjax){
			$this->getLayout()->getBlock('head')->setTitle($this->__($pageTitleAjax));	
		}
		if($pageKeywordsAjax){
		$this->getLayout()->getBlock('head')->setKeywords($this->__($pageKeywordsAjax));	
		}
		if($pageDescriptionAjax){
		$this->getLayout()->getBlock('head')->setDescription($this->__($pageDescriptionAjax));	
		}
		
        Mage::helper('sales/guest')->getBreadcrumbs($this);
        $this->renderLayout();
    }
	
    /*
	*@name guestOrderCancelAction
	*This function Cancels Guest Order and Send an email
	*
	*/
	public function guestOrderCancelAction()
    {
		$orderId = $this->getRequest()->getParam('id');
		$order   = Mage::getModel('sales/order')->load($orderId);
		$storeId = $order->getStore()->getId();
		
		$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();
		//echo "--> ".$order->getState();die;
		$orderModel = Mage::getModel('sales/order')->load($orderId);
		$orderModel->sendCancelEmail($order);
		Mage::getSingleton('core/session')->addSuccess($this->__('The order has been cancelled.'));
		$this->_redirect('/');
	}
}
