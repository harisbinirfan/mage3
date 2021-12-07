<?php

/**
 * Sales orders controller
 *
 * @category   Tejar
 * @package    Tejar_Sales
 * @author     Zeeshan
 */
require_once 'Mage/Sales/controllers/OrderController.php';
class Tejar_Sales_OrderController extends Mage_Sales_OrderController
{

   /**
     * Cancel Order and Send email from Frontend by User
     *
     * @name cancelOrderFrontendAction
     * @param   id
     * @return  none
     */
	public function cancelOrderFrontendAction()
    {

		$orderId = $this->getRequest()->getParam('id');
		$order = Mage::getModel('sales/order')->load($orderId);
		$storeId = $order->getStore()->getId();
		$order->setState(Mage_Sales_Model_Order::STATE_CANCELED, true)->save();

		$orderModel = Mage::getModel('sales/order')->load($orderId);
		Mage::dispatchEvent('order_cancel_after', array('order' => $order));
		$orderModel->sendCancelEmail($order);
		Mage::getSingleton('core/session')->addSuccess($this->__('The order has been cancelled.'));
		$this->_redirect('sales/order/history/');
	}


}
