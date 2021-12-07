<?php
/**
 * Adminhtml sales order shipment controller
 *
 * @category   Tejar
 * @package    Tejar_Adminhtml
 * @author     zeeshan <zeeshan.zeeshan123.com>
 */
require_once 'Mage/Adminhtml/controllers/Sales/Order/ShipmentController.php';
class Tejar_Adminhtml_Sales_Order_ShipmentController extends Mage_Adminhtml_Sales_Order_ShipmentController
{
	 /**
     * Send email with shipment data to customer
     */
	public function emailAction()
    {
        try {
            $shipment = $this->_initShipment();
            if ($shipment) {
		//================================================= ZEE CODE ==========================================//
				$orderIncrementId = $shipment->getOrder()->getIncrementId();
				$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
				
				//--- If Store Pickup was used as shipping method use specific email template..
				if(trim($order->getShippingMethod()) === 'customshippingmethod_customshippingmethod'){
				//--- Set Email Template Id  if Shipment Method: Tejar Pickup Parcel was used ...
				$pickupEmailTemplate = Mage::getModel('core/email_template')->loadByCode('Shipment -Tejar Parcel');
				$templateId          = $pickupEmailTemplate->getTemplateId();
				
				//echo "---> ".$templateId;die;
					$mailer          = Mage::getModel('core/email_template_mailer');
					$emailInfo       = Mage::getModel('core/email_info');
					
					$emailInfo->addTo((string)$order->getCustomerEmail(), (string)$order->getCustomerName());
					$mailer->addEmailInfo($emailInfo);
					
					//--- Set sender info
					$senderName = Mage::getStoreConfig('trans_email/ident_support/name');
					$senderEmail = Mage::getStoreConfig('trans_email/ident_support/email');        
					$sender = array('name' => (string)$senderName,'email' => (string)$senderEmail);
					
					//--- Set all required params and send emails
					$mailer->setSender($sender, $storeId);
					$mailer->setStoreId($storeId);
					$mailer->setTemplateId($templateId);
					$mailer->setTemplateParams(array(
							'order'        => $order,
							'shipment'     => $order->getShippingAddress(),
							'comment'      => '',
							'billing'      => $order->getBillingAddress(),
							'payment_html' => $paymentBlockHtml
						)
					);
					try{
						$mailer->send();
					}catch(Exception $exception){
						Mage::log($e, null, 'pickup.log');
						throw $exception;
					}
				}else{
					$shipment->sendEmail(true)
                    ->setEmailSent(true)
                    ->save();
				}
		//================================================= ZEE CODE ==========================================//
                $historyItem = Mage::getResourceModel('sales/order_status_history_collection')
                    ->getUnnotifiedForInstance($shipment, Mage_Sales_Model_Order_Shipment::HISTORY_ENTITY_NAME);
                if($historyItem){
                    $historyItem->setIsCustomerNotified(1);
                    $historyItem->save();
                }
                $this->_getSession()->addSuccess($this->__('The shipment has been sent.'));
            }
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError($this->__('Cannot send shipment information.'));
        }
        $this->_redirect('*/*/view', array(
            'shipment_id' => $this->getRequest()->getParam('shipment_id')
        ));
    }
}