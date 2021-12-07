<?php
/**
 * Cartalert module observer
 *
 * @author Adjustware
 */
class Tejar_Abandoned_Model_Cartalert extends AdjustWare_Cartalert_Model_Cartalert
{
    /**
     * @param AdjustWare_Cartalert_Model_History $history
     * @return AdjustWare_Cartalert_Model_History
     */
    protected function _send($history)
    {
        $storeId = $this->getStoreId();
        $store = Mage::app()->getStore($storeId);

        if(strlen($this->getProducts())>0){
            try {
                $history = $this->_setHistoryData($history);
                $url = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

                $couponCode = '';
                if ($this->getFollowUp() == Mage::getStoreConfig('catalog/adjcartalert/coupon_step', $store)) {
                    $couponCode = $this->_createCoupon($store);
                }

                if($couponCode){
                    $history->setCouponCode($couponCode)->save();
                }

                $discountAmount = '';

                if ($couponCode)
                {
                    if (Mage::getStoreConfig('catalog/adjcartalert/coupon_type', $store) == 'by_percent')
                    {
                        $discountAmount = Mage::getStoreConfig('catalog/adjcartalert/coupon_amount', $store).'%';
                    }
                    elseif (Mage::getStoreConfig('catalog/adjcartalert/coupon_type', $store) == 'by_fixed' || Mage::getStoreConfig('catalog/adjcartalert/coupon_type', $store) == 'cart_fixed')
                    {
                        $discountAmount = Mage::getStoreConfig('catalog/adjcartalert/coupon_amount', $store).' '.Mage::app()->getStore()->getCurrentCurrencyCode();
                    }
                }

				if($history->getCustomerId()){
					$orderId = Mage::getResourceSingleton('adjcartalert/history')->getOrderId($history->getQuoteId());
					$orderUrl = Mage::getUrl('sales/order/view', array('order_id'=> $orderId));
				} else {
					$orderId = Mage::getResourceSingleton('adjcartalert/history')->getOrderId($history->getQuoteId());
					$order = Mage::getModel('sales/order')->load($orderId);
					$orderUrl = Mage::getUrl('sales/guest/view/', array('oar_email'=> $history->getCustomerEmail(),
					'oar_billing_lastname' => $order->getData('customer_lastname'),
					'oar_type' => 'email',
					'oar_order_id' => $order->getIncrementId()));
				}

				$order = Mage::getModel('sales/order')->load($orderId);


                $tplVars = array(
                    'Cartalert'			   => $history,
                    'website_name'     => $store->getWebsite()->getName(),
                    'group_name'       => $store->getGroup()->getName(),
                    'store_name'       => $store->getName(),
                    'store_url'        => $url,
                    'products'         => $this->getProducts(),
                    'customer_name'    => $this->getCustomerName(),
                    'recover_url'      => $url . 'alerts/recover/cart/id/'.$history->getId().'/code/'.$history->getRecoverCode() .
                                            Mage::getStoreConfig('catalog/adjcartalert/cart_recovery_link'),
					'order_url'        => $orderUrl,
                    'unsubscribe_url'  => $url . 'alerts/unsubscribe/cart/id/'.$history->getId().'/code/'.$history->getRecoverCode(),
                    'real_quote'       => $history->getQuoteId(),
                    'coupon'           => $couponCode,
                    'coupon_days'      => Mage::getStoreConfig('catalog/adjcartalert/coupon_days', $store),
                    'discount_amount'  => $discountAmount,
                );

                if(version_compare(Mage::getVersion(), '1.7', '<')){
                    $tplVars['logo_url'] = Mage::getDesign()->getSkinUrl('images/logo_email.gif', array('_area'=>'frontend'));
                    $tplVars['logo_alt'] = '';
                }

				$validOrderStatuses = Mage::getStoreConfig('catalog/adjcartalert/stchooser');
				$validOrderStatuses = explode(",", $validOrderStatuses);

				if(!in_array($order->getData("status"), $validOrderStatuses) && $this->getQuoteIsActive() == 0){
					$this->_tpl->setSentSuccess(true);
					return $history;
				}

				$this->_sendTransactional($tplVars, $this->getCustomerEmail());
				$bccEmail = Mage::getStoreConfig('catalog/adjcartalert/bcc' , $store);
				if($bccEmail){
					$this->_sendTransactional($tplVars, $bccEmail);
				}

				return $history;

            }
            catch (Exception $e){
                //todo: remove coupon if any
                $history->delete();
                return $history;
            }
        }
    }

}
