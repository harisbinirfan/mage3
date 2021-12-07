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
 * Adminhtml sales orders controller
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Adminhtml/controllers/Sales/OrderController.php';
class Tejar_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{
	const XML_PATH_EXTRA_TEMPLATE_ID           	= 'sales_email/extra/confirmation_template_id';
	const XML_PATH_EXTRA_DELIVERED_TEMPLATE_ID  = 'sales_email/extra/delivered_template_id';
	const XML_PATH_TEMPLATE_ID    				= 'sales_email/return/template_id';
	const XML_PATH_EMAIL_TEMPLATE               = 'sales_email/return/template';
    const XML_PATH_EMAIL_GUEST_TEMPLATE         = 'sales_email/return/guest_template';
    const XML_PATH_EMAIL_IDENTITY               = 'sales_email/return/identity';
    const XML_PATH_EMAIL_COPY_TO                = 'sales_email/return/copy_to';
    const XML_PATH_EMAIL_COPY_METHOD            = 'sales_email/return/copy_method';
    const XML_PATH_EMAIL_ENABLED                = 'sales_email/return/enabled';
	
	const XML_PATH_RECEIVED_TEMPLATE_ID    				= 'sales_email/received/template_id';
	const XML_PATH_RECEIVED_EMAIL_TEMPLATE               = 'sales_email/received/template';
    const XML_PATH_RECEIVED_EMAIL_GUEST_TEMPLATE         = 'sales_email/received/guest_template';
    const XML_PATH_RECEIVED_EMAIL_IDENTITY               = 'sales_email/received/identity';
    const XML_PATH_RECEIVED_EMAIL_COPY_TO                = 'sales_email/received/copy_to';
    const XML_PATH_RECEIVED_EMAIL_COPY_METHOD            = 'sales_email/received/copy_method';
    const XML_PATH_RECEIVED_EMAIL_ENABLED                = 'sales_email/received/enabled';

	const XML_PATH_OUTSTANDING_PAYMENT_TEMPLATE_ID    				= 'sales_email/outstanding_payment/template_id';
	const XML_PATH_OUTSTANDING_PAYMENT_EMAIL_TEMPLATE               = 'sales_email/outstanding_payment/template';
    const XML_PATH_OUTSTANDING_PAYMENT_EMAIL_GUEST_TEMPLATE         = 'sales_email/outstanding_payment/guest_template';
    const XML_PATH_OUTSTANDING_PAYMENT_EMAIL_IDENTITY               = 'sales_email/outstanding_payment/identity';
    const XML_PATH_OUTSTANDING_PAYMENT_EMAIL_COPY_TO                = 'sales_email/outstanding_payment/copy_to';
    const XML_PATH_OUTSTANDING_PAYMENT_EMAIL_COPY_METHOD            = 'sales_email/outstanding_payment/copy_method';
    const XML_PATH_OUTSTANDING_PAYMENT_EMAIL_ENABLED                = 'sales_email/outstanding_payment/enabled';
	
	const XML_PATH_REPLY_TO_EMAIL        		= 'trans_email/ident_custom4/email';
	
	public function confirmationAction(){
		 if ($order = $this->_initOrder()) {
            try {
				$consumers = array();
				$campaignName = "Confirmation";
				$coreHelper = Mage::helper('core');
                $templateId = Mage::getStoreConfig(self::XML_PATH_EXTRA_TEMPLATE_ID, $order->getStoreId());
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
				
				$reponse = Mage::getModel('tejar_sales/observer')->postCampaign($order,$templateId,$campaignName,$consumers);
				if($reponse){
					$this->_getSession()->addSuccess($this->__('Order confirmation notification has been sent.'));	
				}
				
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($this->__('Unable to send order confirmation notification.'));
                Mage::logException($e);
            }
        }
		
        $this->_redirect('*/sales_order/view', array('order_id' => $order->getId()));
	}
	
	public function returnAction(){
		if ($order = $this->_initOrder()) {
			try {
				$storeId = $order->getStoreId();
				if(Mage::getStoreConfig(self::XML_PATH_EMAIL_ENABLED, $storeId)){
					return $this;
				}
				$dataResponse = array();
				$dataResponse['message'] = array(); 
				if(!$order->hasShipments()){
					$dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('shipment has not available.'));
						$this->getResponse()->setBody(
							Mage::helper('core')->jsonEncode($dataResponse)
						);
					return;
				}
				
				$all_items = array();
				if($returnProduct = $this->getRequest()->getParam('return')){
					$items = $returnProduct['items'];
					foreach($order->getItemsCollection() as $item){
						if(isset($items[$item->getId()]['qty'])){
							if($items[$item->getId()]['qty'] != ""){
								$item->setQty($items[$item->getId()]['qty']);
								$all_items[] = $item;
							}
						}
					}
				}

				$configs = array(
					"template_id" => self::XML_PATH_TEMPLATE_ID,
					"template" => self::XML_PATH_EMAIL_TEMPLATE,
					"guest_template" => self::XML_PATH_EMAIL_GUEST_TEMPLATE,
					"identity" => self::XML_PATH_EMAIL_IDENTITY,
					"copy_to" => self::XML_PATH_EMAIL_COPY_TO,
					"copy_method" => self::XML_PATH_EMAIL_COPY_METHOD,
				);

				
				if($this->_sendEmail($order,$all_items,$configs)){
					$dataResponse['message'][] = array('type' => 'success','text' => Mage::helper('sales')->__('The RMA email has been sent.'));
				} else {
					$dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Oops!!! Something went wrong, we could not sent the email.'));
				}
				
				$consumers = array();
				$campaignName = "Return Products";
				$templateId = Mage::getStoreConfig(self::XML_PATH_TEMPLATE_ID, $order->getStoreId());
				$billingAddress = $order->getBillingAddress();
				$coreHelper = Mage::helper('core');
				$telephone = $billingAddress->getData('telephone');
				if($templateId){
					if($validateNumber = $coreHelper->validateNumber($telephone,null,false)){
						$consumers[] = array(
							"consumerCountryCode" => "{$validateNumber->getCountryCode()}",
							"consumerPhoneNumber" => $validateNumber->getNationalNumber(),
							"variables" => array(
								1 => $coreHelper->escapeHtml($order->getCustomerName()),
								2 => $order->getStore()->formatPrice($order->getGrandTotal(), false),
								3 => $order->getIncrementId()
							)
						);
					} else {
						$dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Missing or invalid default region.'));	
					}		
				}
				
				$response = Mage::getModel('tejar_sales/observer')->postCampaign($order,$templateId,$campaignName,$consumers);
				if($response){
					$dataResponse['data'] = $response;
					$dataResponse['message'][] = array('type' => 'success','text' => Mage::helper('sales')->__('The RMA message has been sent.'));
				} else {
					$dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Oops!!! Something went wrong, we could not sent the message.'));	
				}
				
				$this->getResponse()->setBody(
					Mage::helper('core')->jsonEncode($dataResponse)
				);
			
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			} catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('giftmessage')->__('An error occurred while sending the return products.'));
			}
		}
	}
	
	public function receivedAction(){
		if ($order = $this->_initOrder()) {
			try {
				$storeId = $order->getStoreId();
				if(Mage::getStoreConfig(self::XML_PATH_RECEIVED_EMAIL_ENABLED, $storeId)){
					return $this;
				}
				$dataResponse = array();
				$dataResponse['message'] = array(); 
				
				if(!$order->hasShipments()){
					$dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('shipment has not available.'));
						$this->getResponse()->setBody(
							Mage::helper('core')->jsonEncode($dataResponse)
						);
					return;
				}
				
				
				$all_items = array();
				if($returnProduct = $this->getRequest()->getParam('return')){
					$items = $returnProduct['items'];
					foreach($order->getItemsCollection() as $item){
						if(isset($items[$item->getId()]['qty'])){
							if($items[$item->getId()]['qty'] != ""){
								$item->setQty($items[$item->getId()]['qty']);
								$all_items[] = $item;
							}
						}
					}
				}
				
				$configs = array(
					"template_id" => self::XML_PATH_RECEIVED_TEMPLATE_ID,
					"template" => self::XML_PATH_RECEIVED_EMAIL_TEMPLATE,
					"guest_template" => self::XML_PATH_RECEIVED_EMAIL_GUEST_TEMPLATE,
					"identity" => self::XML_PATH_RECEIVED_EMAIL_IDENTITY,
					"copy_to" => self::XML_PATH_RECEIVED_EMAIL_COPY_TO,
					"copy_method" => self::XML_PATH_RECEIVED_EMAIL_COPY_METHOD,
				);

				
				if($this->_sendEmail($order,$all_items,$configs)){
					$dataResponse['message'][] = array('type' => 'success','text' => Mage::helper('sales')->__('The RMA email has been sent.'));
				} else {
					$dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Oops!!! Something went wrong, we could not sent the email.'));
				}
				
				$consumers = array();
				$campaignName = "Received Products";
				$billingAddress = $order->getBillingAddress();
				$telephone = $billingAddress->getData('telephone');
				$coreHelper = Mage::helper('core');
				$templateId = Mage::getStoreConfig(self::XML_PATH_RECEIVED_TEMPLATE_ID, $order->getStoreId());
				if($templateId){
					if($validateNumber = $coreHelper->validateNumber($telephone,null,false)){
						$consumers[] = array(
							"consumerCountryCode" => "{$validateNumber->getCountryCode()}",
							"consumerPhoneNumber" => $validateNumber->getNationalNumber(),
							"variables" => array(
								1 => $coreHelper->escapeHtml($order->getCustomerName()),
								2 => $order->getStore()->formatPrice($order->getGrandTotal(), false),
								3 => $order->getIncrementId()
							)
						);
					} else {
						$dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Missing or invalid default region.'));	
					}		
				}	
				
				
				$response = Mage::getModel('tejar_sales/observer')->postCampaign($order,$templateId,$campaignName,$consumers);
				if($response){
					$dataResponse['data']  = $response;
					$dataResponse['message'][] = array('type' => 'success','text' => Mage::helper('sales')->__('The RMA message has been sent.'));
				} else {
					$dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Oops!!! Something went wrong, we could not sent the message.'));
				}					
				
				
				
				$this->getResponse()->setBody(
					Mage::helper('core')->jsonEncode($dataResponse)
				);
			
			} catch (Mage_Core_Exception $e) {
				$this->_getSession()->addError($e->getMessage());
			} catch (Exception $e) {
				$this->_getSession()->addError(Mage::helper('giftmessage')->__('An error occurred while sending the return products.'));
			}
		}
	}
	
	public function outstandingpaymentAction(){
		if ($order = $this->_initOrder()) {
		   try {
			$storeId = $order->getStoreId();
			$dataResponse = array();
			$dataResponse['message'] = array(); 
			
			 if(!Mage::getStoreConfig(self::XML_PATH_OUTSTANDING_PAYMENT_EMAIL_ENABLED, $storeId)){
				 $dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Outstanding payment is disabled.'));	
				 return $this->getResponse()->setBody(
					 Mage::helper('core')->jsonEncode($dataResponse)
				 );
			 } 
			   $remainingPayment = $this->getRequest()->getParam('remaining_payment');

			   if($remainingPayment > $order->getGrandTotal()){
				$dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Enter an amount less than the order grand total.'));	
				return $this->getResponse()->setBody(
					Mage::helper('core')->jsonEncode($dataResponse)
				);
		   }
			   // var_dump($this->getRequest()->getParam('remaining_payment')); die;
			   $all_items = $order->getStore()->formatPrice($remainingPayment, false);
			   // var_dump($all_items); die;
			   $configs = array(
				   "template_id" => self::XML_PATH_OUTSTANDING_PAYMENT_TEMPLATE_ID,
				   "template" => self::XML_PATH_OUTSTANDING_PAYMENT_EMAIL_TEMPLATE,
				   "guest_template" => self::XML_PATH_OUTSTANDING_PAYMENT_EMAIL_GUEST_TEMPLATE,
				   "identity" => self::XML_PATH_OUTSTANDING_PAYMENT_EMAIL_IDENTITY,
				   "copy_to" => self::XML_PATH_OUTSTANDING_PAYMENT_EMAIL_COPY_TO,
				   "copy_method" => self::XML_PATH_OUTSTANDING_PAYMENT_EMAIL_COPY_METHOD,
			   );
			   if($this->_sendEmail($order,$all_items,$configs)){
				   $dataResponse['message'][] = array('type' => 'success','text' => Mage::helper('sales')->__('Outstanding Payment email has been sent.'));
			   } else {
				   $dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Oops!!! Something went wrong, we could not sent the email.'));
			   }
			   $consumers = array();
			   $campaignName = "Outstanding Payment";
			   $templateId = Mage::getStoreConfig(self::XML_PATH_OUTSTANDING_PAYMENT_TEMPLATE_ID, $order->getStoreId());
			   $billingAddress = $order->getBillingAddress();
			   $coreHelper = Mage::helper('core');
			   $telephone = $billingAddress->getData('telephone');
			   if($templateId){
				   if($validateNumber = $coreHelper->validateNumber($telephone,null,false)){
					   $consumers[] = array(
						   "consumerCountryCode" => "{$validateNumber->getCountryCode()}",
						   "consumerPhoneNumber" => $validateNumber->getNationalNumber(),
						   "variables" => array(
							   1 => $coreHelper->escapeHtml($order->getCustomerName()),
							   2 => $order->getStore()->formatPrice($remainingPayment, false),
							   3 => $order->getIncrementId()
						   )
					   );
				   } else {
					   $dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Missing or invalid default region.'));	
				   }		
			   }
			   $response = Mage::getModel('tejar_sales/observer')->postCampaign($order,$templateId,$campaignName,$consumers);
			   if($response){
				   $dataResponse['data'] = $response;
				   $dataResponse['message'][] = array('type' => 'success','text' => Mage::helper('sales')->__('The RMA message has been sent.'));
			   } else {
				   $dataResponse['message'][] = array('type' => 'error','text' => Mage::helper('sales')->__('Oops!!! Something went wrong, we could not sent the message.'));	
			   }
			   $this->getResponse()->setBody(
				   Mage::helper('core')->jsonEncode($dataResponse)
			   );
		   } catch (Mage_Core_Exception $e) {
			   $this->_getSession()->addError($e->getMessage());
		   } catch (Exception $e) {
			   $this->_getSession()->addError(Mage::helper('giftmessage')->__('An error occurred while sending the return products.'));
		   }
		}
   }
	
	protected function _sendEmail($order,$all_items,$configs){
		$storeId = $order->getStoreId();
		$copyTo = Mage::getStoreConfig($configs['copy_to'], $storeId);
		if (!empty($copyTo)) {
				$copyTo = explode(',', $copyTo);
		}
		$copyMethod = Mage::getStoreConfig($configs['copy_method'], $storeId);
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
			 $emailTemplateId = Mage::getStoreConfig($configs['guest_template'], $storeId);
			 $customerName = $order->getBillingAddress()->getName();
		 } else {
			 $emailTemplateId = Mage::getStoreConfig($configs['template'], $storeId);
			 $customerName = $order->getCustomerName();
		 }

		// $templateId = Mage::getStoreConfig(self::XML_PATH_DELAYED_EMAIL_TEMPLATE, $storeId);
		//--- If no template is found return.. Do nothing.
		if(!$emailTemplateId){
			return;
		}

		//--- Set all required params and send emails
		$mailer->setSender(Mage::getStoreConfig($configs['identity'], $storeId));
		$mailer->setStoreId($storeId);
		$mailer->setReplyTo($replyTo);
		$mailer->setTemplateId($emailTemplateId);
		$mailer->setTemplateParams(array(
				'order'        => $order,
				'all_items' => $all_items
			)
		);
		
		try {
			$mailer->send();
			return true;
		} catch(Exception $exception) {
			 Mage::log($e, null, 'email.log');
			throw $exception;
		}
		
		return false;
	}
   
}
