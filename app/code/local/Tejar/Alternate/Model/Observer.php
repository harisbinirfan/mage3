<?php

class Tejar_Alternate_Model_Observer
{
	public function alternateLinks(){

			/* 3SD CODE GET HEAD BLOCK */
			$headBlock = Mage::app()->getLayout()->getBlock('head');

			/* 3SD CODE GET CURRENT PAGE OBJECTS */
			$stores = Mage::app()->getStores();
			$product = Mage::registry('current_product');
			$category = Mage::registry('current_category');
			$home = Mage::getBlockSingleton('page/html_header')->getIsHomePage();
			$currentStoreId = Mage::app()->getStore()->getId();
			$cmsPages = Mage::getSingleton('cms/page')->getIdentifier();
			$request = Mage::app()->getRequest();
			$webDefaultFront = Mage::getStoreConfig('web/default/front');

			$productId = "";
			if($product){
				$productId = $product->getId();
			}
			$associatedproduct = Mage::registry('associated_product');
			if($associatedproduct){
				$productId = $associatedproduct->getId();
			}

			/* canonical CMS pages */
      if($cmsPages && $cmsPages != $webDefaultFront){
          $url = Mage::getBaseUrl() . $cmsPages;
          $headBlock->addLinkRel('canonical', $url);
      } else if($cmsPages == $webDefaultFront){
          $url = Mage::getBaseUrl();
          $headBlock->addLinkRel('canonical', $url);
      } else if($request->getRouteName() == "contacts"){
          $url = Mage::getBaseUrl() . $request->getModuleName();
          $headBlock->addLinkRel('canonical', $url);
      }

			/* 3SD CODE INIT PRODUCT URL REWRITES */
			$productUrls = array();
			if($product){
				$urlRewriteCollection = Mage::getResourceModel('core/url_rewrite_collection');
				$urlRewriteCollection->getSelect()->Where("id_path = 'product/" . $productId . "'");
				foreach($urlRewriteCollection as $urlRewrite){
					$urlMatches = preg_match ('~'.$urlRewrite->getTargetPath().'~', Mage::helper('core/url')->getCurrentUrl());
					if($urlMatches){
						$productUrls[$urlRewrite->getStoreId()] = "";
					} else {
						$productUrls[$urlRewrite->getStoreId()] = $urlRewrite->getRequestPath();
					}
				}
			}

			/* 3SD CODE INIT CATEGORY URL REWRITES */
			$categoryUrls = array();
			if($category){
				$urlRewriteCollection = Mage::getResourceModel('core/url_rewrite_collection');
				$urlRewriteCollection->getSelect()->Where("id_path = 'category/" . $category->getId() . "'");
				foreach($urlRewriteCollection as $urlRewrite){
					$categoryUrls[$urlRewrite->getStoreId()] = $urlRewrite->getRequestPath();
				}
			}

			if($headBlock){
				foreach ($stores as $store){
					$url = "";
					$storeId = $store->getId();
					$urlSuffix = "";
					if($product){
						/* 3SD CODE IF PRODUCT PAGE */
						if(isset($productUrls[$storeId])){
							// $url = $store->getBaseUrl() . $productUrls[$storeId];
							if(array_key_exists($currentStoreId, $productUrls)){
							$urlSuffix = rtrim(str_ireplace(Mage::getBaseUrl() . $productUrls[$currentStoreId],'',Mage::helper('core/url')->getCurrentUrl()),'/');
						  }
						  $productStatus = Mage::getSingleton('catalog/product_status')->getProductStatus(array($productId), $storeId);
						  if($productStatus[$productId] != 2){
							  $url = $store->getBaseUrl() . $productUrls[$storeId] . $urlSuffix;	
						  }
						}
					} else if($category){
						/* 3SD CODE IF PRODUCT PAGE */
						if(isset($categoryUrls[$storeId])){
							// $url = $store->getBaseUrl() . $categoryUrls[$storeId];
							if(array_key_exists($currentStoreId, $categoryUrls)){
							$urlSuffix = rtrim(str_ireplace(Mage::getBaseUrl() . $categoryUrls[$currentStoreId],'',Mage::helper('core/url')->getCurrentUrl()),'/');
							}
							$url = $store->getBaseUrl() . $categoryUrls[$storeId] . $urlSuffix;
						}
					} else if($cmsPages == $webDefaultFront){
						/* 3SD CODE IF HOME PAGE */
						$url = $store->getBaseUrl();
					} else if ($cmsPages || $request->getRouteName() == "contacts") {
						$urlSuffix = rtrim(str_ireplace(Mage::getBaseUrl(),'',Mage::helper('core/url')->getCurrentUrl()),'/');
						$url = $store->getBaseUrl() . $urlSuffix;
					}

					/* 3SD CODE GET STORE CODE */
					$storeCode = substr(Mage::getStoreConfig('general/locale/code', $store->getId()),0,2);
					if($store->getCode() != "default"){
						$storeCode = $storeCode."-".$store->getCode();
					}

					/* 3SD CODE INSERT ALTERNATE IF URL EXISTS */
					if($url){
						$headBlock->addLinkRelThis('alternate"' . ' hreflang="' . $storeCode, $url);
					}
				}
			}
			return $this;
		}

	/* public function alternateLinks(){
		$headBlock = Mage::app()->getLayout()->getBlock('head');
		$stores = Mage::app()->getStores();
		$prod = Mage::registry('current_product');
		$categ = Mage::registry('current_category');
		$url = "";
		$urlSuffix = "";

		if($headBlock){
				if(Mage::helper('tejar_alternate')->isCanonical()){
				$headBlock->addLinkRel('canonical', $url);
			}
			foreach ($stores as $store){
				if($prod){
					$categ ? $categId=$categ->getId() : $categId = null;
					// $url = $prod->getProductUrl();
					$url = $store->getBaseUrl() .$prod->getUrlKey();
				 //$url = $store->getBaseUrl() . Mage::helper('tejar_alternate')->rewrittenProductUrl($prod->getId(), $categId, $store->getId());
				}elseif($categ){
				   $urlSuffix = rtrim(str_replace(Mage::getBaseUrl(),'',Mage::helper('core/url')->getCurrentUrl()),'/');
					$url = $store->getBaseUrl().$urlSuffix;
				}else{
				   $url =  $store->getBaseUrl();
				   $urlSuffix = rtrim(str_replace(Mage::getBaseUrl(),'',Mage::helper('core/url')->getCurrentUrl()),'/');
				   //$urlSuffix = Mage::helper('tejar_alternate')->getPageDefaultUrlKey();
				   $currentModuleName = Mage::app()->getFrontController()->getRequest()->getModuleName();
					if($store->getCode()=='pk'){
						//echo "---> ".Mage::getBaseUrl();die;
					}
					//if(strtolower($currentModuleName)=="cms"){
						$url = $url.$urlSuffix;
					//}
			    }
				$storeCode = substr(Mage::getStoreConfig('general/locale/code', $store->getId()),0,2);
				if($store->getCode() != "default"){
					$storeCode = $storeCode."-".$store->getCode();
				}
				$headBlock->addLinkRelThis('alternate"' . ' hreflang="' . $storeCode, $url);
			}

			//echo $configValue;
			//$headBlock->addLinkRel('canonical"' . ' hreflang="' . "ddd", $url);
		}
	   return $this;
	} */
}
