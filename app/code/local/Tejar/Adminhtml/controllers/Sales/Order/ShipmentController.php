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
 * Adminhtml sales order shipment controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php';
class Tejar_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController
{
   
	const XML_PATH_DELIVERED_TEMPLATE_ID         		  = 'sales_email/delivered/template_id';
	const XML_PATH_DELIVERED_EMAIL_TEMPLATE               = 'sales_email/delivered/template';
    const XML_PATH_DELIVERED_EMAIL_GUEST_TEMPLATE         = 'sales_email/delivered/guest_template';
    const XML_PATH_DELIVERED_EMAIL_IDENTITY               = 'sales_email/shipment/identity';
    const XML_PATH_DELIVERED_EMAIL_COPY_TO                = 'sales_email/shipment/copy_to';
    const XML_PATH_DELIVERED_EMAIL_COPY_METHOD            = 'sales_email/shipment/copy_method';
    const XML_PATH_DELIVERED_EMAIL_ENABLED                = 'sales_email/delivered/enabled';
   	const XML_PATH_REPLY_TO_EMAIL        			   	  = 'trans_email/ident_custom4/email';
	const XML_PATH_PICKED_UP_TEMPLATE_ID         		  = 'sales_email/picked_up/template_id';
	const XML_PATH_PICKED_UP_EMAIL_TEMPLATE               = 'sales_email/picked_up/template';
	const XML_PATH_PICKED_UP_EMAIL_GUEST_TEMPLATE         = 'sales_email/picked_up/guest_template';
	const XML_PATH_DELIVERED_WEBHOOK_URL            	  = 'sales/webhook/delivered_url';
	const XML_PATH_PICKUP_IDENTITY_CODE            	      = 'sales_email/pickup/identity_code';
	const XML_PATH_OUT_FOR_DELIVERY_IDENTITY_CODE  			= 'sales_email/out_for_delivery/identity_code';
    /**
     * Send email with shipment data to customer
     */
    public function deliveredAction()
    {
		if($shipment = $this->_initShipment()){
			try {
				$consumers = array();
				$campaignName = "Delivered";
				$order = $shipment->getOrder();
				$coreHelper = Mage::helper('core');
				$storeId = $shipment->getStore()->getId();
				$notifyCustomer = true;
				$comment = '';
				if(!$canSendDelivered = Mage::getStoreConfig(self::XML_PATH_DELIVERED_EMAIL_ENABLED, $storeId)){
					$this->_getSession()->addError($this->__('delivered Disabled'));
					return   $this->_redirect('*/*/view', array(
						'shipment_id' => $this->getRequest()->getParam('shipment_id')
					));
				}
				// Get the destination email addresses to send copies to
				$copyTo = Mage::getStoreConfig(self::XML_PATH_DELIVERED_EMAIL_COPY_TO, $storeId);
				if (!empty($copyTo)) {
					$copyTo = explode(',', $copyTo);
				} 
				$copyMethod = Mage::getStoreConfig(self::XML_PATH_DELIVERED_EMAIL_COPY_METHOD, $storeId);
				$replyTo = Mage::getStoreConfig(self::XML_PATH_REPLY_TO_EMAIL, $storeId);
				// Check if at least one recepient is found
				if (!$notifyCustomer && !$copyTo) {
					return $this;
				}
				// Start store emulation process
				$appEmulation = Mage::getSingleton('core/app_emulation');
				$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
				try {
					// Retrieve specified view block from appropriate design package (depends on emulated store)
					$paymentBlock = Mage::helper('payment')->getInfoBlock($order->getPayment())
						->setIsSecureMode(true);
					$paymentBlock->getMethod()->setStore($storeId);
					$paymentBlockHtml = $paymentBlock->toHtml();
				} catch (Exception $exception) {
					// Stop store emulation process
					$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
					throw $exception;
				}
				// Stop store emulation process
				$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
				if ($order->getCustomerIsGuest()) {
					$templateId = Mage::getStoreConfig(self::XML_PATH_DELIVERED_EMAIL_GUEST_TEMPLATE, $storeId);
					$customerName = $coreHelper->escapeHtml($order->getBillingAddress()->getName());
				} else {
					$templateId = Mage::getStoreConfig(self::XML_PATH_DELIVERED_EMAIL_TEMPLATE, $storeId);
					$customerName = $coreHelper->escapeHtml($order->getCustomerName());
				}
				
				$isStorePickup = false;
				$isOutOfDelivery = false;
				$identityCodePickup = Mage::getStoreConfig(self::XML_PATH_PICKUP_IDENTITY_CODE, $storeId);
				$identityCodeOutForDelivery = Mage::getStoreConfig(self::XML_PATH_OUT_FOR_DELIVERY_IDENTITY_CODE, $storeId);
				$allTracks = $shipment->getAllTracks();
				if(!empty($allTracks)){
					foreach($allTracks as $track){
						if($track->getCarrierCode() == "custom" && $track->getTitle() == $identityCodeOutForDelivery){
							$isOutOfDelivery = true;
						} else if($track->getCarrierCode() == "custom" && $track->getTitle() == $identityCodePickup){
							$isStorePickup = true;
						} else {
							$isOutOfDelivery = true;
							$isStorePickup = false;
						}
					}
				}
				
				if($isStorePickup || (trim($order->getShippingMethod()) == 'customshippingmethod_customshippingmethod' && $isOutOfDelivery == false)){
					$campaignName = "Picked up";
					if ($order->getCustomerIsGuest()) {
						$templateId = Mage::getStoreConfig(self::XML_PATH_PICKED_UP_EMAIL_GUEST_TEMPLATE, $storeId);
						$customerName = $coreHelper->escapeHtml($order->getBillingAddress()->getName());
					} else {
						$templateId = Mage::getStoreConfig(self::XML_PATH_PICKED_UP_EMAIL_TEMPLATE, $storeId);
						$customerName = $coreHelper->escapeHtml($order->getCustomerName());
					}
				}
					// var_dump($notifyCustomer); die;
				$mailer = Mage::getModel('core/email_template_mailer');
				if ($notifyCustomer) {
					$emailInfo = Mage::getModel('core/email_info');
					$emailInfo->addTo($order->getCustomerEmail(), $customerName);
					if ($copyTo && $copyMethod == 'bcc') {
						// Add bcc to customer email
						foreach ($copyTo as $email) {
							$emailInfo->addBcc($email);
						}
					}
					$mailer->addEmailInfo($emailInfo);
				}
				// Email copies are sent as separated emails if their copy method is 'copy' or a customer should not be notified
				if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
					foreach ($copyTo as $email) {
						$emailInfo = Mage::getModel('core/email_info');
						$emailInfo->addTo($email);
						$mailer->addEmailInfo($emailInfo);
					}
				}
				// Set all required params and send emails
				$mailer->setSender(Mage::getStoreConfig(self::XML_PATH_DELIVERED_EMAIL_IDENTITY, $storeId));
				$mailer->setStoreId($storeId);
				$mailer->setReplyTo($replyTo);
				$mailer->setTemplateId($templateId);
				$mailer->setTemplateParams(array(
						'order'        => $order,
						'shipment'     => $shipment,
						'comment'      => $comment,
						'billing'      => $order->getBillingAddress(),
						'payment_html' => $paymentBlockHtml
					)
				);
				$mailer->send();
				$messageTemplateId = Mage::getStoreConfig(self::XML_PATH_DELIVERED_TEMPLATE_ID, $shipment->getStoreId());
				if($isStorePickup){
					$messageTemplateId = Mage::getStoreConfig(self::XML_PATH_PICKED_UP_TEMPLATE_ID, $shipment->getStoreId());
				}
				$billingAddress = $order->getBillingAddress();
				$telephone = $billingAddress->getData('telephone');
				if($messageTemplateId){
					if($validateNumber = $coreHelper->validateNumber($telephone)){
						$consumers[] = array(
							"consumerCountryCode" => "{$validateNumber->getCountryCode()}",
							"consumerPhoneNumber" => $validateNumber->getNationalNumber(),
							"variables" => array(
								1 => $coreHelper->escapeHtml($order->getCustomerName()),
								2 => $shipment->getIncrementId()
							)
						);
					}		
				}
				$webhookURL = Mage::getStoreConfig(self::XML_PATH_DELIVERED_WEBHOOK_URL, $shipment->getStoreId());
				if($webhookURL){
					$api = Mage::getModel('sales/order_shipment_api');
					$data = $api->info($shipment->getIncrementId());
				 	$data["status_type"] = "Delivered";
				 	$data["shipping_description"] = $shipment->getOrder()->getShippingDescription();
					$data['action'] = $campaignName;
				  	$adminSession 	= Mage::getSingleton('admin/session');
				  	$user 			= $adminSession->getUser();
					$data['user'] = array(
						'userid'	=> $user->getUserId(),
						'username' => $user->getUsername(),
						'email' => $user->getEmail()
					);
  
					$jsonData = json_encode($data);
					$ch = curl_init($webhookURL);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json'
						)
					);
					$response = curl_exec($ch);
					$err = curl_error($ch);
					curl_close($ch);
				}
				$reponse = Mage::getModel('tejar_sales/observer')->postCampaign($shipment,$messageTemplateId,$campaignName,$consumers);
				$this->_getSession()->addSuccess($this->__('The shipment has been delivered.'));
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			} catch (Exception $e) {
				$this->_getSession()->addError($this->__('Cannot delivered shipment.'));
			}
		}
        $this->_redirect('*/*/view', array(
            'shipment_id' => $this->getRequest()->getParam('shipment_id')
        ));
    }

    	/*
	* Create PDF for Shipping Label
	*/
	public function printBulkShippingLabelAction()
    {
		$order_ids = $this->getRequest()->getParam('order_ids');
		$invoiceCollectionArray = array();
		foreach($order_ids as $orderId){
			$orderObject = Mage::getModel('sales/order')->load($orderId);
			$orderStatus = strtolower($orderObject->getStatusLabel());
			if($orderStatus=="processing" || $orderStatus=="complete"){
				$invoiceCollection = $orderObject->getShipmentsCollection();
				foreach($invoiceCollection as $invoice){
					// array_push($invoiceCollectionArray, $invoice);
					$invoiceCollectionArray[$orderId] = $invoice;
				}
			}
				//var_dump($invoice);
		}
		if (count($invoiceCollectionArray)) {
			$pdf = Mage::getModel('sales/order_pdf_shipment')->getBulkShippingLabelPdf($invoiceCollectionArray);
			$this->_prepareDownloadResponse('bulkshippinglabels-'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').
				'.pdf', $pdf->render(), 'application/pdf');
			//Mage::getSingleton('core/session')->addSuccess('Invoices generated successfully!');
		} else {
			Mage::getSingleton('core/session')->addError('No shipment were found!');
			$this->_redirectReferer();
		}
		//var_dump($order->getInvoiceId());die;
		//die;
    }

		/*
	* Create PDF for Shipping Label
	*/
	public function printShippingLabelAction()
    {
        if ($shipmentId = $this->getRequest()->getParam('shipment_id')) {
            if ($shipment = Mage::getModel('sales/order_shipment')->load($shipmentId)) {
                $pdf = Mage::getModel('sales/order_pdf_shipment')->getBulkShippingLabelPdf(array($shipment));
                $this->_prepareDownloadResponse('printshippinglabel-'.Mage::getSingleton('core/date')->date('Y-m-d_H-i-s').
                    '.pdf', $pdf->render(), 'application/pdf');
            }
        }
        else {
            $this->_forward('noRoute');
        }
    }

}
