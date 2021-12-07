<?php
/**
 * Catalog product controller
 *
 * @category   Tejar
 * @package    Tejar_Adminhtml
 * @author     Zeeshan <zeeshan.zeshan123@gmail.com>
 */
require_once 'Mage/Adminhtml/controllers/Catalog/ProductController.php';
class Tejar_Adminhtml_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController
{ 

    const XML_PATH_PRODUCT_WEBHOOK_URL		          = 'catalog/webhook/product_url';

	public function quickCreateAction()
    { 
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
			
		//--- Set product Categories as parent..	
		if($product->getTypeId()=="simple"){
			$product->setCategoryIds($configurableProduct->getCategoryIds());
		}
			//Mage::log($configurableProduct->getId(), null, 'zeeshan.log');
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
            $product->setName($configurableProduct->getName() . ' - ' . implode(' - ', $autogenerateOptions));
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
            //--- Set Product SKU
            if(!$product->getSku()){
			$product->setSku(Mage::helper('tejarobservpricing')->getRandomSKU())->save();	
            }
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

            $storeId = $product->getStore()->getStoreId();
			$request = $this->getRequest();

            $product->save();
            $result['random_sku'] = Mage::helper('tejarobservpricing')->getRandomSKU();
            $result['product_id'] = $product->getId();

            $webhookURL = Mage::getStoreConfig(self::XML_PATH_PRODUCT_WEBHOOK_URL, $storeId);
            if($webhookURL){
				$adminSession = Mage::getSingleton('admin/session');
				$user = $adminSession->getUser();
				$data['action'] = "new";
				if($request->getParam('id')){
					$data['action'] = "edit";
				}
				$data['user'] = array(
					'userid'	=> $user->getUserId(),
					'username' => $user->getUsername(),
					'email' => $user->getEmail()
				);
				$data['product'] = array('entity_id' => $product->getId(),'store_id' => $product->getStore()->getStoreId());
				$data = serialize($data);
				$data = escapeshellarg($data);
				$output = shell_exec("php /usr/share/nginx/tejar/shell/productWebhook.php quick_create {$data}  > /dev/null&");
			}

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
	 * post product 
	 *
	 * @param string product
	 * @return array
	 */
	public function setProductWebhook($data,$storeId = 0)
	{
		if(!empty($data)){
			$webhookURL = Mage::getStoreConfig(self::XML_PATH_PRODUCT_WEBHOOK_URL, $storeId);
			if($webhookURL){
			$data = json_encode($data);
			$ch = curl_init($webhookURL);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data))
			);
			$response = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);
		}
		}
	}
		
	 /**
     * Delete product action
     */
    public function deleteAction()
    {
		if($this->_isAllowedAction('delete')){
			if ($id = $this->getRequest()->getParam('id')) {
				$product = Mage::getModel('catalog/product')
					->load($id);
				$sku = $product->getSku();
				try {
					$product->delete();
					$this->_getSession()->addSuccess($this->__('The product has been deleted.'));
				} catch (Exception $e) {
					$this->_getSession()->addError($e->getMessage());
				}
			}
			$this->getResponse()
				->setRedirect($this->getUrl('*/*/', array('store'=>$this->getRequest()->getParam('store'))));
		} else {
			
			$this->_forward('denied');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return $this;
		}
    }
	
	public function massDeleteAction()
    {	
		if($this->_isAllowedAction('mass_delete')){
			$productIds = $this->getRequest()->getParam('product');
			if (!is_array($productIds)) {
				$this->_getSession()->addError($this->__('Please select product(s).'));
			} else {
				if (!empty($productIds)) {
					try {
						foreach ($productIds as $productId) {
							$product = Mage::getSingleton('catalog/product')->load($productId);
							Mage::dispatchEvent('catalog_controller_product_delete', array('product' => $product));
							$product->delete();
						}
						$this->_getSession()->addSuccess(
							$this->__('Total of %d record(s) have been deleted.', count($productIds))
						);
					} catch (Exception $e) {
						$this->_getSession()->addError($e->getMessage());
					}
				}
			}
			$this->_redirect('*/*/index');
		} else {
			$this->_forward('denied');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return $this;
		}
    }

    // 	/**
    //  * Save product action
    //  */
    // public function saveAction()
    // {
    //     $storeId        = $this->getRequest()->getParam('store');
    //     $redirectBack   = $this->getRequest()->getParam('back', false);
    //     $productId      = $this->getRequest()->getParam('id');
    //     $isEdit         = (int)($this->getRequest()->getParam('id') != null);
    //     $data = $this->getRequest()->getPost();
    //     if ($data) {
    //         $this->_filterStockData($data['product']['stock_data']);
    //         $product = $this->_initProductSave();
    //         try {
	// 			if(!$isEdit){
	// 				$product->save();
	// 				$productId = $product->getId();
	// 			}
	// 			$request = $this->getRequest();
	// 			$adminSession = Mage::getSingleton('admin/session');
	// 			$user = $adminSession->getUser();
	// 			$storeId = $product->getStore()->getStoreId();
	// 			$data = array();
	// 			$webhookURL = Mage::getStoreConfig(self::XML_PATH_PRODUCT_WEBHOOK_URL, $storeId);
	// 			if($webhookURL){
	// 				$data['action'] = "new";
	// 				if($request->getParam('id')){
	// 					$data['action'] = "edit";
	// 				}
	// 				$data['user'] = array(
	// 					'userid'	=> $user->getUserId(),
	// 					'username' => $user->getUsername(),
	// 					'email' => $user->getEmail()
	// 				);
	// 				$mainProduct = Mage::getModel('catalog/product')->setStoreId($product->getStore()->getStoreId())->load($productId);
	// 				if($product->getAttributeSetId()){
	// 					$attributeSetModel = Mage::getModel("eav/entity_attribute_set");
	// 					$attributeSetModel->load($product->getAttributeSetId());
	// 					$attributeSetName  = $attributeSetModel->getAttributeSetName();
	// 					$data['attributeSetName'] = $attributeSetName;	
	// 				}
	// 				if($product->getCategoryIds()){
	// 					if($product->getCategoryIds() > 0){
	// 						$categoriesIds = $product->getCategoryIds();
	// 						$categoriesIds = array_merge($categoriesIds,$mainProduct->getCategoryIds());
	// 						$collection = Mage::getResourceModel('catalog/category_collection');
	// 						$nameId = $collection->getAttribute('name')->getAttributeId();
	// 						$collection->getSelect(array('name'))
	// 							->columns(array('name' => 'IF(at_name.value_id > 0, at_name.value, at_name_default.value)'))
	// 							->joinInner(array('at_name_default' => 'catalog_category_entity_varchar'),"( `at_name_default`.`entity_id` = `e`.`entity_id` ) AND ( `at_name_default`.`attribute_id` = '".$nameId."' ) AND `at_name_default`.`store_id` = 0")
	// 							->joinLeft(array('at_name' => 'catalog_category_entity_varchar'),"( `at_name`.`entity_id` = `e`.`entity_id` ) AND ( `at_name`.`attribute_id` = '".$nameId."' ) AND ( `at_name`.`store_id` = ". Mage::app()->getStore()->getId() ." )")
	// 							->Where("e.entity_id IN(".join(',',$categoriesIds).")");
	// 						$categories = array();
	// 						foreach($collection as $item){
	// 							$categories[$item->getId()] = $item->getName();
	// 						}
	// 						$data['categories'] = array();
	// 						if($mainProduct->getCategoryIds() > 0){
	// 							$data['categories']['before_update'] = array();
	// 							foreach($mainProduct->getCategoryIds() as $mainCategotyId){
	// 								$data['categories']['before_update'][$mainCategotyId] = $categories[$mainCategotyId];
	// 							}
	// 						}
	// 						$data['categories']['updated'] = array();
	// 						foreach($product->getCategoryIds() as $categotyId){
	// 							$data['categories']['updated'][$categotyId] = $categories[$categotyId];
	// 						}
	// 					}
	// 				}
	// 				$attributes = $request->getPost('option_attr');
	// 				$data['product'] = array();
	// 				$postCollection = array();
	// 				$origData = array();
	// 				foreach($request->getPost('product') as $key => $item){
	// 					$postCollection[$key] = $item;
	// 					if(!is_array($item)){
	// 						if(!empty($attributes) && in_array($key,$attributes)){
	// 							$postCollection[$key."_value"] = $product->getAttributeText($key);
	// 						}
	// 					}
	// 				}
	// 				foreach($request->getPost('product') as $key => $item){
	// 					if(!is_array($item)){
	// 						$origData[$key] = $mainProduct->getData($key);
	// 						if(!empty($attributes) && in_array($key,$attributes)){
	// 							$origData[$key."_value"] = $mainProduct->getAttributeText($key);
	// 						}
	// 					} else if(is_array($item) && $key == "stock_data"){
	// 						foreach($item as $k => $i){
	// 							if($k == "is_in_stock"){
	// 								$origData[$key][$k] = $mainProduct->getData($k);
	// 							} else {
	// 								$origData[$key][$k] = $mainProduct->getStockItem()->getData($k);
	// 							}
	// 						}
	// 					} else if(is_array($item)){
	// 						$origData[$key] = $mainProduct->getData($key);
	// 					}
	// 				}
	// 				$postCollectionD = $postCollection;
	// 				$origDataD = $origData;
	// 				unset($postCollectionD['media_gallery']);
	// 				unset($origDataD['media_gallery']);
	// 				unset($postCollectionD['website_ids']);
	// 				unset($origDataD['website_ids']);
	// 				unset($postCollectionD['stock_data']);
	// 				unset($origDataD['stock_data']);
	// 				$result = array_diff_assoc($postCollectionD,$origDataD);
	// 				$result2 = array_diff_assoc($postCollection['stock_data'],$origData['stock_data']);
	// 				$defaultProduct = Mage::getModel('catalog/product')->setStoreId(0)->load($product->getId());
	// 				foreach($product->getData() as $pkey => $pData){
	// 					if($pData == false){
	// 						if($defaultProduct->hasData($pkey)){
	// 							$postCollection[$pkey] = $defaultProduct->getData($pkey);
	// 						} else {
	// 							$postCollection[$pkey] = $pData;						
	// 						}
	// 					} else {
	// 						$postCollection[$pkey] = $pData;						
	// 					}
	// 				}
	// 				$origDataDiff = array();
	// 				foreach($result as $k => $value){
	// 					$origDataDiff[$k] = $origData[$k];
	// 				}
	// 				$origDataDiff['stock_data'] = $result2;
	// 				$data['product']['before_update'] = $origDataDiff;
	// 				$data['product']['updated'] = $postCollection;
	// 				$relatedProductCounts = 0;
	// 				if($relatedProducts = $product->getData('related_link_data')){
	// 					$relatedProductCounts = count($relatedProducts);
	// 					$data['product']['updated']['related_product_count'] = $relatedProductCounts;
	// 				}
	// 				$crossSellProductCount = 0;
	// 				if($crossSellProducts = $product->getData('cross_sell_link_data')){
	// 					$crossSellProductCount = count($crossSellProducts);
	// 					$data['product']['updated']['cross_sell_product_count'] = $crossSellProductCount;
	// 				}
	// 				$upSellProductCount = 0;
	// 				if($upSellProducts = $product->getData('up_sell_link_data')){
	// 					$upSellProductCount = count($upSellProducts);
	// 					$data['product']['updated']['up_sell_product_count'] = $upSellProductCount;
	// 				}
	// 				if($product->getRelatedProductIds()){
	// 					if(count($product->getRelatedProductIds()) != $relatedProductCounts && $relatedProductCounts > 0){
	// 						$data['product']['before_update']['related_product_count'] = count($product->getRelatedProductIds());
	// 					}
	// 				}
	// 				if($product->getCrossSellProductIds()){
	// 					if(count($product->getCrossSellProductIds()) != $crossSellProductCount && $crossSellProductCount > 0){
	// 						$data['product']['before_update']['cross_sell_product_count'] = count($product->getCrossSellProductIds());
	// 					}
	// 				}
	// 				if($product->getUpSellProductIds()){
	// 					if(count($product->getUpSellProductIds()) != $upSellProductCount && $upSellProductCount > 0){
	// 						$data['product']['before_update']['up_sell_product_count'] = count($product->getUpSellProductIds());
	// 					}
	// 				}

	// 				$data['product']['website_status'] = array();
	// 				$storeCollection	 =	Mage::app()->getStores();
	// 				foreach($storeCollection as $store){
	// 					$sId			=	$store->getStoreId();
	// 					$sProduct		=	Mage::getModel('catalog/product')->setStoreId($sId)->load($productId);
	// 					$data['product']['website_status'][$sId] = $sProduct->getStatus();
	// 				}
					
	// 				if($product->getTypeId() == "simple"){
	// 					$parentIdArray = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());	
	// 					if(!empty($parentIdArray)){
	// 						$data['product']['updated']['type_id'] = "associate";
	// 					}
	// 				}
	// 				$this->setProductWebhook($data,$product->getStore()->getStoreId());
	// 			}
	// 			if($isEdit){
	// 				$product->save();
	// 				$productId = $product->getId();
	// 			}
    //             if (isset($data['copy_to_stores'])) {
    //                $this->_copyAttributesBetweenStores($data['copy_to_stores'], $product);
    //             }
    //             $this->_getSession()->addSuccess($this->__('The product has been saved.'));
    //         } catch (Mage_Core_Exception $e) {
    //             $this->_getSession()->addError($e->getMessage())
    //                 ->setProductData($data);
    //             $redirectBack = true;
    //         } catch (Exception $e) {
    //             Mage::logException($e);
    //             $this->_getSession()->addError($e->getMessage());
    //             $redirectBack = true;
    //         }
    //     }
    //     if ($redirectBack) {
    //         $this->_redirect('*/*/edit', array(
    //             'id'    => $productId,
    //             '_current'=>true
    //         ));
    //     } elseif($this->getRequest()->getParam('popup')) {
    //         $this->_redirect('*/*/created', array(
    //             '_current'   => true,
    //             'id'         => $productId,
    //             'edit'       => $isEdit
    //         ));
    //     } else {
    //         $this->_redirect('*/*/', array('store'=>$storeId));
    //     }
    // }

	    	/**
     * Save product action
     */
    public function saveAction()
    {
        $storeId        = $this->getRequest()->getParam('store');
        $redirectBack   = $this->getRequest()->getParam('back', false);
        $productId      = $this->getRequest()->getParam('id');
        $isEdit         = (int)($this->getRequest()->getParam('id') != null);
        $data = $this->getRequest()->getPost();
        if ($data) {
            $this->_filterStockData($data['product']['stock_data']);
            $product = $this->_initProductSave();
            try {
				if(!$isEdit){
					$product->save();
					$productId = $product->getId();
				}
				
				$request = $this->getRequest();
				$adminSession = Mage::getSingleton('admin/session');
				$user = $adminSession->getUser();
				$storeId = $product->getStore()->getStoreId();
				$data = array();
				$webhookURL = Mage::getStoreConfig(self::XML_PATH_PRODUCT_WEBHOOK_URL, $storeId);
				if($webhookURL){
					$data['action'] = "new";
					if($request->getParam('id')){
						$data['action'] = "edit";
					}
					
					$data['user'] = array(
						'userid'	=> $user->getUserId(),
						'username' => $user->getUsername(),
						'email' => $user->getEmail()
					);
					
					
					
					$mainProduct = Mage::getModel('catalog/product')->setStoreId($product->getStore()->getStoreId())->load($productId);
					if($product->getAttributeSetId()){
						$attributeSetModel = Mage::getModel("eav/entity_attribute_set");
						$attributeSetModel->load($product->getAttributeSetId());
						$attributeSetName  = $attributeSetModel->getAttributeSetName();
						$data['attributeSetName'] = $attributeSetName;	
					}
					
					
					if(!$isEdit){
						if($product->getCategoryIds()){
							if($product->getCategoryIds() > 0){
								$categoriesIds = $product->getCategoryIds();
								$collection = Mage::getResourceModel('catalog/category_collection');
								$nameId = $collection->getAttribute('name')->getAttributeId();
								$collection->getSelect(array('name'))
									->columns(array('name' => 'IF(at_name.value_id > 0, at_name.value, at_name_default.value)'))
									->joinInner(array('at_name_default' => 'catalog_category_entity_varchar'),"( `at_name_default`.`entity_id` = `e`.`entity_id` ) AND ( `at_name_default`.`attribute_id` = '".$nameId."' ) AND `at_name_default`.`store_id` = 0")
									->joinLeft(array('at_name' => 'catalog_category_entity_varchar'),"( `at_name`.`entity_id` = `e`.`entity_id` ) AND ( `at_name`.`attribute_id` = '".$nameId."' ) AND ( `at_name`.`store_id` = ". Mage::app()->getStore()->getId() ." )")
									->Where("e.entity_id IN(".join(',',$categoriesIds).")");
									
								$categories = array();
								foreach($collection as $item){
									$categories[$item->getId()] = $item->getName();
								}
								
								$data['categories'] = array();
								foreach($product->getCategoryIds() as $categotyId){
									$data['categories'][$categotyId] = $categories[$categotyId];
								}
							}
						}
						
						$origData = array();
						$attributes = $request->getPost('option_attr');
						foreach($request->getPost('product') as $key => $item){
							if(!is_array($item)){
								$origData[$key] = $mainProduct->getData($key);
								if(!empty($attributes) && in_array($key,$attributes)){
									$origData[$key."_value"] = $mainProduct->getAttributeText($key);
								}
							} else if(is_array($item) && $key == "stock_data"){
								foreach($item as $k => $i){
									if($k == "is_in_stock"){
										$origData[$key][$k] = $mainProduct->getData($k);
									} else {
										$origData[$key][$k] = $mainProduct->getStockItem()->getData($k);
									}
								}
							} else if(is_array($item)){
								$origData[$key] = $mainProduct->getData($key);
							} else {
								$origData[$key] = $mainProduct->getData($key);
							}
						}
						
						$mainDataArray = array();
						foreach($product->getData() as $key => $val){
							if(!array_key_exists($key, $origData)){
								$mainDataArray[$key] = $val;
							}
							
						}
						
						$data['product'] = array_merge($mainDataArray,$origData);
						
						$relatedProductCounts = 0;
						if($relatedProducts = $product->getData('related_link_data')){
							$relatedProductCounts = count($relatedProducts);
							$data['product']['related_product_count'] = $relatedProductCounts;
						}
						
						$crossSellProductCount = 0;
						if($crossSellProducts = $product->getData('cross_sell_link_data')){
							$crossSellProductCount = count($crossSellProducts);
							$data['product']['cross_sell_product_count'] = $crossSellProductCount;
						}
						
						$upSellProductCount = 0;
						if($upSellProducts = $product->getData('up_sell_link_data')){
							$upSellProductCount = count($upSellProducts);
							$data['product']['up_sell_product_count'] = $upSellProductCount;
						}
						
					} else {
				
						if($product->getCategoryIds()){
							if($product->getCategoryIds() > 0){
								$categoriesIds = $product->getCategoryIds();
								$categoriesIds = array_merge($categoriesIds,$mainProduct->getCategoryIds());
								$collection = Mage::getResourceModel('catalog/category_collection');
								$nameId = $collection->getAttribute('name')->getAttributeId();
								$collection->getSelect(array('name'))
									->columns(array('name' => 'IF(at_name.value_id > 0, at_name.value, at_name_default.value)'))
									->joinInner(array('at_name_default' => 'catalog_category_entity_varchar'),"( `at_name_default`.`entity_id` = `e`.`entity_id` ) AND ( `at_name_default`.`attribute_id` = '".$nameId."' ) AND `at_name_default`.`store_id` = 0")
									->joinLeft(array('at_name' => 'catalog_category_entity_varchar'),"( `at_name`.`entity_id` = `e`.`entity_id` ) AND ( `at_name`.`attribute_id` = '".$nameId."' ) AND ( `at_name`.`store_id` = ". Mage::app()->getStore()->getId() ." )")
									->Where("e.entity_id IN(".join(',',$categoriesIds).")");
								$categories = array();
								foreach($collection as $item){
									$categories[$item->getId()] = $item->getName();
								}
								$data['categories'] = array();
								if($mainProduct->getCategoryIds() > 0){
									$data['categories']['before_update'] = array();
									foreach($mainProduct->getCategoryIds() as $mainCategotyId){
										$data['categories']['before_update'][$mainCategotyId] = $categories[$mainCategotyId];
									}
								}
								$data['categories']['updated'] = array();
								foreach($product->getCategoryIds() as $categotyId){
									$data['categories']['updated'][$categotyId] = $categories[$categotyId];
								}
							}
						}
						
						
						$attributes = $request->getPost('option_attr');
						$data['product'] = array();
						$postCollection = array();
						$origData = array();
						foreach($request->getPost('product') as $key => $item){
							$postCollection[$key] = $item;
							if(!is_array($item)){
								if(!empty($attributes) && in_array($key,$attributes)){
									$postCollection[$key."_value"] = $product->getAttributeText($key);
								}
							}
						}
						
						$isDates = array("news_from_date","news_to_date","special_from_date","special_to_date","created_at","updated_at");
						$isPrice = array("price","special_price","list_price");
						foreach($request->getPost('product') as $key => $item){
							if(!is_array($item)){
								if(in_array($key,$isDates) && $mainProduct->getData($key)){
									$strtotime = strtotime($mainProduct->getData($key));
									$origData[$key] =  date('n/j/Y', $strtotime);
								} else if(in_array($key,$isPrice) && $mainProduct->getData($key)){
									$origData[$key] = Mage::getModel('directory/currency')->format($mainProduct->getData($key),array('display' => Zend_Currency::NO_SYMBOL), false);
									$origData[$key] = str_replace(',', '', $origData[$key]);
								} else {
									$origData[$key] = $mainProduct->getData($key);
								}
								if(!empty($attributes) && in_array($key,$attributes)){
									$origData[$key."_value"] = $mainProduct->getAttributeText($key);
								}
							} else if(is_array($item) && $key == "stock_data"){
								foreach($item as $k => $i){
									if($k == "is_in_stock"){
										$origData[$key][$k] = $mainProduct->getData($k);
									} else {
										$origData[$key][$k] = $mainProduct->getStockItem()->getData($k);
									}
								}
							} else if(is_array($item)){
								$origData[$key] = $mainProduct->getData($key);
							} else {
								$origData[$key] = $mainProduct->getData($key);
							}
						}
						foreach($postCollection as $postkey => $pvalue){
							if(in_array($postkey,$isPrice) && $pvalue){
								$postCollection[$postkey] = Mage::getModel('directory/currency')->format($pvalue,array('display' => Zend_Currency::NO_SYMBOL), false);
								$postCollection[$postkey] = str_replace(',', '', $postCollection[$postkey]);
							}
						}
						$postCollectionD = $postCollection;
						$origDataD = $origData;
						unset($postCollectionD['media_gallery']);
						unset($origDataD['media_gallery']);
						unset($postCollectionD['website_ids']);
						unset($origDataD['website_ids']);
						unset($postCollectionD['stock_data']);
						unset($origDataD['stock_data']);
						$result = array_diff_assoc($postCollectionD,$origDataD);
						
						$defaultProduct = Mage::getModel('catalog/product')->setStoreId(0)->load($product->getId());
						foreach($product->getData() as $pkey => $pData){
							if($pData == false && $storeId != 0){
								if($defaultProduct->hasData($pkey) && $product->getExistsStoreValueFlag($pkey) == false){
									$postCollection[$pkey] = $defaultProduct->getData($pkey);
								} else {
									$postCollection[$pkey] = $pData;						
								}
							} else {
								$postCollection[$pkey] = $pData;						
							}
						}
						foreach($postCollection as $postkey => $pvalue){
							if(!$pvalue){
								$postCollection[$postkey] = null;
							}
						}
						$origDataDiff = array();
						foreach($result as $k => $value){
							$origDataDiff[$k] = $origData[$k];
						}
						$rCategoryIds = array();
						if(array_key_exists('category_ids',$postCollection)){
							foreach($postCollection['category_ids'] as $ck => $cv){
								if(!in_array($cv,$rCategoryIds)){
									$rCategoryIds[] = $cv;
								}
							}
							$postCollection['category_ids'] = $rCategoryIds;
						}

						$result2 = array();
						foreach($postCollection['stock_data'] as $stockkey => $stockVal){
							if($stockkey == "original_inventory_qty"){
								$postCollection['stock_data'][$stockkey] = null;
							} else if($stockkey == "qty" || $stockkey == "max_sale_qty"){
								$postCollection['stock_data'][$stockkey] = (string)number_format((float)$stockVal, 4, '.', '');
							}
						}

						if($postCollection['stock_data']['qty'] == 0 && $postCollection['stock_data']['manage_stock'] == 1){
							$postCollection['stock_data']['is_in_stock'] = "0";
						} 
						
						if($postCollection['stock_data']['manage_stock'] == 0){
							$postCollection['stock_data']['is_in_stock'] = "1";
						}

						$result2 = array_diff_assoc($postCollection['stock_data'],$origData['stock_data']);

						$origDataDiff['stock_data'] = $result2;
						$data['product']['before_update'] = $origDataDiff;
						$data['product']['updated'] = $postCollection;
						// echo "<pre>";
						// var_dump($data);
						// echo "</pre>";
						// die;
						
						$relatedProductCounts = 0;
						if($relatedProducts = $product->getData('related_link_data')){
							$relatedProductCounts = count($relatedProducts);
							$data['product']['updated']['related_product_count'] = $relatedProductCounts;
						}
						$crossSellProductCount = 0;
						if($crossSellProducts = $product->getData('cross_sell_link_data')){
							$crossSellProductCount = count($crossSellProducts);
							$data['product']['updated']['cross_sell_product_count'] = $crossSellProductCount;
						}
						$upSellProductCount = 0;
						if($upSellProducts = $product->getData('up_sell_link_data')){
							$upSellProductCount = count($upSellProducts);
							$data['product']['updated']['up_sell_product_count'] = $upSellProductCount;
						}
						
						
						if($product->getRelatedProductIds()){
							if(count($product->getRelatedProductIds()) != $relatedProductCounts && $relatedProductCounts > 0){
								$data['product']['before_update']['related_product_count'] = count($product->getRelatedProductIds());
							}
						}
						if($product->getCrossSellProductIds()){
							if(count($product->getCrossSellProductIds()) != $crossSellProductCount && $crossSellProductCount > 0){
								$data['product']['before_update']['cross_sell_product_count'] = count($product->getCrossSellProductIds());
							}
						}
						if($product->getUpSellProductIds()){
							if(count($product->getUpSellProductIds()) != $upSellProductCount && $upSellProductCount > 0){
								$data['product']['before_update']['up_sell_product_count'] = count($product->getUpSellProductIds());
							}
						}
					}
					

					$data['product']['website_status'] = array();
					$storeCollection	 =	Mage::app()->getStores();
					foreach($storeCollection as $store){
						$sId			=	$store->getStoreId();
						$sProduct		=	Mage::getModel('catalog/product')->setStoreId($sId)->load($productId);
						$data['product']['website_status'][$sId] = $sProduct->getStatus();
					}
					
					if($product->getTypeId() == "simple"){
						$parentIdArray = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());	
						if(!empty($parentIdArray)){
							$data['product']['updated']['type_id'] = "associate";
						}
					}
					if($isEdit){
						$product->save();
						$productId = $product->getId();
					}
					if(!$data['product']['updated']['special_from_date'] && $product->getData('special_from_date')){
						$strtotime = strtotime($product->getData('special_from_date'));
						$data['product']['updated']['special_from_date']  =  date('n/j/Y', $strtotime);
						
					}
					$this->setProductWebhook($data,$product->getStore()->getStoreId());
				}
				// if($isEdit){
				// 	$product->save();
				// 	$productId = $product->getId();
				// }
                if (isset($data['copy_to_stores'])) {
                   $this->_copyAttributesBetweenStores($data['copy_to_stores'], $product);
                }
                $this->_getSession()->addSuccess($this->__('The product has been saved.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage())
                    ->setProductData($data);
                $redirectBack = true;
            } catch (Exception $e) {
                Mage::logException($e);
                $this->_getSession()->addError($e->getMessage());
                $redirectBack = true;
            }
        }
        if ($redirectBack) {
            $this->_redirect('*/*/edit', array(
                'id'    => $productId,
                '_current'=>true
            ));
        } elseif($this->getRequest()->getParam('popup')) {
            $this->_redirect('*/*/created', array(
                '_current'   => true,
                'id'         => $productId,
                'edit'       => $isEdit
            ));
        } else {
            $this->_redirect('*/*/', array('store'=>$storeId));
        }
    }

		/**
     * Create product duplicate
     */
    public function duplicateAction()
    {
        $product = $this->_initProduct();
		
        try {
            $newProduct = $product->duplicate();
			$request = $this->getRequest();
			$adminSession = Mage::getSingleton('admin/session');
			$user = $adminSession->getUser();
			$storeId = $newProduct->getStore()->getStoreId();
			$data = array();
			$webhookURL = Mage::getStoreConfig(self::XML_PATH_PRODUCT_WEBHOOK_URL, $storeId);
			if($webhookURL){
				
				$data['action'] = "duplicate";
				$data['user'] = array(
					'userid'	=> $user->getUserId(),
					'username' => $user->getUsername(),
					'email' => $user->getEmail()
				);
				
				
				
				$mainProduct = Mage::getModel('catalog/product')->setStoreId($newProduct->getStore()->getStoreId())->load($newProduct->getId());
				if($newProduct->getAttributeSetId()){
					$attributeSetModel = Mage::getModel("eav/entity_attribute_set");
					$attributeSetModel->load($newProduct->getAttributeSetId());
					$attributeSetName  = $attributeSetModel->getAttributeSetName();
					$data['attributeSetName'] = $attributeSetName;	
				}
				
				
				
					
				if($newProduct->getCategoryIds()){
					if($newProduct->getCategoryIds() > 0){
						$categoriesIds = $newProduct->getCategoryIds();
						$collection = Mage::getResourceModel('catalog/category_collection');
						$nameId = $collection->getAttribute('name')->getAttributeId();
						$collection->getSelect(array('name'))
							->columns(array('name' => 'IF(at_name.value_id > 0, at_name.value, at_name_default.value)'))
							->joinInner(array('at_name_default' => 'catalog_category_entity_varchar'),"( `at_name_default`.`entity_id` = `e`.`entity_id` ) AND ( `at_name_default`.`attribute_id` = '".$nameId."' ) AND `at_name_default`.`store_id` = 0")
							->joinLeft(array('at_name' => 'catalog_category_entity_varchar'),"( `at_name`.`entity_id` = `e`.`entity_id` ) AND ( `at_name`.`attribute_id` = '".$nameId."' ) AND ( `at_name`.`store_id` = ". Mage::app()->getStore()->getId() ." )")
							->Where("e.entity_id IN(".join(',',$categoriesIds).")");
							
						$categories = array();
						foreach($collection as $item){
							$categories[$item->getId()] = $item->getName();
						}
						
						$data['categories'] = array();
						foreach($newProduct->getCategoryIds() as $categotyId){
							$data['categories'][$categotyId] = $categories[$categotyId];
						}
					}
				}
				
				
				$option_attr = array();
				$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
				$query = $readConnection->select()
					->from('eav_attribute')
					->where('entity_type_id = ?', 4)
					->where('backend_type = ?', "int");
				$query->reset(Zend_Db_Select::COLUMNS);
				$query->columns(array('attribute_code'));
				$results = $readConnection->fetchAll($query);
				foreach($results as $row){
					$option_attr[] = $row['attribute_code'];
				}
				
				$origData = array();
				foreach($newProduct->getData() as $key => $item){
					if(!is_array($item)){
						$origData[$key] = $newProduct->getData($key);
						if(!empty($option_attr) && in_array($key,$option_attr)){
							$origData[$key."_value"] = $newProduct->getAttributeText($key);
						}
					} else if(is_array($item) && $key == "stock_data"){
						foreach($item as $k => $i){
							if($k == "is_in_stock"){
								$origData[$key][$k] = $newProduct->getData($k);
							} 
						}
					} else if(is_array($item)){
						$origData[$key] = $newProduct->getData($key);
					} else {
						$origData[$key] = $newProduct->getData($key);
					}
				}
				
				$data['product'] = $origData;
				
				$relatedProductCounts = 0;
				if($relatedProducts = $newProduct->getData('related_link_data')){
					$relatedProductCounts = count($relatedProducts);
					$data['product']['related_product_count'] = $relatedProductCounts;
				}
				
				$crossSellProductCount = 0;
				if($crossSellProducts = $newProduct->getData('cross_sell_link_data')){
					$crossSellProductCount = count($crossSellProducts);
					$data['product']['cross_sell_product_count'] = $crossSellProductCount;
				}
				
				$upSellProductCount = 0;
				if($upSellProducts = $newProduct->getData('up_sell_link_data')){
					$upSellProductCount = count($upSellProducts);
					$data['product']['up_sell_product_count'] = $upSellProductCount;
				}
				
				$this->setProductWebhook($data,$newProduct->getStore()->getStoreId());
						
					
			}
				
            $this->_getSession()->addSuccess($this->__('The product has been duplicated.'));
            $this->_redirect('*/*/edit', array('_current'=>true, 'id'=>$newProduct->getId()));
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($e->getMessage());
            $this->_redirect('*/*/edit', array('_current'=>true));
        }
    }
	
	protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/products/' . $action);
    }		
}