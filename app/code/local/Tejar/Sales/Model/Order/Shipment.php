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
 * Sales order shipment model
 *
 * @method Mage_Sales_Model_Resource_Order_Shipment _getResource()
 * @method Mage_Sales_Model_Resource_Order_Shipment getResource()
 * @method int getStoreId()
 * @method Mage_Sales_Model_Order_Shipment setStoreId(int $value)
 * @method float getTotalWeight()
 * @method Mage_Sales_Model_Order_Shipment setTotalWeight(float $value)
 * @method float getTotalQty()
 * @method Mage_Sales_Model_Order_Shipment setTotalQty(float $value)
 * @method int getEmailSent()
 * @method Mage_Sales_Model_Order_Shipment setEmailSent(int $value)
 * @method int getOrderId()
 * @method Mage_Sales_Model_Order_Shipment setOrderId(int $value)
 * @method int getCustomerId()
 * @method Mage_Sales_Model_Order_Shipment setCustomerId(int $value)
 * @method int getShippingAddressId()
 * @method Mage_Sales_Model_Order_Shipment setShippingAddressId(int $value)
 * @method int getBillingAddressId()
 * @method Mage_Sales_Model_Order_Shipment setBillingAddressId(int $value)
 * @method int getShipmentStatus()
 * @method Mage_Sales_Model_Order_Shipment setShipmentStatus(int $value)
 * @method string getIncrementId()
 * @method Mage_Sales_Model_Order_Shipment setIncrementId(string $value)
 * @method string getCreatedAt()
 * @method Mage_Sales_Model_Order_Shipment setCreatedAt(string $value)
 * @method string getUpdatedAt()
 * @method Mage_Sales_Model_Order_Shipment setUpdatedAt(string $value)
 *
 * @category    Mage
 * @package     Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Sales_Model_Order_Shipment extends Mage_Sales_Model_Order_Shipment
{
    const XML_PATH_PICKUP_EMAIL_ENABLED              		     = 'sales_email/pickup/enabled';
    const XML_PATH_PICKUP_EMAIL_TEMPLATE               = 'sales_email/pickup/template';
	const XML_PATH_PICKUP_EMAIL_GUEST_TEMPLATE         = 'sales_email/pickup/guest_template';
    const XML_PATH_PICKUP_IDENTITY_CODE            	    	= 'sales_email/pickup/identity_code';
	const XML_PATH_REPLY_TO_EMAIL        			   = 'trans_email/ident_custom4/email';
    const XML_PATH_OUT_FOR_DELIVERY_EMAIL_ENABLED                = 'sales_email/out_for_delivery/enabled';
    const XML_PATH_OUT_FOR_DELIVERY_EMAIL_TEMPLATE               = 'sales_email/out_for_delivery/template';
	const XML_PATH_OUT_FOR_DELIVERY_EMAIL_GUEST_TEMPLATE         = 'sales_email/out_for_delivery/guest_template';
    const XML_PATH_OUT_FOR_DELIVERY_IDENTITY_CODE  			= 'sales_email/out_for_delivery/identity_code';
	
    public function sendEmail($notifyCustomer = true, $comment = '')
    {
        $order = $this->getOrder();
        $storeId = $order->getStore()->getId();

        if (!Mage::helper('sales')->canSendNewShipmentEmail($storeId)) {
            return $this;
        }
        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_EMAIL_COPY_METHOD, $storeId);
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

        $identityCodePickup = Mage::getStoreConfig(self::XML_PATH_PICKUP_IDENTITY_CODE, $this->getStoreId());
		$identityCodeOutForDelivery = Mage::getStoreConfig(self::XML_PATH_OUT_FOR_DELIVERY_IDENTITY_CODE, $this->getStoreId());

        // Retrieve corresponding email template id and customer name
        $isOutOfDelivery = false;
		$isStorePickup   = false;
        $otherTrackingCode = false;
		$allTracks = $this->getAllTracks();
		if(!empty($allTracks)){
			foreach($allTracks as $track){
				if($track->getCarrierCode() == "custom" && $track->getTitle() == $identityCodeOutForDelivery){
					$isOutOfDelivery = true;
				} else if($track->getCarrierCode() == "custom" && $track->getTitle() == $identityCodePickup){
					$isStorePickup = true;
				} else {
                    $otherTrackingCode = true;
                }
			}
		}
		// 3SD CODE CHECK SHIPPING METHOD IS CUSTOM PICKUP METHOD
        $canSendPickup = Mage::getStoreConfig(self::XML_PATH_PICKUP_EMAIL_ENABLED, $storeId);
		$canSendOutForDelivery = Mage::getStoreConfig(self::XML_PATH_OUT_FOR_DELIVERY_EMAIL_ENABLED, $storeId);
        if($canSendOutForDelivery && $isOutOfDelivery) {
			if ($order->getCustomerIsGuest()) {
				$templateId = Mage::getStoreConfig(self::XML_PATH_OUT_FOR_DELIVERY_EMAIL_GUEST_TEMPLATE, $storeId);
				$customerName = $order->getBillingAddress()->getName();
			} else {
				$templateId = Mage::getStoreConfig(self::XML_PATH_OUT_FOR_DELIVERY_EMAIL_TEMPLATE, $storeId);
				$customerName = $order->getCustomerName();
			}
        } else if(($canSendPickup && (trim($order->getShippingMethod()) == 'customshippingmethod_customshippingmethod') && $otherTrackingCode == false) || ($isStorePickup)){
			if ($order->getCustomerIsGuest()) {
				$templateId = Mage::getStoreConfig(self::XML_PATH_PICKUP_EMAIL_GUEST_TEMPLATE, $storeId);
				$customerName = $order->getBillingAddress()->getName();
			} else {
				$templateId = Mage::getStoreConfig(self::XML_PATH_PICKUP_EMAIL_TEMPLATE, $storeId);
				$customerName = $order->getCustomerName();
			}
		} else {
			if ($order->getCustomerIsGuest()) {
				$templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
				$customerName = $order->getBillingAddress()->getName();
			} else {
				$templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
				$customerName = $order->getCustomerName();
			}
		}

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
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
		$mailer->setReplyTo($replyTo);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'order'        => $order,
                'shipment'     => $this,
                'comment'      => $comment,
                'billing'      => $order->getBillingAddress(),
                'payment_html' => $paymentBlockHtml
            )
        );
		
        $mailer->send();
        return $this;
    }
	
	
	/**
     * Send email with shipment update information
     *
     * @param boolean $notifyCustomer
     * @param string $comment
     * @return Mage_Sales_Model_Order_Shipment
     */
    public function sendUpdateEmail($notifyCustomer = true, $comment = '')
    {
        $order = $this->getOrder();
        $storeId = $order->getStore()->getId();

        if (!Mage::helper('sales')->canSendShipmentCommentEmail($storeId)) {
            return $this;
        }
        // Get the destination email addresses to send copies to
        $copyTo = $this->_getEmails(self::XML_PATH_UPDATE_EMAIL_COPY_TO);
        $copyMethod = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_COPY_METHOD, $storeId);
		$replyTo = Mage::getStoreConfig(self::XML_PATH_REPLY_TO_EMAIL, $storeId);
        // Check if at least one recepient is found
        if (!$notifyCustomer && !$copyTo) {
            return $this;
        }

        // Retrieve corresponding email template id and customer name
        if ($order->getCustomerIsGuest()) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_GUEST_TEMPLATE, $storeId);
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $templateId = Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_TEMPLATE, $storeId);
            $customerName = $order->getCustomerName();
        }

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
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_UPDATE_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
		$mailer->setReplyTo($replyTo);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'order'    => $order,
                'shipment' => $this,
                'comment'  => $comment,
                'billing'  => $order->getBillingAddress()
            )
        );
        $mailer->send();

        return $this;
    }

   
}
