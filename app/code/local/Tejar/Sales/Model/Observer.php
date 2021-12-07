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
 * @package     Mage_Sales
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Order Observer
 *
 * @category   Tejar
 * @package    Tejar_Sales
 * @author     Shariq Shahab <shariqshahab2@gmail.com>
 * @Code       3SD
 */


class Tejar_Sales_Model_Observer
{
	/**
	   * XML configuration paths
     */
    const XML_PATH_ORDER_WEBHOOK_ENABLED           	  = 'sales/webhook/order';
    const XML_PATH_ORDER_WEBHOOK_URL		          = 'sales/webhook/order_url';
    const XML_PATH_INVOICE_WEBHOOK_ENABLED            = 'sales/webhook/invoice';
    const XML_PATH_INVOICE_WEBHOOK_URL                = 'sales/webhook/invoice_url';
    const XML_PATH_SHIPMENT_WEBHOOK_ENABLED           = 'sales/webhook/shipment';
    const XML_PATH_SHIPMENT_WEBHOOK_URL               = 'sales/webhook/shipment_url';
	const XML_PATH_CREDITMEMO_WEBHOOK_ENABLED         = 'sales/webhook/creditmemo';
	const XML_PATH_CREDITMEMO_WEBHOOK_URL             = 'sales/webhook/creditmemo_url';
	const XML_PATH_ORDER_TEMPLATE_ID           	 	  = 'sales_email/order/template_id';
	const XML_PATH_INVOICE_TEMPLATE_ID           	  = 'sales_email/invoice/template_id';
	const XML_PATH_SHIPMENT_TEMPLATE_ID           	  = 'sales_email/shipment/template_id';
	const XML_PATH_CREDITMEMO_TEMPLATE_ID        	  = 'sales_email/creditmemo/template_id';
	const XML_PATH_ORDER_CANCEL_TEMPLATE_ID  		  = 'sales_email/order_cancel/template_id';
	const XML_PATH_PICKUP_TEMPLATE_ID           	  = 'sales_email/pickup/template_id';
	const XML_PATH_OUT_FOR_DELIVERY_TEMPLATE_ID       = 'sales_email/out_for_delivery/template_id';
	const XML_PATH_OUTSTANDING_PAYMENT_TEMPLATE_ID       = 'sales_email/outstanding_payment/template_id';
	const XML_PATH_ORDER_MESSAGE_TEMPLATE             = 'sales_email/order/message_template';
	const XML_PATH_INVOICE_MESSAGE_TEMPLATE           = 'sales_email/invoice/message_template';
	const XML_PATH_ORDER_CANCEL_MESSAGE_TEMPLATE      = 'sales_email/order_cancel/message_template';
	const XML_PATH_SHIPMENT_MESSAGE_TEMPLATE          = 'sales_email/shipment/message_template';
	const XML_PATH_RETURN_MESSAGE_TEMPLATE           	   = 'sales_email/return/message_template';
	const XML_PATH_CREDITMEMO_MESSAGE_TEMPLATE        	   = 'sales_email/creditmemo/message_template';
	const XML_PATH_EXTRA_CONFIRMATION_MESSAGE_TEMPLATE     = 'sales_email/extra/confirmation_message_template';
	const XML_PATH_PICKUP_MESSAGE_TEMPLATE         	  = 'sales_email/pickup/message_template';
	const XML_PATH_OUT_FOR_DELIVERY_MESSAGE_TEMPLATE  = 'sales_email/out_for_delivery/message_template';
	const XML_PATH_PICKUP_EMAIL_ENABLED              		     = 'sales_email/pickup/enabled';
	const XML_PATH_PICKUP_IDENTITY_CODE            	    	= 'sales_email/pickup/identity_code';
	const XML_PATH_OUT_FOR_DELIVERY_EMAIL_ENABLED                = 'sales_email/out_for_delivery/enabled';
	const XML_PATH_OUT_FOR_DELIVERY_IDENTITY_CODE  			= 'sales_email/out_for_delivery/identity_code';
	const XML_PATH_OUTSTANDING_PAYMENT_MESSAGE_TEMPLATE  = 'sales_email/outstanding_payment/message_template';
	const XML_PATH_DELIVERED_MESSAGE_TEMPLATE        = 'sales_email/delivered/message_template';
	const XML_PATH_SALES_API_LIVEPERSON_ACCOUNT_ID   	 = 'sales/api/live_person_account';
	const XML_PATH_SALES_API_LIVEPERSON_CLIENT_ID     	 = 'sales/api/live_person_client_id';
	const XML_PATH_SALES_API_LIVEPERSON_CLIENT_SECRET    = 'sales/api/live_person_client_secret';
	const XML_PATH_SALES_API_TWILIO_ACCOUNT_NUMBER		 = 'sales/api/twilio_account';
	const XML_PATH_SALES_API_TWILIO_ACCOUNT_SID		  	 = 'sales/api/twilio_account_sid';
	const XML_PATH_SALES_API_TWILIO_ACCOUNT_SECRET     	 = 'sales/api/twilio_auth_token';
	const XML_PATH_PICKED_UP_MESSAGE_TEMPLATE         	  = 'sales_email/picked_up/message_template';
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

	/**
     * Retrieve adminhtml session singleton
     *
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

	 /**
     * post Order information
     *
     * @param string Order collection
     * @return array
     */
    public function setOrderWebhook(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
		$isEnabled = Mage::getStoreConfig(self::XML_PATH_ORDER_WEBHOOK_ENABLED, $order->getStoreId());
		$webhookURL = Mage::getStoreConfig(self::XML_PATH_ORDER_WEBHOOK_URL, $order->getStoreId());
		if($isEnabled){
			$api = Mage::getModel('sales/order_api');
			$data = $api->info($order->getIncrementId());
			$data["status_type"] = "Confirmation";
			$data = json_encode($data);
			$ch = curl_init($webhookURL);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data))
			);
			curl_exec($ch);
			curl_close($ch);
		}
		
		$coreHelper = Mage::helper('core');
		$campaignName = "Order";
		$consumers = array();
		$templateId = Mage::getStoreConfig(self::XML_PATH_ORDER_TEMPLATE_ID, $order->getStoreId());
		$billingAddress = $order->getBillingAddress();
		$telephone = $billingAddress->getData('telephone');
		if($templateId){		
			if($validateNumber = $coreHelper->validateNumber($telephone)){		
				$consumers[] = array(
					"consumerCountryCode" => "{$validateNumber->getCountryCode()}",
					"consumerPhoneNumber" => $validateNumber->getNationalNumber(),
					"variables" => array(
						1 => $coreHelper->escapeHtml($order->getCustomerName()),
						2 => $order->getStore()->formatPrice($order->getGrandTotal(), false),
						3 => $order->getIncrementId()
					)
				);				
			}
		}
		
		$reponse = $this->postCampaign($order,$templateId,$campaignName,$consumers);

    }

	//  /**
    //  * post Invoice information
    //  *
    //  * @param string Invoice collection
    //  * @return array
    //  */
	//  public function setInvoiceWebhook(Varien_Event_Observer $observer)
    // {
    //     $invoice = $observer->getEvent()->getInvoice();
		/**
     * post Invoice information
     *
     * @param string Invoice collection
     * @return array
     */
	public function setInvoiceWebhook(Varien_Event_Observer $observer)
    {
		
		$invoice = $observer->getEvent()->getInvoice();
		$invoiceId = $invoice->getId();
		$order = $invoice->getOrder();
		$paymentMethodCode = $order->getPayment()->getMethodInstance()->getCode();
		
		if($paymentMethodCode == "stripe_payments"){
			$output = shell_exec("php /usr/share/nginx/tejar/shell/customWebhook.php invoice_id {$invoiceId} > /dev/null&");							
		} else {
			$this->triggerInvoiceWebhook($invoice);
		}
	}

	 /**
     * post Invoice information
     *
     * @param string Invoice collection
     * @return array
     */
	 public function triggerInvoiceWebhook($invoice)
    {
        $invoice = $invoice;
        if(!$invoice->getSentDelayEmail() || $invoice->getSentDelayEmail() != 1){
      		$isEnabled = Mage::getStoreConfig(self::XML_PATH_INVOICE_WEBHOOK_ENABLED, $invoice->getStoreId());
      		$webhookURL = Mage::getStoreConfig(self::XML_PATH_INVOICE_WEBHOOK_URL, $invoice->getStoreId());
      		if($isEnabled){
      			$api = Mage::getModel('sales/order_invoice_api');
      			$data = $api->info($invoice->getIncrementId());
				if(isset($data['payment_method_code'])){
					if(($data['payment_method_code'] == "cryozonic_stripe" || $data['payment_method_code'] == "stripe_payments") && $data['can_void_flag'] !== NULL){
						return false;
					}
				}
				$data["status_type"] = "Invoice";
				foreach($invoice->getOrder()->getAllItems() as $key => $item){
					if(isset($item['product_options'])){
						$product_options = @unserialize($item['product_options']);	
						if(isset($product_options['options'])){
							$data['items'][$key]['custom_options'] = $product_options['options'];
						}
					}
				}
      			$jsonData = json_encode($data);
      			$ch = curl_init($webhookURL);
      			curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      				'Content-Type: application/json',
      				'Content-Length: ' . strlen($jsonData))
      			);
      			curl_exec($ch);
				curl_close($ch);

      		}
			
			$consumers = array();
			$campaignName = "Invoice";
			$order = $invoice->getOrder();
			$coreHelper = Mage::helper('core');
			$templateId = Mage::getStoreConfig(self::XML_PATH_INVOICE_TEMPLATE_ID, $invoice->getStoreId());
			$billingAddress = $order->getBillingAddress();
			$telephone = $billingAddress->getData('telephone');
			if($templateId){
				if($validateNumber = $coreHelper->validateNumber($telephone)){		
					$consumers[] = array(
						"consumerCountryCode" => "{$validateNumber->getCountryCode()}",
						"consumerPhoneNumber" => $validateNumber->getNationalNumber(),
						"variables" => array(
							1 => $coreHelper->escapeHtml($order->getCustomerName()),
							2 => $invoice->getStore()->formatPrice($invoice->getGrandTotal(), false),
							3 => $invoice->getIncrementId()
						)
					);
				}		
			}
			
			$reponse = $this->postCampaign($invoice,$templateId,$campaignName,$consumers);
		}
    }

	 /**
     * post Shipment information
     *
     * @param string Shipment collection
     * @return array
     */
	public function setShipmentWebhook(Varien_Event_Observer $observer)
    {

		$shipment = $observer->getEvent()->getShipment();
		$request = Mage::app()->getRequest();
        // if(!$shipment->getSentRatingEmail() || $shipment->getSentRatingEmail() != 1){
			$storeId = $shipment->getStoreId();
      		$isEnabled = Mage::getStoreConfig(self::XML_PATH_SHIPMENT_WEBHOOK_ENABLED, $shipment->getStoreId());
      		$webhookURL = Mage::getStoreConfig(self::XML_PATH_SHIPMENT_WEBHOOK_URL, $shipment->getStoreId());
      		if($isEnabled){
      			$api = Mage::getModel('sales/order_shipment_api');
      			$data = $api->info($shipment->getIncrementId());
				$data["status_type"] = "Shipment";
				$data["shipping_description"] = $shipment->getOrder()->getShippingDescription();
				$data["shipping_address"] = $shipment->getShippingAddress()->getData();
				$data["action"] = $request->getActionName();
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
      				'Content-Type: application/json',
      				'Content-Length: ' . strlen($jsonData))
      			);
      			curl_exec($ch);
				curl_close($ch);
      		}
			
			$consumers = array();
			$campaignName = "Shipment";
			$order = $shipment->getOrder();
			$coreHelper = Mage::helper('core');
			$templateId = Mage::getStoreConfig(self::XML_PATH_SHIPMENT_TEMPLATE_ID, $shipment->getStoreId());

			$identityCodePickup = Mage::getStoreConfig(self::XML_PATH_PICKUP_IDENTITY_CODE, $shipment->getStoreId());
			$identityCodeOutForDelivery = Mage::getStoreConfig(self::XML_PATH_OUT_FOR_DELIVERY_IDENTITY_CODE, $shipment->getStoreId());

			$isOutOfDelivery = false;
			$isStorePickup   = false;
			$allTracks = $shipment->getAllTracks();
			if(!empty($allTracks)){
				foreach($allTracks as $track){
					if($track->getCarrierCode() == "custom" && $track->getTitle() == $identityCodeOutForDelivery){
						$isOutOfDelivery = true;
					} else if($track->getCarrierCode() == "custom" && $track->getTitle() == $identityCodePickup){
						$isStorePickup = true;
					}
				}
			}
			$canSendPickup = Mage::getStoreConfig(self::XML_PATH_PICKUP_EMAIL_ENABLED, $storeId);
			$canSendOutForDelivery = Mage::getStoreConfig(self::XML_PATH_OUT_FOR_DELIVERY_EMAIL_ENABLED, $storeId);
			if(($canSendPickup && trim($order->getShippingMethod()) === 'customshippingmethod_customshippingmethod') || ($isStorePickup)){
				$campaignName = "Pickup";
				$templateId = Mage::getStoreConfig(self::XML_PATH_PICKUP_TEMPLATE_ID, $shipment->getStoreId());
			} else if($canSendOutForDelivery && $isOutOfDelivery) {
				$campaignName = "Out for Delivery";
				$templateId = Mage::getStoreConfig(self::XML_PATH_OUT_FOR_DELIVERY_TEMPLATE_ID, $shipment->getStoreId());
			}

			$billingAddress = $order->getBillingAddress();
			$telephone = $billingAddress->getData('telephone');
			if($templateId){
				if($validateNumber = $coreHelper->validateNumber($telephone)){		
					$consumers[] = array(
						"consumerCountryCode" => "{$validateNumber->getCountryCode()}",
						"consumerPhoneNumber" => $validateNumber->getNationalNumber(),
						"variables" => array(
							1 => $coreHelper->escapeHtml($order->getCustomerName()),
							2 => $shipment->getIncrementId(),
							3 => "",
						)
					);
				}		
			}
			
			if(($data["action"] == "save" && $data['email_sent'] == 1) || ($data["action"] == "email")){
				$reponse = $this->postCampaign($shipment,$templateId,$campaignName,$consumers);
			}
		// }
    }

	 /**
     * post credit memo information
     *
     * @param string credit memo collection
     * @return array
     */

	public function setCreditmemoWebhook(Varien_Event_Observer $observer)
    {
		$creditmemo = $observer->getEvent()->getCreditmemo();
		$isEnabled = Mage::getStoreConfig(self::XML_PATH_CREDITMEMO_WEBHOOK_ENABLED, $creditmemo->getStoreId());
		$webhookURL = Mage::getStoreConfig(self::XML_PATH_CREDITMEMO_WEBHOOK_URL, $creditmemo->getStoreId());
		if($creditmemo->getOrder()->getCustomerId()){
			foreach($creditmemo->getAllItems() as $item){
				if($item->getOrderItem()->getParentItem()) continue;
				$product = Mage::getModel('catalog/product')->load($item->getProductId());
				if(!$product->getIsInStock()){
					$model = Mage::getModel('productalert/stock')
						->setCustomerId($creditmemo->getCustomerId())
						->setProductId($product->getId())
						->setWebsiteId($creditmemo->getStore()->getWebsiteId());
					$model->save();
				}
			}
		}
		
		if($isEnabled){
			$api = Mage::getModel('sales/order_creditmemo_api');
			$data = $api->info($creditmemo->getIncrementId());
			$data["status_type"] = "Credit Memo";
			foreach($creditmemo->getOrder()->getAllItems() as $key => $item){
				if(isset($item['product_options'])){
					$product_options = @unserialize($item['product_options']);	
					if(isset($product_options['options'])){
						$data['items'][$key]['custom_options'] = $product_options['options'];
					}
				}
			}
			$data = json_encode($data);
			$ch = curl_init($webhookURL);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data))
			);
			curl_exec($ch);
			curl_close($ch);
		}
		
		$consumers = array();
		$campaignName = "Credit Memo";
		$order = $creditmemo->getOrder();
		$coreHelper = Mage::helper('core');
		$templateId = Mage::getStoreConfig(self::XML_PATH_CREDITMEMO_TEMPLATE_ID, $creditmemo->getStoreId());
		$billingAddress = $order->getBillingAddress();
		$telephone = $billingAddress->getData('telephone');
		if($templateId){
			if($validateNumber = $coreHelper->validateNumber($telephone)){		
				$consumers[] = array(
					"consumerCountryCode" => "{$validateNumber->getCountryCode()}",
					"consumerPhoneNumber" => $validateNumber->getNationalNumber(),
					"variables" => array(
						1 => $coreHelper->escapeHtml($order->getCustomerName()),
						2 => $creditmemo->getStore()->formatPrice($creditmemo->getGrandTotal(), false),
						3 => $creditmemo->getIncrementId()
					)
				);
			}		
		}
		
		$reponse = $this->postCampaign($creditmemo,$templateId,$campaignName,$consumers);
		
    }

    /**
     * post Order Cancel information
     *
     * @param string Order Cancel hook
     * @return array
     */
    public function setCancelWebhook(Varien_Event_Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
		$storeId = $order->getStore()->getStoreId();
		$zendDate = Zend_Date::now()->setLocale(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE , $storeId))->setTimezone(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE , $storeId));
		$currentDate = Mage::helper('core')->formatDate($zendDate , 'short', true);
		$currentDate = date('Y-m-d H:i:s',strtotime($currentDate));
		$isEnabled = Mage::getStoreConfig(self::XML_PATH_ORDER_WEBHOOK_ENABLED, $order->getStoreId());
		$webhookURL = Mage::getStoreConfig(self::XML_PATH_ORDER_WEBHOOK_URL, $order->getStoreId());
		if($order->getCustomerId()){
			foreach($order->getAllVisibleItems() as $item){
				if($item->getProductId()){
					$product = Mage::getModel('catalog/product')->load($item->getProductId());
					if(!$product->getIsInStock()){
						$model = Mage::getModel('productalert/stock')
							->setCustomerId($order->getCustomerId())
							->setProductId($product->getId())
							->setWebsiteId($order->getStore()->getWebsiteId());
						$model->save();
					}
				}
			}
		}
		
		if($isEnabled){
			$api = Mage::getModel('sales/order_api');
			$data = $api->info($order->getIncrementId());
			$data["status_type"] = "Canceled";
			$data["store_cancel_date"] = $currentDate;
			$data["order_increment_id"] = $order->getIncrementId();
			$data = json_encode($data);
			$ch = curl_init($webhookURL);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data))
			);
			curl_exec($ch);
			curl_close($ch);
		}


		$coreHelper = Mage::helper('core');
		$campaignName = "Canceled";
		$consumers = array();
		$billingAddress = $order->getBillingAddress();
		$telephone = $billingAddress->getData('telephone');
		$templateId = Mage::getStoreConfig(self::XML_PATH_ORDER_CANCEL_TEMPLATE_ID, $order->getStoreId());
		if($templateId){
			if($validateNumber = $coreHelper->validateNumber($telephone)){
				$consumers[] = array(
					"consumerCountryCode" => "{$validateNumber->getCountryCode()}",
					"consumerPhoneNumber" => $validateNumber->getNationalNumber(),
					"variables" => array(
						1 => $coreHelper->escapeHtml($order->getCustomerName()),
						2 => $order->getStore()->formatPrice($order->getGrandTotal(), false),
						3 => $order->getIncrementId()
					)
				);				
			}
		}
		
		$reponse = $this->postCampaign($order,$templateId,$campaignName,$consumers);
	}

		/**
     * Send email with shipment data to customer
     */
    public function deliveredAction($shipmentId)
    {
		$result = array();
		$shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
		if($shipment->getId()){
			try {
				$consumers = array();
				$campaignName = "Delivered";
				$order = $shipment->getOrder();
				$coreHelper = Mage::helper('core');
				$storeId = $shipment->getStore()->getId();
				$notifyCustomer = true;
				$comment = '';
				if(!$canSendDelivered = Mage::getStoreConfig(self::XML_PATH_DELIVERED_EMAIL_ENABLED, $storeId)){
					$result['error'][] = Mage::helper('sales')->__('Delivered disabled');
					echo json_encode($result);
					exit();
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
					$result['error'][] = Mage::helper('sales')->__('Check if at least one recepient is found');
					echo json_encode($result);
					exit();
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
					$result['error'][] = $exception;
					echo json_encode($result);
					exit();
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

				if(!$templateId){
					$result['error'][] = Mage::helper('sales')->__('Please select email template');
					echo json_encode($result);
					exit();
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

				if($mailer->send()){
					$result['success'][] = Mage::helper('sales')->__('Delivered email has been sent.');
				} else {
					$result['success'][] = Mage::helper('sales')->__('Delivered email not sent somthing went to wrong.');
				}

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
					if($user){
						$data['user'] = array(
							'userid'	=> $user->getUserId(),
							'username' => $user->getUsername(),
							'email' => $user->getEmail()
						);
					}
  
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

				$reponse = $this->postCampaign($shipment,$messageTemplateId,$campaignName,$consumers);
				if($reponse){
					$result['success'][] = Mage::helper('sales')->__('The shipment has been delivered.');
				} else {
					$result['error'][] = Mage::helper('sales')->__('Oops!!! Something went wrong, we could not sent the message.');
				}

			} catch (Mage_Core_Exception $e) {
				$result['error'][] = $e->getMessage();
			} catch (Exception $e) {
				$result['error'][] = Mage::helper('sales')->__('Cannot deliver shipment.');
			}
		} else {
			$result['error'][] = Mage::helper('sales')->__('Shipment not found.');
		}

		echo json_encode($result);
		exit();
    }
	
	
	/**
     * post Tracking Delete Hook 
     *
     * @param string Observer
     * @return array
	*/
	public function setTrackingDeleteWebhook(Varien_Event_Observer $observer){
		$event = $observer->getEvent();
		$track = $event->getTrack();
		$trackingId = $track->getNumber();
		$shipment = $track->getShipment();
		$order = $shipment->getOrder();
		$shippingDescription = $order->getShippingDescription();
		$webhookURL = Mage::getStoreConfig(self::XML_PATH_SHIPMENT_WEBHOOK_URL, $shipment->getStoreId());
		if($webhookURL){
			$adminSession 	= Mage::getSingleton('admin/session');
			$user 			= $adminSession->getUser();
			$data = array();
			$data['user'] = array(
				'userid'	=> $user->getUserId(),
				'username' => $user->getUsername(),
				'email' => $user->getEmail()
			);
			$data['action'] = "delete";
			$data["status_type"] = "Shipment";
			$data["increment_id"] = $shipment->getIncrementId();
			$data["order_increment_id"] = $order->getIncrementId();
			$data["shipping_description"] = $shippingDescription;
			$data["carriers"] = $track->getData();
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
	}
	
	
	/**
     * post LivePerson Campaign 
     *
     * @param string Order TemplateId CampaignName Variables
     * @return array
	*/
	public function postCampaign($order, $templateId = null, $campaignName = null, $variables = array()){
		if($templateId && !empty($variables)){
	
			$storeId = $order->getStoreId();
			$livePersonAccountId = Mage::getStoreConfig(self::XML_PATH_SALES_API_LIVEPERSON_ACCOUNT_ID, $order->getStoreId());
			$url = "https://proactive-messaging.z1.fs.liveperson.com/api/v2/account/{$livePersonAccountId}/campaign";
			$zendDate = Zend_Date::now()->setLocale(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE , $storeId))->setTimezone(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE , $storeId));
			$whatsappNumber = Mage::getStoreConfig('intenso/contact/whatsapp_contact_number', $storeId);
			$whatsappWP = str_replace("+", "", $whatsappNumber);
			$hour = date("G", strtotime($zendDate));
			$day = strtoUpper(date("l", strtotime($zendDate)));
			$date = date("Y-m-d H:i:s", strtotime($zendDate));
			$fromMinute = date("i", strtotime($zendDate));
			$endMinute = date("i", (strtotime($zendDate)+(60*2)));
			$rCampaignName = $campaignName . ' ' . $date;
			$outboundNumber = $whatsappWP;
			$windows = array();
			$windows[] = array(
				"startTime" => array(
					"hour" => $hour,
					"minute" => $fromMinute
				),
				"endTime" => array(
					'hour' => $hour,
					'minute' => $endMinute
				),
				'day' => "{$day}",
			);
			$schedulingMetrics = array(
				"windows" => $windows
			);
			$campaign = array(
				"campaignName" => $rCampaignName,
				"skill" => "WhatsApp",
				"templateId" => $templateId,
				"outboundNumber" => $outboundNumber,
				"consumers" => $variables,
				"schedulingMetrics" => $schedulingMetrics,
				'consent' => true,
			);
			$jsonCampaign = json_encode($campaign);
			if($auth = $this->getAuthToken($order)){
				try {
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonCampaign);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						"accept: application/json",
						"Authorization: {$auth}",
						"Content-Type: application/json"
						)
					);
					$response = curl_exec($ch);
					$err = curl_error($ch);
					curl_close($ch);
					$decode = Mage::helper('core')->jsonDecode($response);
					if(isset($decode['proactiveCampaignId']) && $decode['proactiveCampaignId']){
						$time = time();
						$proactiveCampaignId = $decode['proactiveCampaignId'];
						$output = shell_exec("php /usr/share/nginx/tejar/shell/msgresponse.php proactiveCampaignId {$proactiveCampaignId} auth {$auth} time {$time} object_type {$campaignName} object_id {$order->getId()} > /dev/null&");							
					}
			
					
					return $response;
				} catch (Mage_Core_Exception $e) {
					$this->_getSession()->addError($e->getMessage());
				} catch (Exception $e) {
					$this->_getSession()->addError($this->__('Unable to confirm the order.'));
					Mage::logException($e);
				}					
			} 
		} else {
			$response = $this->postMessage($order,$campaignName);
			return $response;
		}
	}
	
	/**
     * get LivePerson Auth 
     *
     * @param string Order
     * @return array
	*/
	protected function getAuthToken($order){
		$livePersonClientId = Mage::getStoreConfig(self::XML_PATH_SALES_API_LIVEPERSON_CLIENT_ID, $order->getStoreId());
		$livePersonClientSecret = Mage::getStoreConfig(self::XML_PATH_SALES_API_LIVEPERSON_CLIENT_SECRET, $order->getStoreId());
		if($livePersonClientId && $livePersonClientSecret){
			try {
				$clientSentine = "client_id={$livePersonClientId}&client_secret={$livePersonClientSecret}";
				$apiSentinelUrl = "https://va.sentinel.liveperson.net/sentinel/api/account/85633532/app/token?v=1.0&grant_type=client_credentials";
				$ch = curl_init($apiSentinelUrl);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $clientSentine);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/x-www-form-urlencoded'
					)
				);
				$response = curl_exec($ch);
				$err = curl_error($ch);
				curl_close($ch);
				if(!$err){
					$result = json_decode($response);
					return $result->access_token;
				} else {
					return null;
				}
			} catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Unable to get livePerson Auth.'));
                Mage::logException($e);
            }
		}
		return null;
	}
	
	/**
     * get LivePerson WhatsApp 
     *
     * @param string Auth ProactiveCampaignId Type ObjectId
     * @return array
	*/
	public function getResponseMessage($auth, $proactiveCampaignId, $type, $id){
		
		if($type == "Order" || $type == "Canceled" || $type == "Return Products" || $type == "Confirmation"){
			$order = Mage::getModel('sales/order')->load($id);
		} else if($type == "Invoice"){
			$order = Mage::getModel('sales/order_invoice')->load($id);
		} else if($type == "Shipment" || $type == "Delivered" || $type == "Pickup" || $type == "Out for Delivery"){
			$order = Mage::getModel('sales/order_shipment')->load($id);
		} else if($type == "Credit Memo"){
			$order = Mage::getModel('sales/order_creditmemo')->load($id);
		} else {
			$order = Mage::getModel('sales/order')->load($id);
		} 
		
		try {
			$billingAddress = $order->getBillingAddress();
			$telephone = $billingAddress->getData('telephone');
			$url = "https://proactive-messaging.z1.fs.liveperson.com/api/v2/account/85633532/campaign/{$proactiveCampaignId}/conversations";
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"accept: application/json",
				"Authorization: {$auth}",
				"Content-Type: application/json"
				)
			);
			$response = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);
			$decode = Mage::helper('core')->jsonDecode($response);		
			if($decode["campaignStatus"] != "FINISHED"){
				$this->postMessage($order,$type);
				Mage::log($telephone . " => " . $response, null, 'message.log');
			} else {
				Mage::log($telephone . " => " . $response, null, 'message.log');
			}
		} catch (Mage_Core_Exception $e) {
			$this->_getSession()->addError($e->getMessage());
		} catch (Exception $e) {
			$this->_getSession()->addError($this->__('Unable to confirm the order.'));
			Mage::logException($e);
		}
	}
	
	
	/**
     * post Twilio SMS 
     *
     * @param string Order Type
     * @return array
	*/
	public function postMessage($order,$type){
		
		if($type == "Order"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_ORDER_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Canceled"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_ORDER_CANCEL_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Return Products"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_RETURN_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Confirmation"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_EXTRA_CONFIRMATION_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Invoice"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_INVOICE_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Delivered"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_DELIVERED_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Shipment"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_SHIPMENT_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Pickup"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_PICKUP_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Picked up"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_PICKED_UP_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Out for Delivery"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_OUT_FOR_DELIVERY_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Outstanding Payment"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_OUTSTANDING_PAYMENT_MESSAGE_TEMPLATE, $order->getStoreId());
		} else if($type == "Credit Memo"){
			$templateText = Mage::getStoreConfig(self::XML_PATH_CREDITMEMO_MESSAGE_TEMPLATE, $order->getStoreId());
		} 
		
		$coreHelper = Mage::helper('core');
		$twilioNumber = Mage::getStoreConfig(self::XML_PATH_SALES_API_TWILIO_ACCOUNT_NUMBER, $order->getStoreId());
		$twilioAccountSID = Mage::getStoreConfig(self::XML_PATH_SALES_API_TWILIO_ACCOUNT_SID, $order->getStoreId());
		$twilioAccountSecret = Mage::getStoreConfig(self::XML_PATH_SALES_API_TWILIO_ACCOUNT_SECRET, $order->getStoreId());
		if($twilioNumber && $twilioAccountSID && $twilioAccountSecret && $templateText){
			try {
				$billingAddress = $order->getBillingAddress();
				$telephone = $billingAddress->getData('telephone');
				if($validateNumber = $coreHelper->validateNumber($telephone)){
					$emailTemplate = Mage::getModel('core/email_template');
					$variables = array(
						'order' => $order
					);
					
					if (!isset($variables['store'])) {
						$variables['store'] = Mage::app()->getStore($order->getStoreId());
					}
					$defaultValuesMap = array(
						"logo_width" => $emailTemplate::XML_PATH_DESIGN_EMAIL_LOGO_WIDTH,
						"logo_height" => $emailTemplate::XML_PATH_DESIGN_EMAIL_LOGO_HEIGHT,
						"phone" => Mage_Core_Model_Store::XML_PATH_STORE_STORE_PHONE,
						"store_phone" => Mage_Core_Model_Store::XML_PATH_STORE_STORE_PHONE,
						"store_hours" => Mage_Core_Model_Store::XML_PATH_STORE_STORE_HOURS,
						"store_email" => Mage_Customer_Helper_Data::XML_PATH_SUPPORT_EMAIL,
					);
					foreach ($defaultValuesMap as $variableName => $configValue) {
						if (!isset($variables[$variableName])) {
							$variables[$variableName] = Mage::getStoreConfig($configValue, $storeId);
						}
					}
					$processor = $emailTemplate->getTemplateFilter();
					$processor->setUseSessionInUrl(false)->setPlainTemplateMode(1);
					$processor->setTemplateProcessor(array($emailTemplate, 'getTemplateByConfigPath'))
					->setIncludeProcessor(array($emailTemplate, 'getInclude'))
					->setVariables($variables);
					$result = $processor->filter($templateText);
					$payload = array(
						"Body" => "{$result}",
						"MessagingServiceSid" => "{$twilioNumber}",
						"To" => $telephone
					);
					$TWILIO_ACCOUNT_SID = "{$twilioAccountSID}";
					$AUTH_TOKEN = "{$twilioAccountSecret} ";
					$url = "https://api.twilio.com/2010-04-01/Accounts/{$TWILIO_ACCOUNT_SID}/Messages.json";
					$ch = curl_init($url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_USERPWD, $TWILIO_ACCOUNT_SID . ':' . $AUTH_TOKEN);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						"Content-Type: application/x-www-form-urlencoded"
						)
					);
					$response = curl_exec($ch);
					$err = curl_error($ch);
					curl_close($ch);
					
					
					if($err){
						Mage::log($response . ' => ' . $telephone, null, 'message.log');
						return null;
					} else {
						return $response;	
					}
					
				}
				
			} catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Unable to confirm the order.'));
                Mage::logException($e);
            }
		} 
		
		return null;
	}

}
