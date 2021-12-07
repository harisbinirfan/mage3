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
 * Order creditmemo model
 *
 * @method Mage_Sales_Model_Resource_Order_Creditmemo _getResource()
 * @method Mage_Sales_Model_Resource_Order_Creditmemo getResource()
 * @method int getStoreId()
 * @method Mage_Sales_Model_Order_Creditmemo setStoreId(int $value)
 * @method float getAdjustmentPositive()
 * @method float getBaseShippingTaxAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseShippingTaxAmount(float $value)
 * @method float getStoreToOrderRate()
 * @method Mage_Sales_Model_Order_Creditmemo setStoreToOrderRate(float $value)
 * @method float getBaseDiscountAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseDiscountAmount(float $value)
 * @method float getBaseToOrderRate()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseToOrderRate(float $value)
 * @method float getGrandTotal()
 * @method Mage_Sales_Model_Order_Creditmemo setGrandTotal(float $value)
 * @method float getBaseAdjustmentNegative()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseAdjustmentNegative(float $value)
 * @method float getBaseSubtotalInclTax()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseSubtotalInclTax(float $value)
 * @method float getShippingAmount()
 * @method float getSubtotalInclTax()
 * @method Mage_Sales_Model_Order_Creditmemo setSubtotalInclTax(float $value)
 * @method float getAdjustmentNegative()
 * @method float getBaseShippingAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseShippingAmount(float $value)
 * @method float getStoreToBaseRate()
 * @method Mage_Sales_Model_Order_Creditmemo setStoreToBaseRate(float $value)
 * @method float getBaseToGlobalRate()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseToGlobalRate(float $value)
 * @method float getBaseAdjustment()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseAdjustment(float $value)
 * @method float getBaseSubtotal()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseSubtotal(float $value)
 * @method float getDiscountAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setDiscountAmount(float $value)
 * @method float getSubtotal()
 * @method Mage_Sales_Model_Order_Creditmemo setSubtotal(float $value)
 * @method float getAdjustment()
 * @method Mage_Sales_Model_Order_Creditmemo setAdjustment(float $value)
 * @method float getBaseGrandTotal()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseGrandTotal(float $value)
 * @method float getBaseAdjustmentPositive()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseAdjustmentPositive(float $value)
 * @method float getBaseTaxAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseTaxAmount(float $value)
 * @method float getShippingTaxAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setShippingTaxAmount(float $value)
 * @method float getTaxAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setTaxAmount(float $value)
 * @method int getOrderId()
 * @method Mage_Sales_Model_Order_Creditmemo setOrderId(int $value)
 * @method int getEmailSent()
 * @method Mage_Sales_Model_Order_Creditmemo setEmailSent(int $value)
 * @method int getCreditmemoStatus()
 * @method Mage_Sales_Model_Order_Creditmemo setCreditmemoStatus(int $value)
 * @method int getState()
 * @method Mage_Sales_Model_Order_Creditmemo setState(int $value)
 * @method int getShippingAddressId()
 * @method Mage_Sales_Model_Order_Creditmemo setShippingAddressId(int $value)
 * @method int getBillingAddressId()
 * @method Mage_Sales_Model_Order_Creditmemo setBillingAddressId(int $value)
 * @method int getInvoiceId()
 * @method Mage_Sales_Model_Order_Creditmemo setInvoiceId(int $value)
 * @method string getCybersourceToken()
 * @method Mage_Sales_Model_Order_Creditmemo setCybersourceToken(string $value)
 * @method string getStoreCurrencyCode()
 * @method Mage_Sales_Model_Order_Creditmemo setStoreCurrencyCode(string $value)
 * @method string getOrderCurrencyCode()
 * @method Mage_Sales_Model_Order_Creditmemo setOrderCurrencyCode(string $value)
 * @method string getBaseCurrencyCode()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseCurrencyCode(string $value)
 * @method string getGlobalCurrencyCode()
 * @method Mage_Sales_Model_Order_Creditmemo setGlobalCurrencyCode(string $value)
 * @method string getTransactionId()
 * @method Mage_Sales_Model_Order_Creditmemo setTransactionId(string $value)
 * @method string getIncrementId()
 * @method Mage_Sales_Model_Order_Creditmemo setIncrementId(string $value)
 * @method string getCreatedAt()
 * @method Mage_Sales_Model_Order_Creditmemo setCreatedAt(string $value)
 * @method string getUpdatedAt()
 * @method Mage_Sales_Model_Order_Creditmemo setUpdatedAt(string $value)
 * @method float getHiddenTaxAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setHiddenTaxAmount(float $value)
 * @method float getBaseHiddenTaxAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseHiddenTaxAmount(float $value)
 * @method float getShippingHiddenTaxAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setShippingHiddenTaxAmount(float $value)
 * @method float getBaseShippingHiddenTaxAmount()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseShippingHiddenTaxAmount(float $value)
 * @method float getShippingInclTax()
 * @method Mage_Sales_Model_Order_Creditmemo setShippingInclTax(float $value)
 * @method float getBaseShippingInclTax()
 * @method Mage_Sales_Model_Order_Creditmemo setBaseShippingInclTax(float $value)
 *
 * @category    Mage
 * @package     Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Sales_Model_Order_Creditmemo extends Mage_Sales_Model_Order_Creditmemo
{

	const XML_PATH_REPLY_TO_EMAIL        			   = 'trans_email/ident_custom4/email';
    /**
     * Send email with creditmemo data
     *
     * @param boolean $notifyCustomer
     * @param string $comment
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    public function sendEmail($notifyCustomer = true, $comment = '')
    {
        $order = $this->getOrder();
        $storeId = $order->getStore()->getId();

        if (!Mage::helper('sales')->canSendNewCreditmemoEmail($storeId)) {
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

        // Retrieve corresponding email template id and customer name
        if ($order->getCustomerIsGuest()) {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $storeId);
            $customerName = $order->getBillingAddress()->getName();
        } else {
            $templateId = Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE, $storeId);
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

        $orderMessage = null;
    		foreach ($order->getAllItems() as $_item){
    			if($_item->getParentItem()) continue;
    			$product = Mage::getModel('catalog/product')->load($_item->getProductId());
    			if(!$product->getStockItem()->getIsInStock()){
    				$orderMessage = 1;
    			}
    		}

        // Set all required params and send emails
        $mailer->setSender(Mage::getStoreConfig(self::XML_PATH_EMAIL_IDENTITY, $storeId));
        $mailer->setStoreId($storeId);
		$mailer->setReplyTo($replyTo);
        $mailer->setTemplateId($templateId);
        $mailer->setTemplateParams(array(
                'order'        => $order,
                'creditmemo'   => $this,
                'comment'      => $comment,
                'billing'      => $order->getBillingAddress(),
                'payment_html' => $paymentBlockHtml,
                'order_message' => $orderMessage
            )
        );

        $mailer->send();
        $this->setEmailSent(true);
        $this->_getResource()->saveAttribute($this, 'email_sent');

        return $this;
    }

    /**
     * Send email with creditmemo update information
     *
     * @param boolean $notifyCustomer
     * @param string $comment
     * @return Mage_Sales_Model_Order_Creditmemo
     */
    public function sendUpdateEmail($notifyCustomer = true, $comment = '')
    {
        $order = $this->getOrder();
        $storeId = $order->getStore()->getId();

        if (!Mage::helper('sales')->canSendCreditmemoCommentEmail($storeId)) {
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
                'order'      => $order,
                'creditmemo' => $this,
                'comment'    => $comment,
                'billing'    => $order->getBillingAddress()
            )
        );
        $mailer->send();

        return $this;
    }


}
