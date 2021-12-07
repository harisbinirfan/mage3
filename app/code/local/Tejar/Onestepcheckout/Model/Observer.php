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


class Tejar_Onestepcheckout_Model_Observer
{
	
	public function checkoutOnepageSaveShippingMethod(Varien_Event_Observer $observer){
		
		$event 			= $observer->getEvent();
		$quote 			= $event->getQuote();
		$request 		= $event->getRequest();
		$payment_method = $request->getPost('payment_method', false);
		
		
		$custom_price = 0;
		$totalQty = $quote->getItemsSummaryQty();
		$storeId = Mage::app()->getStore()->getStoreId();
        $processingFee = Mage::getStoreConfig("payment/{$payment_method}/processing_fee", $storeId);
		
		if($payment_method){
			foreach($quote->getItemsCollection() as $quoteItem){
				$product = Mage::getModel("catalog/product")->load($quoteItem->getProductId());
				$custom_price = $product->getFinalPrice();
				
				if($processingFee){
					$custom_price = eval($processingFee);
				}
				
				$quoteItem->setOriginalCustomPrice($custom_price);
				$quoteItem->setCustomPrice($custom_price);
				$quoteItem->setIsSuperMode(true);
				$quoteItem->save();
			}
		}
		
		$quote->save();
	}

}
