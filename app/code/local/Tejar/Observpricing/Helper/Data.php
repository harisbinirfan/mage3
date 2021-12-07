<?php
/* ObservPricing Helper
 *
 * @category   Tejar
 * @package    Tejar_ObservPricing
 * @class      Tejar_Observpricing_Helper_Data
 * @author     Zeeshan <zeeshan.zeeshan123@gmail.com>
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Tejar_Observpricing_Helper_Data extends Mage_Core_Helper_Abstract
{
	/*
	*@name        getRandomSKU
	*@description This function returns a random hash containing 10 alpha numeric Characters..
	*@parameters  None
	*@returns     String
	*/
	public function getRandomSKU(){
		$characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
		$string = '';
		for($i = 0; $i <= 9; $i++) {
			$string .= strtoupper($characters[mt_rand(0, strlen($characters) - 1)]);
		}
		if($string[0]=="0"){
			return $this->getRandomSKU();
		}else{
			return $string;
		}
	}
   /*
	*@name        round_up
	*@parameters  $value, $places
	*@description to obtain EXCEL like ROUND UP functionality...
	*@returns     Number
	*/
	public function round_up($value, $places){
		$mult = pow(10, abs($places));
		return $places < 0 ? ceil($value / $mult) * $mult :ceil($value * $mult) / $mult;
	}

	/*
		*@name        getMetaTitle
		*@parameters  $countryCode, $productTitle
		*@description to obtain Meta Title of a given product
		*@returns     String
		*/
		public function getMetaTitle($store, $productTitle){

			$storeId = $store->getId();
			$countryCode = Mage::getStoreConfig('general/country/default', $storeId);
			$country = Mage::getModel('directory/country')->loadByCode($countryCode);
			$countryName = $country->getName();

			$meta_title = Mage::getStoreConfig('design/product_meta/meta_title', $storeId);

			if($meta_title){
				$metaTitle = sprintf($meta_title , $productTitle , $countryName);
				return $metaTitle;
			}

			return;
		}

		/*
		*@name        getMetaDescription
		*@parameters  $countryCode, $productDescription
		*@description to obtain Meta Description of a given product
		*@returns     String
		*/
		public function getMetaDescription($store, $productTitle){

			$storeId = $store->getId();
			$countryCode = Mage::getStoreConfig('general/country/default', $storeId);
			$country = Mage::getModel('directory/country')->loadByCode($countryCode);
			$countryName = $country->getName();

			$meta_description = Mage::getStoreConfig('design/product_meta/meta_description', $storeId);
			if($meta_description){
				$metaDescription = sprintf($meta_description , $productTitle , $countryName);
				return $metaDescription;
			}

			return;

		}



		public function getFinalCost($product, $defaultProduct, $basePrice, $baseStorePrice , $storeId, $storeCurrencyCode, $specialFormula){

			$result 				= 	array();
			$ExchangeRateRow		= 	null;
			$allowedCurrencies 		= 	Mage::getModel('directory/currency')->getConfigAllowCurrencies();
			$currencyRates 			= 	Mage::getModel('directory/currency')->getCurrencyRates(Mage::app()->getStore()->getBaseCurrency(), array_values($allowedCurrencies));
			$Weight 			= 	$product->getWeight()!=""?$product->getWeight():0;
			$ActualWeight 			= 	$product->getWeight()!=""?$product->getWeight():0;
			$GrossWeight 			= 	$product->getWeight()!=""?$product->getWeight():0;
			$productSource 			= 	$product->getSourcing()!=""?$product->getAttributeText('sourcing'):"";
			$sources 				= 	@unserialize(Mage::getStoreConfig('tejarobservpricing/sourcing/sourcing',$storeId));
			$ExchangeRate 			= 	(isset($currencyRates[$storeCurrencyCode]) ? $currencyRates[$storeCurrencyCode] : 1);
			$Volume					= 	$product->getDimWeight();
			$ListPriceFlag 			=	$product->getExistsStoreValueFlag('list_price');
			$ProductPrice			= 	$basePrice;
			$CurrentPrice			=	$baseStorePrice;
			$FixedLocalShipping 	= 	Mage::getStoreConfig('carriers/flatrate/price',$storeId);
			$MinimumOrderAmount 	= 	(int)Mage::getStoreConfig('carriers/freeshipping/free_shipping_subtotal',$storeId);
			$optionText				= 	"US Reseller";
			$isAttributeExist		= 	$product->getResource()->getAttribute('sourcing');


			$DefaultPrice			=	$defaultProduct->getPrice();
			$DefaultSpecialPrice	=	$defaultProduct->getSpecialPrice();
			$DefaultListPrice		=	$defaultProduct->getListPrice();
			$StorePrice				=	$product->getPrice();
			$StoreSpecialPrice		=	$product->getSpecialPrice();
			$StoreListPrice			=	$product->getListPrice();


			$Markup 				=	1;
			$KGtoLB 				= 	2.20462;
			$InternationalShipping 	= 	0;
			$ShippingBoxWeight 		= 	0;
			$LocalShipping 			= 	0;
			$Clearance 				= 	0;
			$Handling 				= 	0;
			$TransactionFee 		= 	0;
			$ForeignTransactionFee 	= 	1;
			$TransactionPercentage 	= 	1;

			try{
				if($sources){
					foreach($sources as $source){

						if ($isAttributeExist && $isAttributeExist->usesSource()) {
							$optionText = $isAttributeExist->getSource()->getOptionText($source['source']);
						}

						if(($productSource ==  $optionText) && ($optionText!="" && $productSource!="") && $source['value'] != ""){

							$ExchangeRateRow = $source['currency'];
							$ExchangeRate = $this->getCustomCurrency($source['currency'], $storeCurrencyCode, $ExchangeRate, $allowedCurrencies);
							if($source['special_value'] && $specialFormula == true){
								$result = eval($source['special_value']);
							} else {
								$result = eval($source['value']);
							}

						} elseif($productSource == "" && ($optionText == "US Reseller" && $source['value'] != "")) {

							$ExchangeRateRow = $source['currency'];
							$ExchangeRate = $this->getCustomCurrency($source['currency'], $storeCurrencyCode, $ExchangeRate, $allowedCurrencies);
							if($source['special_value'] && $specialFormula == true){
								$result = eval($source['special_value']);
							} else {
								$result = eval($source['value']);
							}

						} elseif(($productSource == "" &&  $source['value'] == "") || ($productSource !=  $optionText && $source['value'] == "") || ($productSource ==  $optionText && $source['value'] == "")){

							$result = $ProductPrice * $ExchangeRate;

						}

					}

				}

				if(empty($result)){
						$result = $ProductPrice * $ExchangeRate;
			  }

				// if($ProductPrice == null){
				// 	return null;
				// }

				if($ExchangeRateRow==null){
					$ExchangeRateRow = Mage::app()->getStore()->getBaseCurrency()->getCurrencyCode();
				}


			} catch(Exception $e) {
				 'Message: ' . $e->getMessage();
			}


			return $result;

		}



	public function getCustomCurrency($sourceCurrency, $storeCurrencyCode, $exchangeRate, $allowedCurrencies){

			if($sourceCurrency){
				$currencyRates = Mage::getModel('directory/currency')->getCurrencyRates($sourceCurrency, array_values($allowedCurrencies));
				if(array_key_exists($storeCurrencyCode, $currencyRates)){
				$ExchangeRate = $currencyRates[$storeCurrencyCode];
				}
				if($currencyRates != ""){
					$customCurrencyRates = @unserialize(Mage::getStoreConfig('tejarobservpricing/currency/rates',$storeId));
					foreach($customCurrencyRates as $rate){
						if($rate['currency'] == $sourceCurrency){
							 $ExchangeRate = $rate[$storeCurrencyCode];
						}
					}
				}
			} else {
				 $ExchangeRate = $exchangeRate;
			}
			return $ExchangeRate;
	}

}
