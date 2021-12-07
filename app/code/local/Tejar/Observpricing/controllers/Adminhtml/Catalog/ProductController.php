<?php
/*                                                                        *
 * This script is part of the ChangeAttributeSet project        		  *
 *                                                                        *
 * TypoGento is free software; you can redistribute it and/or modify it   *
 * under the terms of the GNU General Public License version 2 as         *
 * published by the Free Software Foundation.                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * ChangeAttributeSet Controller
 *
 * @version $Id: ProductController.php 642 2011-03-14 17:21:28Z weller $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tejar_Observpricing_Adminhtml_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController
{
	public function __quickCreateAction()
    { 
	echo "ZEE---> ";die; 
        $result = array();

        /* @var $configurableProduct Mage_Catalog_Model_Product */
        $configurableProduct = Mage::getModel('catalog/product')
            ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
            ->load($this->getRequest()->getParam('product'));

        if (!$configurableProduct->isConfigurable()) {
            // If invalid parent product
            $this->_redirect('*/*/');
            return;
        }

        /* @var $product Mage_Catalog_Model_Product */

        $product = Mage::getModel('catalog/product')
            ->setStoreId(0)
            ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
            ->setAttributeSetId($configurableProduct->getAttributeSetId());

        foreach ($product->getTypeInstance()->getEditableAttributes() as $attribute) {
            if ($attribute->getIsUnique()
                || $attribute->getAttributeCode() == 'url_key'
                || $attribute->getFrontend()->getInputType() == 'gallery'
                || $attribute->getFrontend()->getInputType() == 'media_image'
                || !$attribute->getIsVisible()) {
                continue;
            }

            $product->setData(
                $attribute->getAttributeCode(),
                $configurableProduct->getData($attribute->getAttributeCode())
            );
        }

        $product->addData($this->getRequest()->getParam('simple_product', array()));
        $product->setWebsiteIds($configurableProduct->getWebsiteIds());

        $autogenerateOptions = array();
        $result['attributes'] = array();

        foreach ($configurableProduct->getTypeInstance()->getConfigurableAttributes() as $attribute) {
            $value = $product->getAttributeText($attribute->getProductAttribute()->getAttributeCode());
            $autogenerateOptions[] = $value;
            $result['attributes'][] = array(
                'label'         => $value,
                'value_index'   => $product->getData($attribute->getProductAttribute()->getAttributeCode()),
                'attribute_id'  => $attribute->getProductAttribute()->getId()
            );
        }

        if ($product->getNameAutogenerate()) {
            $product->setName($configurableProduct->getName() . '-' . implode('-', $autogenerateOptions));
        }

        if ($product->getSkuAutogenerate()) {
            $product->setSku($configurableProduct->getSku() . '-' . implode('-', $autogenerateOptions));
        }

        if (is_array($product->getPricing())) {
           $result['pricing'] = $product->getPricing();
           $additionalPrice = 0;
           foreach ($product->getPricing() as $pricing) {
               if (empty($pricing['value'])) {
                   continue;
               }

               if (!empty($pricing['is_percent'])) {
                   $pricing['value'] = ($pricing['value']/100)*$product->getPrice();
               }

               $additionalPrice += $pricing['value'];
           }
           $product->setPrice($product->getPrice() + $additionalPrice);
           $product->unsPricing();
        }
//==================================== ZEE CODE ===================================//
			$productId = $product->getId();
			$exm = "";
			//--- GET Default Store ID...
			//$defaultStoreId = Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId();
		
			//--- GET Base USD Price/Rate & Special Price...
			//$productDefault   = Mage::getModel('catalog/product')->setStoreId($defaultStoreId)->load($productId);
			$usd_rate         = $product->getPrice();
			$usd_specialPrice = $product->getspecialPrice();
			$exm .= $usd_rate." - ";
			$product->setSku(Mage::helper('tejarobservpricing')->getRandomSKU())->save();
			
			//--- Loop through available Stores...	
			foreach(Mage::app()->getStores() as $store){
				$storeCurrencyCode = $store->getCurrentCurrencyCode();
				$storeId           = $store->getId();
				//$product           = Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);
				//Mage::log($currencyRates[$storeCurrencyCode], null, 'confPricing.log');
				
				$metaTitle         = Mage::helper('tejarobservpricing')->getMetaTitle($storeCurrencyCode, $product->getName());
				$metaDescription   = Mage::helper('tejarobservpricing')->getMetaDescription($storeCurrencyCode, $product->getName());
			
				Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
				$allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();	
				
				//--- Get currency rates for USD Currency
				$currencyRates = Mage::getModel('directory/currency')->getCurrencyRates('USD', array_values($allowedCurrencies));
				
################################## SET PRICES & SPECIAL PRICE #####################################		
				//if($product->getAttributeDefaultValue('price')==false){
					if($storeCurrencyCode=="PKR"){
						Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
					
					//--- Get Product Final Cost using helper function...
					$finalCost = Mage::helper('tejarobservpricing')->getFinalCostQuickView($product, $usd_rate);
					
					//--- Now get the PKR rate by multiplying with Store Currency Rates..
					$pkr_rate = $finalCost * $currencyRates[$storeCurrencyCode];
					
					//--- Round up Last two digits -1 for PKR and multiply with US BASED currency rate
					$pkr_rate = Mage::helper('tejarobservpricing')->round_up($pkr_rate,-2)-1;
					
					//--- For Special Price...
					if($usd_specialPrice!="" || $usd_specialPrice != false){
						
						//--- Get Product Final Cost for Special Price using helper function...
						$finalSpecialCost = Mage::helper('tejarobservpricing')->getFinalCostQuickView($product, $usd_specialPrice);
						$pkr_special_rate = $finalSpecialCost * $currencyRates[$storeCurrencyCode];
						
						//--- Round up Last two digits -1 for PKR and multiply with US BASED currency rate
						$pkr_special_rate = Mage::helper('tejarobservpricing')->round_up($pkr_special_rate,-2)-1;
					}else{
						$pkr_special_rate = $usd_specialPrice;
					}
					
					//--- Update Attributes...
					Mage::getSingleton('catalog/product_action')
					->updateAttributes(array($product->getId()),array('price'=>$pkr_rate, 'special_price'=>$pkr_special_rate,'meta_title'=>$metaTitle, 'meta_description'=>$metaDescription),$storeId);	
				
					}
					
					if($storeCurrencyCode=="AED"){
						Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
					
					//--- GET product brand...
					$productBrand   = $product->getAttributeText('manufacturer');
					
					//--- Get Product Final Cost using helper function...
					$finalCost = Mage::helper('tejarobservpricing')->getFinalCostQuickView($product, $usd_rate);
					
					//--- Now get the PKR rate by multiplying with Store Currency Rates..
					$aed_rate = $finalCost * $currencyRates[$storeCurrencyCode];
					
					//--- Round up Last two digits -1 for AED and multiply with US BASED currency rates
					$aed_rate = Mage::helper('tejarobservpricing')->round_up($aed_rate,-1)-1;
					
					//echo $aed_rate;die;
					//--- For Special Price...
					if($usd_specialPrice!="" || $usd_specialPrice != false){	
						
						//--- Get Product Final Cost for Special Price using helper function...
						$finalSpecialCost = Mage::helper('tejarobservpricing')->getFinalCostQuickView($product, $usd_specialPrice);
						$aed_special_rate = $finalSpecialCost * $currencyRates[$storeCurrencyCode];
						
						//--- Round up Last two digits -1 for UAE and multiply with US BASED currency rate
						$aed_special_rate = Mage::helper('tejarobservpricing')->round_up($aed_special_rate,-2)-1;
					}else{
						$aed_special_rate = $usd_specialPrice;
					}
					
					//--- Update Attributes with Cost if Cost is not default value 1.25..
						Mage::getSingleton('catalog/product_action')
						->updateAttributes(array($product->getId()),array('price'=>$aed_rate, 'special_price'=>$aed_special_rate,'meta_title'=>$metaTitle,'meta_description'=>$metaDescription),$storeId);
					}
					if($storeCurrencyCode=="USD"){
						
						//$product->setData($metaTitle)->save();
						Mage::getSingleton('catalog/product_action')
						->updateAttributes(array($product->getId()),array('meta_title'=>$metaTitle,'meta_description'=>$metaDescription),$storeId);
					}
				}//--- End foreach..
//============================================ ZEE CODE =========================================//		

        try {
            /**
             * @todo implement full validation process with errors returning which are ignoring now
             */
//            if (is_array($errors = $product->validate())) {
//                $strErrors = array();
//                foreach($errors as $code=>$error) {
//                    $codeLabel = $product->getResource()->getAttribute($code)->getFrontend()->getLabel();
//                    $strErrors[] = ($error === true)? Mage::helper('catalog')->__('Value for "%s" is invalid.', $codeLabel) : Mage::helper('catalog')->__('Value for "%s" is invalid: %s', $codeLabel, $error);
//                }
//                Mage::throwException('data_invalid', implode("\n", $strErrors));
//            }

            $product->validate();
            $product->save();
            $result['product_id'] = $product->getId();
            $this->_getSession()->addSuccess(Mage::helper('catalog')->__('The product has been created.'));
            $this->_initLayoutMessages('adminhtml/session');
            $result['messages']  = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = array(
                'message' =>  $e->getMessage(),
                'fields'  => array(
                'sku'  =>  $product->getSku()
                )
            );

        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = array(
                'message'   =>  $this->__('An error occurred while saving the product. ') . $e->getMessage()
             );
        }
		$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
	
	
	/**
	 * Product list page
	 */
	/*public function updateBulkPricesAction()
	{
		//echo "zee";die;
		$productIds = $this->getRequest()->getParam('product');
		//echo count($productIds);die;
		//$storeId = (int)$this->getRequest()->getParam('store', 0);
		if(!is_array($productIds)) {
			$this->_getSession()->addError($this->__('Please select product(s)'));
		}
		else {
			try {
				//foreach ($productIds as $productId) {
					//echo $productId."</br>";
					/*$product = Mage::getSingleton('catalog/product')
						->unsetData()
						->setStoreId($storeId)
						->load($productId)
						->setAttributeSetId($this->getRequest()->getParam('attribute_set'))
						->setIsMassupdate(true)
						->save();*/
				//}
			/*	Mage::dispatchEvent('catalog_product_massupdate_pricing_before', array('products'=>$productIds));
				$this->_getSession()->addSuccess(
					$this->__('Total of %d record(s) were successfully updated', count($productIds))
				);
			}
			catch (Exception $e) {
				$this->_getSession()->addException($e, $e->getMessage());
			}
		}
		
		$this->_redirect('adminhtml/catalog_product/index/', array());
	}	*/
}
