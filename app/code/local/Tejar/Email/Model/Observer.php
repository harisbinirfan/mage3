<?php

class Tejar_Email_Model_Observer extends Mage_Sales_Model_Order
{

	const XML_PATH_RATING_EMAIL_TEMPLATE               = 'sales_email/review_rating/template';
	const XML_PATH_RATING_EMAIL_GUEST_TEMPLATE         = 'sales_email/review_rating/guest_template';
	const XML_PATH_RATING_EMAIL_IDENTITY               = 'sales_email/review_rating/identity';
	const XML_PATH_RATING_EMAIL_COPY_TO                = 'sales_email/review_rating/copy_to';
	const XML_PATH_RATING_EMAIL_COPY_METHOD            = 'sales_email/review_rating/copy_method';
	const XML_PATH_RATING_EMAIL_ENABLED                = 'sales_email/review_rating/enabled';

	const XML_PATH_DELAYED_EMAIL_TEMPLATE               = 'sales_email/delayed/template';
	const XML_PATH_DELAYED_EMAIL_GUEST_TEMPLATE        	= 'sales_email/delayed/guest_template';
	const XML_PATH_DELAYED_EMAIL_IDENTITY               = 'sales_email/delayed/identity';
	const XML_PATH_DELAYED_EMAIL_COPY_TO                = 'sales_email/delayed/copy_to';
	const XML_PATH_DELAYED_EMAIL_COPY_METHOD            = 'sales_email/delayed/copy_method';
	const XML_PATH_DELAYED_EMAIL_ENABLED                = 'sales_email/delayed/enabled';
	const XML_PATH_REPLY_TO_EMAIL        		 	  = 'trans_email/ident_custom4/email';


		 /**
	 * Retrieve store model instance
	 *
	 * @return Mage_Core_Model_Store
	 */
	public function getStore()
	{
		$storeId = Mage::app()->getStore()->getStoreId();
		if ($storeId) {
			return Mage::app()->getStore($storeId);
		}
		return Mage::app()->getStore();
	}



	protected function _getEmails($configPath)
	{
			$data = Mage::getStoreConfig($configPath, $this->getStoreId());
			if (!empty($data)) {
					return explode(',', $data);
			}
			return false;
	}




	public function sendReviewEmails()
    {
		$shipmentCollections  = Mage::getSingleton('sales/order_shipment')->getCollection()->addFieldToFilter('order_id', array('neq' => null))->load();

		foreach($shipmentCollections as $_shipment){

			$order = $_shipment->getOrder();
			$storeId = $order->getStore()->getId();
			$this->setStoreId($storeId);
			$enable = Mage::getStoreConfig('sales_email/review_rating/enabled' , $storeId);
			$dayLimits = Mage::getStoreConfig('sales_email/review_rating/days_limit', $storeId);
			$currentTime = strtotime(Zend_Date::now()->setLocale(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE , $storeId))->setTimezone(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE , $storeId)));
			$maxOrderTime = $this->getMaxOrderTime($_shipment , $dayLimits);


			if($enable == 1) {

				if($currentTime >= max($maxOrderTime) && $_shipment->getSentRatingEmail() == 0){

					// Get the destination email addresses to send copies to
					$copyTo =  $this->_getEmails(self::XML_PATH_RATING_EMAIL_COPY_TO);
					$copyMethod = Mage::getStoreConfig(self::XML_PATH_RATING_EMAIL_COPY_METHOD, $storeId);
					$replyTo = Mage::getStoreConfig(self::XML_PATH_REPLY_TO_EMAIL, $storeId);
					$mailer           = Mage::getModel('core/email_template_mailer');
					$emailInfo        = Mage::getModel('core/email_info');

					$emailInfo->addTo((string)$order->getCustomerEmail(), (string)$order->getCustomerName());

					if ($copyTo && $copyMethod == 'bcc') {
						// Add bcc to customer email
						foreach ($copyTo as $email) {
							$emailInfo->addBcc($email);
						}
					}

					$mailer->addEmailInfo($emailInfo);

					// Email copies are sent as separated emails if their copy method is 'copy'
					if ($copyTo && $copyMethod == 'copy') {
						foreach ($copyTo as $email) {
							$emailInfo = Mage::getModel('core/email_info');
							$emailInfo->addTo($email);
							$mailer->addEmailInfo($emailInfo);
						}
					}

					 // Retrieve corresponding email template id and customer name
					 if ($order->getCustomerIsGuest()) {
						 $templateId = Mage::getStoreConfig(self::XML_PATH_RATING_EMAIL_GUEST_TEMPLATE, $storeId);
						 $customerName = $order->getBillingAddress()->getName();
					 } else {
						 $templateId = Mage::getStoreConfig(self::XML_PATH_RATING_EMAIL_TEMPLATE, $storeId);
						 $customerName = $order->getCustomerName();
					 }

					// $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
					//--- If no template is found return.. Do nothing.
					if(!$templateId){
						return;
					}

					//--- Set all required params and send emails
					$mailer->setSender(Mage::getStoreConfig(self::XML_PATH_RATING_EMAIL_IDENTITY, $storeId));
					$mailer->setStoreId($storeId);
					$mailer->setReplyTo($replyTo);
					$mailer->setTemplateId($templateId);
					$mailer->setTemplateParams(array(
						'order'        => $order,
						'shipment'     => $_shipment
						)
					);

					try{

						 $mailer->send();

					 }catch(Exception $exception) {

						 Mage::log($e, null, 'email.log');
						throw $exception;
					}

					// $_shipment->setSentRatingEmail(1)->save();

					$resource = Mage::getSingleton('core/resource');
					$writeConnection = $resource->getConnection('core_write');
					$writeConnection->update(
						$_shipment->getResource()->getMainTable(),
						array("sent_rating_email" => 1),
						"entity_id={$_shipment->getId()}"
					);

				}
			}

		}

		//===================================== Logic to go into OBSERVER =====================================//
	}


	public function sendDelayEmails()
    {
		$invoiceCollections  = Mage::getSingleton('sales/order_invoice')->getCollection();
		$invoiceCollections = $invoiceCollections->addFieldToFilter('order_id', array('neq' => null))->load();

		foreach($invoiceCollections as $_invoice){

			$order = $_invoice->getOrder();
			$_order = Mage::getModel('sales/order')->load($order->getId());
			$storeId = $order->getStore()->getId();
			$this->setStoreId($storeId);
			$enable = Mage::getStoreConfig('sales_email/delayed/enabled', $storeId);
			$dayLimits = Mage::getStoreConfig('sales_email/delayed/days_limit', $order->getStore()->getId());
			$currentTime = strtotime(Zend_Date::now()->setLocale(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE , $storeId))->setTimezone(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE , $storeId)));
			$maxOrderTime = $this->getMaxOrderTime($_invoice , $dayLimits);

			if($enable == 1) {

				if($currentTime >= max($maxOrderTime) && $order->getStatus()=="processing" && $_invoice->getSentDelayEmail()== 0){

					// Get the destination email addresses to send copies to
					$copyTo = $this->_getEmails(self::XML_PATH_DELAYED_EMAIL_COPY_TO);
					$copyMethod = Mage::getStoreConfig(self::XML_PATH_DELAYED_EMAIL_COPY_METHOD, $storeId);
					$replyTo = Mage::getStoreConfig(self::XML_PATH_REPLY_TO_EMAIL, $storeId);
					$mailer    = Mage::getModel('core/email_template_mailer');
					$emailInfo = Mage::getModel('core/email_info');

					$emailInfo->addTo((string)$order->getCustomerEmail(), (string)$order->getCustomerName());

					if ($copyTo && $copyMethod == 'bcc') {
						// Add bcc to customer email
						foreach ($copyTo as $email) {
							$emailInfo->addBcc($email);
						}
					}

					$mailer->addEmailInfo($emailInfo);

					// Email copies are sent as separated emails if their copy method is 'copy'
					if ($copyTo && $copyMethod == 'copy') {
						foreach ($copyTo as $email) {
							$emailInfo = Mage::getModel('core/email_info');
							$emailInfo->addTo($email);
							$mailer->addEmailInfo($emailInfo);
						}
					}

					 // Retrieve corresponding email template id and customer name
					 if ($order->getCustomerIsGuest()) {
						 $templateId = Mage::getStoreConfig(self::XML_PATH_DELAYED_EMAIL_GUEST_TEMPLATE, $storeId);
						 $customerName = $order->getBillingAddress()->getName();
					 } else {
						 $templateId = Mage::getStoreConfig(self::XML_PATH_DELAYED_EMAIL_TEMPLATE, $storeId);
						 $customerName = $order->getCustomerName();
					 }

					// $templateId = Mage::getStoreConfig(self::XML_PATH_DELAYED_EMAIL_TEMPLATE, $storeId);
					//--- If no template is found return.. Do nothing.
					if(!$templateId){
						return;
					}

					//--- Set all required params and send emails
					$mailer->setSender(Mage::getStoreConfig(self::XML_PATH_DELAYED_EMAIL_IDENTITY, $storeId));
					$mailer->setStoreId($storeId);
					$mailer->setReplyTo($replyTo);
					$mailer->setTemplateId($templateId);
					$mailer->setTemplateParams(array(
							'order'        => $order,
							'invoice'     => $_invoice
						)
					);
					try{

						$mailer->send();

					 }catch(Exception $exception) {
						 Mage::log($e, null, 'email.log');
						throw $exception;
					}

					//--- Set Sent Order Delay to 1
					$_invoice->setSentDelayEmail(1)->save();


				}
			}
		}
	}

		/*
	*@name getMaxOrderTime
	*@params $order
	*@desc
	*/
	public function getMaxOrderTime($order , $limit){
			$orderDelayDaysLimit = $limit;
			$storeWeekEnds       = explode(',', Mage::getStoreConfig('general/locale/weekend', $order->getStore()));
			$storeWeekEndsText   = array();
			$returnArray         = array();

			$daysOfWeek    = array();
			$daysOfWeek[0] = "Sun";
			$daysOfWeek[1] = "Mon";
			$daysOfWeek[2] = "Tue";
			$daysOfWeek[3] = "Wed";
			$daysOfWeek[4] = "Thu";
			$daysOfWeek[5] = "Fri";
			$daysOfWeek[6] = "Sat";

			foreach($storeWeekEnds as $sw){

				array_push($storeWeekEndsText, $daysOfWeek[$sw]);
			}
			$milliSeconds = 0 ;
			//--- initialize day count
			$j = (int) $orderDelayDaysLimit;
			$i = 0;
			while($j>=0){
				//echo "ddd";die;
				$day = date('D',strtotime($order->getCreatedAtStoreDate())+$milliSeconds);
				if(!in_array($day, $storeWeekEndsText)){
					$returnArray[$i] = strtotime(date('Y-M-d D h:i:s A',strtotime($order->getCreatedAtStoreDate())+$milliSeconds));
					$j--;
				}
				$milliSeconds += 3600*24;
				$i++;
			}

			return $returnArray;
	}
}
