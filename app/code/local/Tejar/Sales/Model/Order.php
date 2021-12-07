<?php

class Tejar_Sales_Model_Order extends Mage_Sales_Model_Order
{

//---------------------------------------------ZEE CODE -------------------------------------------//

	const XML_PATH_CANCEL_EMAIL_TEMPLATE               = 'sales_email/order_cancel/template';
	const XML_PATH_CANCEL_EMAIL_GUEST_TEMPLATE         = 'sales_email/order_cancel/guest_template';
	const XML_PATH_CANCEL_EMAIL_IDENTITY               = 'sales_email/order_cancel/identity';
    const XML_PATH_CANCEL_EMAIL_COPY_TO                = 'sales_email/order_cancel/copy_to';
	const XML_PATH_CANCEL_EMAIL_COPY_METHOD            = 'sales_email/order_cancel/copy_method';
	const XML_PATH_CANCEL_EMAIL_ENABLED                = 'sales_email/order_cancel/enabled';
	const XML_PATH_REPLY_TO_EMAIL        			   = 'trans_email/ident_custom4/email';


	/***
     * Queue email with new order data
     *
     * @param bool $forceMode if true then email will be sent regardless of the fact that it was already sent previously
     *
     * @return Mage_Sales_Model_Order
     * @throws Exception
     */
    public function queueNewOrderEmail($forceMode = false)
    {
        $storeId = $this->getStore()->getId();

        if (!Mage::helper('sales')->canSendNewOrderEmail($storeId)) {
            return $this;
        }

        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);
		$replyTo = Mage::getStoreConfig(self::XML_PATH_REPLY_TO_EMAIL, $storeId);
        // Start store emulation process
        /** @var $appEmulation Mage_Core_Model_App_Emulation */
        $appEmulation = Mage::getSingleton('core/app_emulation');
        $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);

        try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
            $paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())
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

        // Retrieve corresponding email template id and customer name
        if ($this->getCustomerIsGuest()) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
            $customerName = $this->getBillingAddress()->getName();
        } else {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
            $customerName = $this->getCustomerName();
        }

        /** @var $mailer Mage_Core_Model_Email_Template_Mailer */
        $mailer = Mage::getModel('core/email_template_mailer');
        /** @var $emailInfo Mage_Core_Model_Email_Info */
        $emailInfo = Mage::getModel('core/email_info');
        $emailInfo->addTo($this->getCustomerEmail(), $customerName);
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

        $isWarrantyClaim = null;
		foreach ($this->getAllItems() as $_item){
			if($_item->getParentItem()) continue;
			if($_item->getProductId() == 72980){
				$isWarrantyClaim = true;
			}
		}
        $isOrderNotice = "";
		if(!Mage::Helper('core')->isStoreOpen($this->getStore())){
			$isOrderNotice = true;
		}

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
		$mailer->setReplyTo($replyTo);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
            'order'        => $this,
            'billing'      => $this->getBillingAddress(),
            'payment_html' => $paymentBlockHtml,
            'is_warranty_claim' => $isWarrantyClaim,
            'is_order_notice' => $isOrderNotice
        ));

        /** @var $emailQueue Mage_Core_Model_Email_Queue */
        $emailQueue = Mage::getModel('core/email_queue');
        $emailQueue->setEntityId($this->getId())
            ->setEntityType(self::ENTITY)
            ->setEventType(self::EMAIL_EVENT_NAME_NEW_ORDER)
            ->setIsForceCheck(!$forceMode);

        $mailer->setQueue($emailQueue)->send();

        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');

        return $this;
    }



	 /**
     * Queue email with order update information
     *
     * @param boolean $notifyCustomer
     * @param string $comment
     * @param bool $forceMode if true then email will be sent regardless of the fact that it was already sent previously
     *
     * @return Mage_Sales_Model_Order
     */
    public function queueOrderUpdateEmail($notifyCustomer = true, $comment = '', $forceMode = false)
    {
        $storeId = $this->getStore()->getId();

        if (!Mage::helper('sales')->canSendOrderCommentEmail($storeId)) {
            return $this;
        }
        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_UPDATE_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_COPY_METHOD, $storeId);
		$replyTo = Mage::getStoreConfig(self::XML_PATH_REPLY_TO_EMAIL, $storeId);
        // Check if at least one recipient is found
        if (!$notifyCustomer && !$copyTo) {
            return $this;
        }

        // Retrieve corresponding email template id and customer name
        if ($this->getCustomerIsGuest()) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE, $storeId);
            $customerName = $this->getBillingAddress()->getName();
        } else {
            $templateId = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_TEMPLATE, $storeId);
            $customerName = $this->getCustomerName();
        }

        /** @var $mailer Mage_Core_Model_Email_Template_Mailer */
        $mailer = Mage::getModel('core/email_template_mailer');
        if ($notifyCustomer) {
            /** @var $emailInfo Mage_Core_Model_Email_Info */
            $emailInfo = Mage::getModel('core/email_info');
            $emailInfo->addTo($this->getCustomerEmail(), $customerName);
            if ($copyTo && $copyMethod == 'bcc') {
                // Add bcc to customer email
                foreach ($copyTo as $email) {
                    $emailInfo->addBcc($email);
                }
            }
            $mailer->addEmailInfo($emailInfo);
        }

        // Email copies are sent as separated emails if their copy method is
        // 'copy' or a customer should not be notified
        if ($copyTo && ($copyMethod == 'copy' || !$notifyCustomer)) {
            foreach ($copyTo as $email) {
                $emailInfo = Mage::getModel('core/email_info');
                $emailInfo->addTo($email);
                $mailer->addEmailInfo($emailInfo);
            }
        }

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
		$mailer->setReplyTo($replyTo);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'order'   => $this,
                'comment' => $comment,
                'billing' => $this->getBillingAddress()
            )
        );

        /** @var $emailQueue Mage_Core_Model_Email_Queue */
        $emailQueue = Mage::getModel('core/email_queue');
        $emailQueue->setEntityId($this->getId())
            ->setEntityType(self::ENTITY)
            ->setEventType(self::EMAIL_EVENT_NAME_UPDATE_ORDER)
            ->setIsForceCheck(!$forceMode);
        $mailer->setQueue($emailQueue)->send();

        return $this;
    }


	/**
     * Cancel order
     *
     * @return Mage_Sales_Model_Order
     */
    public function cancel()
    {
        if($this->canCancel()){
            $this->getPayment()->cancel();
            $this->registerCancellation();

            Mage::dispatchEvent('order_cancel_after', array('order' => $this));

			//--- Sending Order Cancellation Email to User..
			$this->sendCancelEmail($this);
        }

        return $this;
    }


	/**
     * Send Cancel order Email
     * @name SendCancelEmail
     * @return sendmail object
     */
	function sendCancelEmail($order){

		//--- Sending Order Cancellation Email to User..
		$storeId = $this->getStore()->getId();

		$enable = Mage::getStoreConfig('sales_email/order_cancel/enabled' , $storeId);
		if($enable == 1) {

			// Get the destination email addresses to send copies to
			$copyTo = $this->_getEmails(self::XML_PATH_CANCEL_EMAIL_COPY_TO);
			$copyMethod = Mage::getStoreConfig(self::XML_PATH_CANCEL_EMAIL_COPY_METHOD, $storeId);
			$replyTo = Mage::getStoreConfig(self::XML_PATH_REPLY_TO_EMAIL, $storeId);

			 // Retrieve corresponding email template id and customer name
			if ($this->getCustomerIsGuest()) {
				$templateId = Mage::getStoreConfig(self::XML_PATH_CANCEL_EMAIL_GUEST_TEMPLATE, $storeId);
				$customerName = $this->getBillingAddress()->getName();
			} else {
				$templateId = Mage::getStoreConfig(self::XML_PATH_CANCEL_EMAIL_TEMPLATE, $storeId);
				$customerName = $this->getCustomerName();
			}

			//--- If no template is found return.. Do nothing.
			 if(!$templateId){
				 return;
			 }

			$zendDate = Zend_Date::now()->setLocale(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE , $storeId))->setTimezone(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE , $storeId));
			$CurrentDate = Mage::helper('core')->formatDate($zendDate , 'long', true);

            // Start store emulation process
            $appEmulation = Mage::getSingleton('core/app_emulation');
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($storeId);
            try {
            // Retrieve specified view block from appropriate design package (depends on emulated store)
                $paymentBlock = Mage::helper('payment')->getInfoBlock($this->getPayment())
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
			$mailer = Mage::getModel('core/email_template_mailer');
			// $customerName = $order->getBillingAddress()->getName();

			$emailInfo = Mage::getModel('core/email_info');
			$emailInfo->addTo($order->getCustomerEmail(), $customerName);

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

      $orderMessage = null;
			foreach ($order->getAllItems() as $_item){
				if($_item->getParentItem()) continue;
				$product = Mage::getModel('catalog/product')->load($_item->getProductId());
				// var_dump($product->isSaleable());
				if(!$product->getStockItem()->getIsInStock()){
					$orderMessage = 1;
				}
			}

			  // Set all required params and send emails
			$mailer->setSender(Mage::getStoreConfig(self::XML_PATH_CANCEL_EMAIL_IDENTITY, $storeId));
			$mailer->setStoreId($storeId);
			$mailer->setReplyTo($replyTo);
			$mailer->setTemplateId($templateId);
			$mailer->setTemplateParams(array(
					'order'        => $order,
					'invoice'      => $this,
					'cancel_date'  => $CurrentDate,
					'billing'      => $order->getBillingAddress(),
					'payment_html' => $paymentBlockHtml,
          'order_message' => $orderMessage
				)
			);

			$mailer->send();
		}
	}


//---------------------------------------------ZEE CODE -------------------------------------------//
}
