<?php
/**
 * ObservPricing Observer
 *
 * @category   Tejar
 * @package    Tejar_ObservPricing
 * @class      Tejar_Observpricing_Model_Observer
 * @author     Zeeshan <zeeshan.zeeshan123@gmail.com>
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Tejar_Observpricing_Model_Observer extends Varien_Event_Observer
{

	const XML_PATH_PRODUCT_WEBHOOK_URL		          = 'catalog/webhook/product_url';

	const SWATCH_LABEL_SUFFIX = '-swatch';
	/*
	*@name        productSaveAfter
	*@description This Observer function calls another class function to return simple product price..
	*@parameters  Varien_Event_Observer
	*@returns     Object
	*/
	public function simpleProductPrice(Varien_Event_Observer $observer){

		$event   = $observer->getEvent();
		$product = $event->getProduct();
		$qty     = $event->getQty();

		if($product->getTypeId()=="simple"){
			return false;
		}
		//Mage::log($observer, null, 'confPricing.log');
		//--- process percentage discounts only for simple products
		$selectedAttributes = array();
		if ($product->getCustomOption('attributes')) {
			//Mage::log('yes-----', null, 'confPricing.log');
			$selectedAttributes = unserialize($product->getCustomOption('attributes')->getValue());
		}
		if (sizeof($selectedAttributes)) return $this->getSimpleProductPrice($qty, $product);
	}

	/*
	*@name        getSimpleProductPrice
	*@description This function is called by the observer function simpleProductPrice
	*@parameters  Varien_Event_Observer
	*@returns     Object
	*/
	public function getSimpleProductPrice($qty=null, $product){
        $cfgId = $product->getId();
        $product->getTypeInstance(true)
            ->setStoreFilter($product->getStore(), $product);
        $attributes = $product->getTypeInstance(true)
            ->getConfigurableAttributes($product);
        $selectedAttributes = array();
        if ($product->getCustomOption('attributes')) {
            $selectedAttributes = unserialize($product->getCustomOption('attributes')->getValue());
        }
        $db = Mage::getSingleton('core/resource')->getConnection('core_read');
        $dbMeta = Mage::getSingleton('core/resource');
        $sql = <<<SQL
SELECT main_table.entity_id FROM {$dbMeta->getTableName('catalog/product')} `main_table` INNER JOIN
{$dbMeta->getTableName('catalog/product_super_link')} `sl` ON sl.parent_id = {$cfgId}
SQL;
        foreach($selectedAttributes as $attributeId => $optionId) {
            $alias = "a{$attributeId}";
            $sql .= ' INNER JOIN ' . $dbMeta->getTableName('catalog/product') . "_int" . " $alias ON $alias.entity_id = main_table.entity_id AND $alias.attribute_id = $attributeId AND $alias.value = $optionId AND $alias.entity_id = sl.product_id";
        }
        $id = $db->fetchOne($sql);
		//Mage::log(Mage::getModel("catalog/product")->load($id)->getFinalPrice($qty), null, 'confPricing.log');
        //return
		$fp = Mage::getModel("catalog/product")->load($id)->getFinalPrice($qty);
		return $product->setFinalPrice($fp);
	}
	
	public function adminhtmlCatalogProductEditPrepareForm($observer)
	{ 
		$event = $observer->getEvent();
		$form = $event->getForm();
		if (!Mage::registry('product')->getId()) {
			if ($form->getElement('sku')) {
				$autoGenerateSku = Mage::helper('tejarobservpricing')->getRandomSKU();
				$form->getElement('sku')->setValue($autoGenerateSku);
			}
		} 
		if ($form->getElement('sku')) {
			if(!$this->_isAllowedAction('mass_delete')){
				$form->getElement('sku')->setClass('disabled');
				$form->getElement('sku')->setReadonly(true, false);
			}
		}
		if ($form->getElement('model')) {
			$form->getElement('model')->setMaxlength(30);
		}
	}

		/**
	      * Event Product PrePare Save
	      *
	      * @var Tejar_Observpricing_Model_Observer catalogProductPrepareSave
	 	 *
	 	 * @return Product
	      */
	 	public function catalogProductPrepareSave(Varien_Event_Observer $observer){

	 		$event   = $observer->getEvent();
			$product = $event->getProduct();
			$request = $event->getRequest();
			$adminSession = Mage::getSingleton('admin/session');
			$user = $adminSession->getUser();
			$storeId = $product->getStore()->getStoreId();

			$data = array();

	 		$this->setProductSku($product);
	 		$this->setProductName($product);
			$this->setProductModel($product);
			// $this->setProductDimWeight($product);
			$this->setProductMediaGallery($product);
			$this->setProductCustomStock($product);
			/*
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
				$mainProduct = Mage::getModel('catalog/product')->setStoreId($product->getStore()->getStoreId())->load($product->getId());
				if($product->getAttributeSetId()){
					$attributeSetModel = Mage::getModel("eav/entity_attribute_set");
					$attributeSetModel->load($product->getAttributeSetId());
					$attributeSetName  = $attributeSetModel->getAttributeSetName();
					$data['attributeSetName'] = $attributeSetName;	
				}
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
				$result2 = array_diff_assoc($postCollection['stock_data'],$origData['stock_data']);
				$defaultProduct = Mage::getModel('catalog/product')->setStoreId(0)->load($product->getId());
				foreach($product->getData() as $pkey => $pData){
					if($pData == false){
						if($defaultProduct->hasData($pkey)){
							$postCollection[$pkey] = $defaultProduct->getData($pkey);
						} else {
							$postCollection[$pkey] = $pData;						
						}
					} else {
						$postCollection[$pkey] = $pData;						
					}
				}
				$origDataDiff = array();
				foreach($result as $k => $value){
					$origDataDiff[$k] = $origData[$k];
				}
				$origDataDiff['stock_data'] = $result2;
				$data['product']['before_update'] = $origDataDiff;
				$data['product']['updated'] = $postCollection;
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
					if(count($product->getRelatedProductIds()) != $relatedProductCounts){
						$data['product']['before_update']['related_product_count'] = count($product->getRelatedProductIds());
					}
				}
				if($product->getCrossSellProductIds()){
					if(count($product->getCrossSellProductIds()) != $crossSellProductCount){
						$data['product']['before_update']['cross_sell_product_count'] = count($product->getCrossSellProductIds());
					}
				}
				if($product->getUpSellProductIds()){
					if(count($product->getUpSellProductIds()) != $upSellProductCount){
						$data['product']['before_update']['up_sell_product_count'] = count($product->getUpSellProductIds());
					}
				}
				if($product->getTypeId() == "simple"){
					$parentIdArray = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());	
					if(!empty($parentIdArray)){
						$data['product']['updated']['type_id'] = "associate";
					}
				}
				$this->setProductWebhook($data,$product->getStore()->getStoreId());
			} else {
				$mainProduct = Mage::getModel('catalog/product')->setStoreId($product->getStore()->getStoreId())->load($product->getId());
			} */

			$mainProduct = Mage::getModel('catalog/product')->setStoreId($product->getStore()->getStoreId())->load($product->getId());
			
			if($request->getActionName() != NULL && $request->getParam('id') && $storeId == 3){
				if(!$product->getData('sourcing') && $mainProduct->getData('sourcing')){
					$output = shell_exec("php /usr/share/nginx/tejar/shell/airTableHandler.php {$product->getStore()->getCode()} product_id {$product->getId()} sourcing_disabled");
				}
			}
	 	}

		 		/**
	      * Event Product PrePare Save
	      *
	      * @var Tejar_Observpricing_Model_Observer catalogProductPrepareSave
	 	 *
	 	 * @return Product
	      */
		  public function catalogProductDeleteAfterDone(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			$product = $event->getProduct();
			$storeId = $product->getStore()->getStoreId();
			$webhookURL = Mage::getStoreConfig(self::XML_PATH_PRODUCT_WEBHOOK_URL, $storeId);
            if($webhookURL){
			$adminSession = Mage::getSingleton('admin/session');
			$user = $adminSession->getUser();
			$data = array();
			$data['action'] = "delete";
			$data['user'] = array(
				'userid'	=> $user->getUserId(),
				'username' => $user->getUsername(),
				'email' => $user->getEmail()
			);
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
			foreach($product->getData() as $key => $item){
				if(!is_array($item)){
					$origData[$key] = $product->getData($key);
					if(!empty($option_attr) && in_array($key,$option_attr)){
						$origData[$key."_value"] = $product->getAttributeText($key);
					}
				} else if(is_array($item) && $key == "stock_data"){
					foreach($item as $k => $i){
						if($k == "is_in_stock"){
							$origData[$key][$k] = $product->getData($k);
						} else {
							$origData[$key][$k] = $product->getStockItem()->getData($k);
						}
					}
				} else if(is_array($item)){
					$origData[$key] = $product->getData($key);
				} else {
					$origData[$key] = $product->getData($key);
				}
			}
			
			$data['product'] = $origData;
			$data['product']['store'] = $product->getStore();
			$data['product']['store_id'] = $product->getStore()->getStoreId();
			if($product->getStore()->getStoreId() == 0){
				$request = Mage::app()->getRequest();	
				if($request->getParam('store')){
					$data['product']['store_id'] = $request->getParam('store');
				}
			}
			$this->setProductWebhook($data,$product->getStore()->getStoreId());
		}
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
	      * Set product Sku auto genrate
	      *
	      * @return Tejar_Observpricing_Model_Observer
	      */
	 	// public function setProductSku($product){

	 	// 	if(!$product->getSku()){
	 	// 		$product->setSku(Mage::helper('tejarobservpricing')->getRandomSKU());
	 	// 	}

	 	// 	return true;
		//  }
		 

		 	 	 /**
	      * Set product Sku auto genrate
	      *
	      * @return Tejar_Observpricing_Model_Observer
	      */
		  public function setProductSku($product){
			if(!$this->_isAllowedAction('mass_delete')){
				if($product->getId()){
					$_product = Mage::getModel('catalog/product')->load($product->getId());
					if($product->getSku() != $_product->getSku()) {
						$product->setSku($_product->getSku());	
					}
				}
			} else {
				if($product->getSku() == "generate"){
					$product->setSku(Mage::helper('tejarobservpricing')->getRandomSKU());
				} 		
			}
			if(!$product->getSku()) {
				$product->setSku(Mage::helper('tejarobservpricing')->getRandomSKU());
			}	
	 		return true;
	 	}



	 	 /**
	      * Set product Name filter
	      *
	      * @return Tejar_Observpricing_Model_Observer
	      */
				public function setProductName($product){
			 		// $name = preg_replace('/\s+/', ' ', $product->getName());
			 		// $name = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $name);
					// $name = trim($name);
					$name = $product->getName();
			 		$productAttrLimit = Mage::getStoreConfig('intenso/product_page/product_name_attribute_limit');
			 		if($productAttrLimit){
			 			$name = mb_strimwidth($name , 0, $productAttrLimit, '', 'UTF-8');
			 		}
			 		if($product->getName()){
			 			$product->setName($name);
			 		}
			 	}

		/**
	      * Set product Model filter
	      *
	      * @return Tejar_Observpricing_Model_Observer
	      */
				public function setProductModel($product){
					$model = $product->getModel();
					if($model){
						// $model = preg_replace('/\s+/', ' ', $model);
						// $model = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $model);
						// $model = trim($model);
						$modelAttrLimit = Mage::getStoreConfig('intenso/product_page/product_model_attribute_limit');
						if($modelAttrLimit){
							$model = mb_strimwidth($model , 0, $modelAttrLimit, '', 'UTF-8');
						}
						$product->setModel($model);
					}
			 	}

				/**
	      * Set product Dim Weight filter
	      *
	      * @return Tejar_Observpricing_Model_Observer
	      */
	 	// public function setProductDimWeight($product){
	 	// 	$dimWeight = $product->getDimWeight();
		// 	if($dimWeight){
		// 		$dimWeight = preg_replace('/\s+/', ' ', $dimWeight);
		// 		$dimWeight = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $dimWeight);
		// 		$dimWeight = trim($dimWeight);
		// 		$product->setDimWeight($dimWeight);
		// 	}
	 	// }

		/**
		 * Set Product MediaGallery filter
		 *
		 * @return Tejar_Observpricing_Model_Observer
		 */
		public function setProductMediaGallery($product){
			$defaultProductName = $product->getAttributeDefaultValue('name');
			$currentProductName = $product->getName()!=false?$product->getName():$defaultProductName;
			$currentProductName = $currentProductName!=false?$currentProductName:$product->getOrigData('name');
			$mediaGallery 		= $product->getMediaGallery('images');
			if($mediaGallery){
				$mediaGallery = Mage::helper('core')->jsonDecode($mediaGallery);
				foreach($mediaGallery as $key => $gallery){
					$matches = preg_grep('/'.self::SWATCH_LABEL_SUFFIX.'/i',array($mediaGallery[$key]['label']));
					if(empty($matches)){
						// CURRENT STORE LABEL UPDATE
						$position = "";
						if(array_key_exists('position',$mediaGallery[$key])){
							if((int)$mediaGallery[$key]['position'] > 1){
								$position = ' - ' . $mediaGallery[$key]['position'];
							}
							$mediaGallery[$key]['label'] = $currentProductName . $position;
						}
						// DEFAULT STORE LABEL UPDATE
						$position = "";
						if(array_key_exists('position_default',$mediaGallery[$key])){
							if((int)$mediaGallery[$key]['position_default'] > 1){
								$position = ' - ' . $mediaGallery[$key]['position'];
							}
							$mediaGallery[$key]['label_default'] = $defaultProductName . $position;
						}
					} else {
						// if(array_key_exists('label_default',$mediaGallery[$key])){
						// 	$mediaGallery[$key]['label'] = $mediaGallery[$key]['label_default'];
						// }
					}
				}
				$result = array('images' => Mage::helper('core')->jsonEncode($mediaGallery), 'values' => $product->getMediaGallery('values'));
				$product->setMediaGallery($result);
			}
		}

	 	 /**
	      * Set product custom stock
	      *
	      * @return Tejar_Observpricing_Model_Observer
	      */
	 	public function setProductCustomStock($product){

	 		$productCustomStockStatus = $product->getAttributeText('custom_stock_availability');
	 		$prodOrigData = $product->getOrigData();

	 		// if($prodOrigData['stock_item']['use_config_manage_stock']!== null && trim($prodOrigData['stock_item']['use_config_manage_stock']) !== trim($product->getStockData('use_config_manage_stock'))){
	 		// 	return $product->setCustomStockAvailability('');
	 		// }

	 		//--- Set Stock Status in accordance with Custom Stock...
	 		if($product->getStockData('is_in_stock') && $productCustomStockStatus=="No Longer Available" || $productCustomStockStatus=="Discontinued") {
	 			$product->setStockData(array(
	             'use_config_manage_stock' => 0,
	             'is_in_stock' => 0,
	             'qty' => 0,
	             'manage_stock' => 1,
	             'use_config_notify_stock_qty' => 0
	 			));
	 		} elseif($product->getStockData('is_in_stock')==0 && $productCustomStockStatus=="Pre-order" || $productCustomStockStatus=="Backordered") {
	 			$product->setStockData(array(
	             'use_config_manage_stock' => 1,
	             'manage_stock' => 0,
	             'use_config_notify_stock_qty' => 1
	 			));
	 		}

	 		return true;
	 	}


	public function setDefaultSkuAndStock($product, $defaultStoreId){
		//--- Trim Product name to avoid Whitespaces issues..


		//--- Check if SKU is already set, Insert only if there is no SKU...
		if(!$product->getSku()){
			$product->setSku(Mage::helper('tejarobservpricing')->getRandomSKU())->save();
			//$product->getResource()->save($product);
		}
	}

	/*
	*@name        isCustomCostAvailable
	*@description This Observer function set prices per store based for currently inserted product..
	*@parameters  varchar StoreId
	*@returns     boolean
	*/
	public function isCustomCostAvailable($storeId){
		//--- Get Store Based Custom Cost and Custom Shipping Cost...
		$customCost = Mage::getStoreConfig('tejarobservpricing/store_custom_cost/custom_cost',$storeId);
		$customShippingCost = Mage::getStoreConfig('tejarobservpricing/store_custom_cost/custom_shipping_cost',$storeId);

		//--- if Custom Cost or Custom Shipping Cost is not available return false..
		if(!$customCost || !$customShippingCost){
			return false;
		}
		return true;
	}

	protected function getStoreProductCollection($productIds, $params = array(), $conditions = array()){
		$products = array();
		$collection = Mage::getModel('catalog/product')->getCollection();
		$readAdapter = $collection->getConnection('core_read');
		// $include = array("model","warranty","sourcing","list_price","special_price","price","status","weight","dim_weight");
		$include = array();
		if(!empty($params)){
			$include = array_merge($include,$params);
		} 
		$attributes = Mage::getSingleton('eav/config')
			->getEntityType(Mage_Catalog_Model_Product::ENTITY)
					->getAttributeCollection()
					->addSetInfo();
		$attributes->getSelect()->where('attribute_code IN(?)',$include);
		$_productCollection = Mage::getResourceModel('catalog/product_collection');
		$_productCollection->getSelect()->joinInner(array('at_website' => 'catalog_product_website'),"(`at_website`.`product_id` = `e`.`entity_id`)",array("store_id" => "website_id"));
		if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
			Mage::getModel('cataloginventory/stock_item')->addCatalogInventoryToProductCollection($_productCollection);
		}
		if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $_productCollection->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
        }
		$columns = array();
		$attrCodes = array();
		foreach($attributes as $attribute){
			if ($attribute->isScopeStore() || $attribute->isScopeWebsite()) {
				$attrId = $attribute->getAttributeId();
				$attrCode = $attribute->getAttributeCode();
				$attrCodes[$attrCode] = $attrCode."_flag";
				$attrTable = $attribute->getBackend()->getTable();
				$columns[$attrCode] = "IF(at_{$attrCode}.value_id > 0, at_{$attrCode}.value, at_{$attrCode}_default.value)";
				$columns[$attrCode."_flag"] = "IF(at_{$attrCode}.value_id > 0, TRUE, FALSE)";
				$_productCollection->getSelect()
				->joinInner(array("at_{$attrCode}_default" => $attrTable),"(`at_{$attrCode}_default`.`entity_id` = `e`.`entity_id`) AND (`at_{$attrCode}_default`.`attribute_id` = {$attrId}) AND `at_{$attrCode}_default`.`store_id` = 0",array())
				->joinLeft(array("at_{$attrCode}" => $attrTable),"(`at_{$attrCode}`.`entity_id` = `e`.`entity_id`) AND (`at_{$attrCode}`.`attribute_id` = {$attrId}) AND `at_{$attrCode}`.`store_id` = `at_website`.`website_id`",array());
			} else if($attribute->isScopeGlobal()) {
				$attrId = $attribute->getAttributeId();
				$attrCode = $attribute->getAttributeCode();
				$attrCodes[$attrCode] = $attrCode."_flag";
				$attrTable = $attribute->getBackend()->getTable();
				$columns[$attrCode] = "at_{$attrCode}.value";
				$columns[$attrCode."_flag"] = "IF(at_{$attrCode}.value_id > 0, TRUE, FALSE)";
				$_productCollection->getSelect()
				->joinLeft(array("at_{$attrCode}" => $attrTable),"(`at_{$attrCode}`.`entity_id` = `e`.`entity_id`) AND (`at_{$attrCode}`.`attribute_id` = {$attrId}) AND `at_{$attrCode}`.`store_id` = 0",array());				
			}
		}
		if(!empty($columns)){
			$_productCollection->getSelect()->columns($columns);
		}
		$_productCollection->getSelect()->where("e.entity_id IN(?)",$productIds);
		if(!empty($conditions)){
			foreach($conditions as $attribute => $condition){
				if(is_array($condition) && !empty($condition)){
					foreach($condition as $operator => $value){
						$_productCollection->getSelect()->where("{$attribute} {$operator}(?)",$value);
					}
				}
			}
		}
		foreach ($readAdapter->fetchAll($_productCollection->getSelect()) as $row) {
			$product = Mage::getModel('catalog/product');
			$product->setStoreId($row['store_id']);
			$product->setData($row);
			foreach($attrCodes as $key => $attr){
				if(array_key_exists($attr,$row)){
					if($row[$attr]){
						$product->setExistsStoreValueFlag($key);
						// $product->setAttributeDefaultValue($key,$row[$attr]);
					}
				}
			}
			$products[$row['store_id']][$row["entity_id"]] = $product;
		}
		return $products;
	}
	public function conditionDate($store, $dateFrom = null, $dateTo = null)
    {
		$storeTimeStamp = Mage::app()->getLocale()->storeTimeStamp($store);
        $fromTimeStamp  = strtotime($dateFrom);
        $toTimeStamp    = strtotime($dateTo);
        if ($dateTo) {
            // fix date YYYY-MM-DD 00:00:00 to YYYY-MM-DD 23:59:59
            $toTimeStamp += 86400;
        }
		$result = false;
        if (!is_empty_date($dateFrom) && $storeTimeStamp < $fromTimeStamp) {
        } elseif (!is_empty_date($dateTo) && $storeTimeStamp > $toTimeStamp) {
        } else {
            $result = true;
        }
		if(($storeTimeStamp < $toTimeStamp) || (is_empty_date($dateTo))){
			 $result = true;
		}
		return $result;
	}

	/**
     * Event Product Save After
     *
     * @var Tejar_Observpricing_Model_Observer catalogProductSaveAfter
	 *
	 * @return Product
     */
	public function catalogProductSaveAfter(Varien_Event_Observer $observer){

		$event 		    	 = 	$observer->getEvent();
		$product    		 = 	$event->getProduct();
		$productId			 =	$product->getId();
		$helper				 =	Mage::helper('tejarobservpricing');
		$storeCollection	 =	Mage::app()->getStores();
		$defaultStoreId		 = 	Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId();
		$defaultProduct 	 = 	Mage::getModel('catalog/product')->setStoreId($defaultStoreId)->load($productId);
		$defaultPrice		 =	$defaultProduct->getPrice();
		$defaultSpecialPrice = 	$defaultProduct->getSpecialPrice();
		$defaultListPrice  	 = 	$defaultProduct->getListPrice();
		$request			= 	Mage::app()->getRequest();
		$postProduct		 =	$request->getPost('product');
		$updateSpecialPrice = false;
		if(array_key_exists('special_from_date',$postProduct)){
			if($postProduct['special_from_date'] == ""){
				$updateSpecialPrice = true;
			}
		}
		$paramsProduct		 = $request->getParams();
		if(array_key_exists('module',$paramsProduct)){
			if($paramsProduct['module'] == "product"){
				$updateSpecialPrice = true;
			}
		}


		if($defaultListPrice){
			Mage::getSingleton('catalog/product_action')->updateAttributes(array($productId), array("price"=> $defaultListPrice), $defaultStoreId);
		}

		if($product->getTypeId() == "configurable"){
			$products = $product->getTypeInstance(true)->getUsedProductIds($product);
			if(!empty($products)){
				$prices = array();
				$specialPrices = array();
				$stockPrices = array();
				$stockSpecialPrices = array();
				$disablePrices = array();
				$disableSpecialPrices = array();
				$disables = array();
				$notInStock = array();
				$is_in_stock = 1;
				$use_config_manage_stock = 1;
				$manage_stock = 1;
				$params = array("sourcing","list_price","special_price","price","status","special_from_date","special_to_date","news_from_date","news_to_date","availability_date");
				$parentProductCollection = $this->getStoreProductCollection(array($productId),$params);
				// $params = array("model","warranty","sourcing","list_price","special_price","price","status","special_from_date","special_to_date","news_from_date","news_to_date");
				// $conditions = array("IF(at_status.value_id > 0, at_status.value, at_status_default.value)" => array("=" => 1));
				$simpleProductCollection = $this->getStoreProductCollection($products,$params);
				if(!empty($simpleProductCollection)){
					foreach($simpleProductCollection as $key => $items){
						$simpleObject = new Varien_Object();
						$prices[$key] = array();
						$specialPrices[$key] = array();
						$stockPrices[$key] = array();
						$stockSpecialPrices[$key] = array();
						$disablePrices[$key] = array();
						$disableSpecialPrices[$key] = array();
						$disables[$key] = array();
						$notInStock[$key] = array();
						$countItems = count($items);
						$configProduct = null;
						if(!empty($parentProductCollection) && array_key_exists($key,$parentProductCollection)){
							foreach($parentProductCollection[$key] as $parentProduct){
								$configProduct = $parentProduct;	
							}
						}
						foreach($items as $k => $item){
							if($item->getStatus() == 2){
								$disables[$key][$item->getId()] = $item->getStatus();
							}
							if(!$item->getIsSaleable()){
								$notInStock[$key][$item->getId()] = $item->getIsSaleable();
							}
							if($item->getIsSaleable() && $item->getStatus() == 1){
								if($item->getPrice()){
									$prices[$key][$item->getId()] = $item->getPrice();
								}
								if($item->getSpecialPrice() && ($this->conditionDate($key, $item->getSpecialFromDate(), $item->getSpecialToDate())) && ($item->getPrice() > $item->getSpecialPrice())){
									$specialPrices[$key][$item->getId()] = $item->getSpecialPrice();
								}
							} else if(!$item->getIsSaleable() && $item->getStatus() == 1){
								if($item->getPrice()){
									$stockPrices[$key][$item->getId()] = $item->getPrice();
								}
								if($item->getSpecialPrice() && ($this->conditionDate($key, $item->getSpecialFromDate(), $item->getSpecialToDate())) && ($item->getPrice() > $item->getSpecialPrice())){
									$stockSpecialPrices[$key][$item->getId()] = $item->getSpecialPrice();
								}
							} else if($item->getIsSaleable() && $item->getStatus() == 2){
								if($item->getPrice()){
									$stockPrices[$key][$item->getId()] = $item->getPrice();
								}
								if($item->getSpecialPrice() && ($this->conditionDate($key, $item->getSpecialFromDate(), $item->getSpecialToDate())) && ($item->getPrice() > $item->getSpecialPrice())){
									$stockSpecialPrices[$key][$item->getId()] = $item->getSpecialPrice();
								}
							} else if(!$item->getIsSaleable() && $item->getStatus() == 2){
								if($item->getPrice()){
									$disablePrices[$key][$item->getId()] = $item->getPrice();
								}
								if($item->getSpecialPrice() && ($this->conditionDate($key, $item->getSpecialFromDate(), $item->getSpecialToDate())) && ($item->getPrice() > $item->getSpecialPrice())){
									$disableSpecialPrices[$key][$item->getId()] = $item->getSpecialPrice();
								}
							}
						}
						if(!empty($notInStock[$key])){
							if(count($notInStock[$key])  == $countItems){
								$prices[$key] = $stockPrices[$key];
								$specialPrices[$key] = $stockSpecialPrices[$key];
							}
						}
						if(!empty($disables[$key])){
							if(count($disables[$key])  == $countItems){
								$prices[$key] = $disablePrices[$key];
								$specialPrices[$key] = $disableSpecialPrices[$key];
							}
						}
						if(!empty($notInStock[$key]) && !empty($disables[$key])){
							$margeTotal = array_merge($notInStock[$key],$disables[$key]);
							if(count($margeTotal) == $countItems){
								$prices[$key] = $stockPrices[$key];
								$specialPrices[$key] = $stockSpecialPrices[$key];
							}
						}
						$simpleProduct = null;
						$minSpecialPriceskey = array();
						$removeattribute = array();
						if(!empty($specialPrices[$key])){
							$minSpecialPriceskey = array_keys($specialPrices[$key], min($specialPrices[$key]));
							if(!empty($minSpecialPriceskey)){
								if(array_key_exists($minSpecialPriceskey[0],$simpleProductCollection[$key])){
									$simpleProduct = $simpleProductCollection[$key][$minSpecialPriceskey[0]];		
								}
							}
						}
						// if(!empty($prices[$key]) && empty($minSpecialPriceskey)){
						// 	$minPricekey = array_keys($prices[$key], min($prices[$key]));
						// 	if(!empty($minPricekey)){
						// 		if(array_key_exists($minPricekey[0],$simpleProductCollection[$key])){
						// 			$simpleProduct = $simpleProductCollection[$key][$minPricekey[0]];		
						// 		}
						// 	}
						// }
						if(!empty($prices[$key])){
							$minPricekey = array_keys($prices[$key], min($prices[$key]));
							if(!empty($minPricekey)){
								if(empty($minSpecialPriceskey)){
									if(array_key_exists($minPricekey[0],$simpleProductCollection[$key])){
										$simpleProduct = $simpleProductCollection[$key][$minPricekey[0]];		
									}
								} else {
									$mergePrices = array();
									$mergePrices[$minPricekey[0]] = $prices[$key][$minPricekey[0]];
									$mergePrices[$minSpecialPriceskey[0]] = $specialPrices[$key][$minSpecialPriceskey[0]];
									$mergePricekey = array_keys($mergePrices, min($mergePrices));
									if(!empty($mergePricekey)){
										if(array_key_exists($mergePricekey[0],$simpleProductCollection[$key])){
											$simpleProduct = $simpleProductCollection[$key][$mergePricekey[0]];		
										}
									}
								}
							}
						}
						if($simpleProduct){
							if($simpleProduct->getPrice()) $simpleObject->setPrice($simpleProduct->getPrice()); else $simpleObject->setPrice(null);
							if($simpleProduct->getSpecialPrice()) $simpleObject->setSpecialPrice($simpleProduct->getSpecialPrice()); else $simpleObject->setSpecialPrice(null);
							// if($simpleProduct->getSpecialPrice()) $simpleObject->setSpecialPrice($simpleProduct->getSpecialPrice()); else $removeattribute[] = "special_price";
							if($simpleProduct->getStatus()) $simpleObject->setStatus($simpleProduct->getStatus());
							if($simpleProduct->getListPrice() && $simpleProduct->getExistsStoreValueFlag('list_price')) $simpleObject->setListPrice($simpleProduct->getListPrice()); else $removeattribute[] = "list_price";
							if($simpleProduct->getSourcing() && $simpleProduct->getExistsStoreValueFlag('sourcing')) $simpleObject->setSourcing($simpleProduct->getSourcing()); else $removeattribute[] = "sourcing";
							// if($simpleProduct->getListPrice()) $simpleObject->setListPrice($simpleProduct->getListPrice()); else $simpleObject->setListPrice(null);
							// if($simpleProduct->getSourcing()) $simpleObject->setSourcing($simpleProduct->getSourcing()); else $removeattribute[] = "sourcing";
							// if($simpleProduct->getWarranty()) $simpleObject->setWarranty($simpleProduct->getWarranty()); else $simpleObject->setWarranty(null);
							// if($simpleProduct->getWeight()) $simpleObject->setWeight($simpleProduct->getWeight()); else $simpleObject->setWeight(null);
							// if($simpleProduct->getDimWeight()) $simpleObject->setDimWeight($simpleProduct->getDimWeight()); else $removeattribute[] = "dim_weight";
							if($simpleProduct->getNewsFromDate()) $simpleObject->setNewsFromDate($simpleProduct->getNewsFromDate()); else $removeattribute[] = "news_from_date";
							if($simpleProduct->getNewsToDate()) $simpleObject->setNewsToDate($simpleProduct->getNewsToDate()); else $removeattribute[] = "news_to_date";
							if($simpleProduct->getSpecialFromDate()) $simpleObject->setSpecialFromDate($simpleProduct->getSpecialFromDate()); else $removeattribute[] = "special_from_date";
							if($simpleProduct->getSpecialToDate()) $simpleObject->setSpecialToDate($simpleProduct->getSpecialToDate()); else $removeattribute[] = "special_to_date";
							if($simpleProduct->getAvailabilityDate()) $simpleObject->setAvailabilityDate($simpleProduct->getAvailabilityDate()); else $removeattribute[] = "availability_date";
							if(!empty($removeattribute)){
								$currentStoreId = $defaultProduct->getStoreId();
								$defaultProduct->setStoreId($key);
								$this->removeAttributeValueForStore($defaultProduct,$removeattribute);	
								$defaultProduct->setStoreId($currentStoreId);
							}
						}
						if(!empty($disables[$key])){
							if(count($disables[$key])  == $countItems){
								$simpleObject->setStatus(2);
							}
						}
						if(!is_null($configProduct)){
							foreach($simpleObject->getData() as $attr => $val){
								if($configProduct->getData($attr) == $val){
									$simpleObject->unsetData($attr);
								}
							}
						}
						if(!empty($notInStock[$key])){
							if(count($notInStock[$key])  == $countItems){
								$is_in_stock = 0;
								$use_config_manage_stock = 0;
							}
						}
						if(!empty($simpleObject->getData())){
							Mage::getSingleton('catalog/product_action')->updateAttributes(array($productId), $simpleObject->getData(), $key);
						}
					}
					$inventoryData = array("is_in_stock" => $is_in_stock,"use_config_manage_stock" => $use_config_manage_stock,"manage_stock" => $manage_stock);
					$stockItem = Mage::getModel('cataloginventory/stock_item');
					$stockItem->setData(array());
					$stockItem->loadByProduct($productId)->setProductId($productId);
					$stockDataChanged = false;
					foreach ($inventoryData as $k => $v) {
						$stockItem->setDataUsingMethod($k, $v);
						if ($stockItem->dataHasChangedFor($k)) {
							$stockDataChanged = true;
						}
					}
					if ($stockDataChanged) {
						$stockItem->save();
					}
				}	
				return true;
			}
		}

		$resource = Mage::getModel('core/resource');
		$readAdapter = $resource->getConnection('core_read');
		$sourcingAttribute = Mage::getModel('eav/config')->getAttribute('catalog_product', 'sourcing');
		$entityIdField = "entity_id";
		$sourcingData = array();
		if($attrId = $sourcingAttribute->getAttributeId()){
			if($defaultStoreId != $product->getStore()->getStoreId()){
				$storeIds = array($product->getStore()->getStoreId());
				$select = $readAdapter->select()->from($sourcingAttribute->getBackend()->getTable(), $entityIdField)->where('entity_id = ?',$product->getId())->where('attribute_id = ?',$attrId)->where('store_id IN(?)',$storeIds); 
				foreach ($readAdapter->fetchAll($select) as $row) {
					$sourcingData[] = $row['entity_id'];
				}
			}
		}

		//--- Loop through available Stores...
		foreach($storeCollection as $store){
			$storeId			=	$store->getStoreId();
			$storeCode			=	$store->getCode();
			$storeCurrencyCode 	= 	$store->getBaseCurrency()->getCurrencyCode();
			$storeProduct		=	Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);
			$storePrice		 	=	$storeProduct->getPrice();
			$storeSpecialPrice 	= 	$storeProduct->getSpecialPrice();
			$storeListPrice    	= 	$storeProduct->getListPrice();
			$storeProductName	=	$storeProduct->getName();
			$storeListPriceFlag	=	$storeProduct->getExistsStoreValueFlag('list_price');
			$storeSpecialPriceFlag	=	$storeProduct->getExistsStoreValueFlag('special_price');
			$storeSourcingFlag	=	$storeProduct->getExistsStoreValueFlag('sourcing');
			$storeModelFlag		=	$storeProduct->getExistsStoreValueFlag('model');
			$storeWarrantyFlag	=	$storeProduct->getExistsStoreValueFlag('warranty');
			$storeStatusFlag	=	$storeProduct->getExistsStoreValueFlag('status');
			$storeSpecialFromDateFlag		=	$storeProduct->getExistsStoreValueFlag('special_from_date');
			$storeSpecialToDateFlag	=	$storeProduct->getExistsStoreValueFlag('special_to_date');
			$metaTitle			= 	$helper->getMetaTitle($store, $storeProductName);
			$metaDescription 	= 	$helper->getMetaDescription($store, $storeProductName);
			$object 			= 	new Varien_Object();
			// $object->setMetaTitle($metaTitle);
			// $object->setMetaDescription($metaDescription);

			$formulaPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultListPrice, $storeListPrice, $storeId, $storeCurrencyCode, $specialFormula = false);
			if($formulaPrice) $object->setPrice($formulaPrice);

			if($defaultSpecialPrice != null){
				$formulaSpecialPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultSpecialPrice, $storeSpecialPrice, $storeId, $storeCurrencyCode, $specialFormula = true);
				if($formulaSpecialPrice) $object->setSpecialPrice($formulaSpecialPrice);
			} else {
				$object->setSpecialPrice(null);
			}


			// if($storeSpecialPrice != null && $storeListPriceFlag == true){
			// 	$formulaSpecialPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultSpecialPrice, $storeSpecialPrice, $storeId, $storeCurrencyCode, $specialFormula = true);
			// 	if($formulaSpecialPrice) $object->setSpecialPrice($formulaSpecialPrice);
			// } elseif($storeSpecialPrice == null && $storeListPriceFlag == true){
			// 	$object->setSpecialPrice(null);
			// }

			$storeFormulaDone = true;
			if($storeSourcingFlag){
				$defaultFormulaPrice = "";
				if(!$storeStatusFlag){
					$getSourcing = $storeProduct->getSourcing();
					$storeProduct->setSourcing($defaultProduct->getSourcing());
					$storeProduct->setListPrice($defaultProduct->getListPrice());
					$defaultFormulaPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultListPrice, $defaultListPrice, $storeId, $storeCurrencyCode, $specialFormula = false);
					$storeProduct->setSourcing($getSourcing);
					// Default formula price less then store formula price
					if($formulaPrice > $defaultFormulaPrice){
						$object->setPrice($defaultFormulaPrice);
						$removeattribute = array();
						if($storeModelFlag) $removeattribute[] = "model";
						if($storeSourcingFlag) $removeattribute[] = "sourcing";
						if($storeWarrantyFlag) $removeattribute[] = "warranty";
						if($storeSpecialFromDateFlag) $removeattribute[] = "special_from_date";
						if($storeSpecialToDateFlag) $removeattribute[] = "special_to_date";
						$storeFormulaDone = false;
						$this->removeAttributeValueForStore($storeProduct,$removeattribute);
					} else {
						if(!$storeModelFlag) $object->setModel($defaultProduct->getModel());	
						if(!$storeWarrantyFlag) $object->setWarranty($defaultProduct->getWarranty());
						if(!$storeSpecialFromDateFlag) $object->setSpecialFromDate($defaultProduct->getSpecialFromDate());	
						if(!$storeSpecialToDateFlag) $object->setSpecialToDate($defaultProduct->getSpecialToDate());
					}
				} else {
					if(!$storeModelFlag) $object->setModel($defaultProduct->getModel());	
					if(!$storeWarrantyFlag) $object->setWarranty($defaultProduct->getWarranty());
					if(!$storeSpecialFromDateFlag) $object->setSpecialFromDate($defaultProduct->getSpecialFromDate());	
					if(!$storeSpecialToDateFlag) $object->setSpecialToDate($defaultProduct->getSpecialToDate());
				}
			} else if(!$storeSourcingFlag){
				$storeProduct->setListPrice($defaultProduct->getListPrice());
				$formulaPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultListPrice, $storeListPrice, $storeId, $storeCurrencyCode, $specialFormula = false);
				if($formulaPrice) $object->setPrice($formulaPrice);
				$removeattribute = array();
				if($storeModelFlag) $removeattribute[] = "model";
				if($storeWarrantyFlag) $removeattribute[] = "warranty";
				if($storeSpecialFromDateFlag) $removeattribute[] = "special_from_date";
				if($storeSpecialToDateFlag) $removeattribute[] = "special_to_date";
				$this->removeAttributeValueForStore($storeProduct,$removeattribute);
			}
			if($storeFormulaDone){
				if($storeSpecialPrice != null && $storeSourcingFlag == true){
					$formulaSpecialPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultSpecialPrice, $storeSpecialPrice, $storeId, $storeCurrencyCode, $specialFormula = true);
					if($formulaSpecialPrice) $object->setSpecialPrice($formulaSpecialPrice);
				} elseif($storeSpecialPrice == null && $storeSourcingFlag == true){
					$object->setSpecialPrice(null);
				}
			} else {
				if($defaultSpecialPrice != null){
					$getSourcing = $storeProduct->getSourcing();
					$getSpecialPrice = $storeProduct->getSpecialPrice();
					$storeProduct->setSourcing($defaultProduct->getSourcing());
					$storeProduct->setSpecialPrice($defaultProduct->getSpecialPrice());
					$formulaSpecialPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultSpecialPrice, $defaultSpecialPrice, $storeId, $storeCurrencyCode, $specialFormula = true);
					if($formulaSpecialPrice) $object->setSpecialPrice($formulaSpecialPrice);
					$storeProduct->setSourcing($getSourcing);
					$storeProduct->setSpecialPrice($getSpecialPrice);
				} else {
					$object->setSpecialPrice(null);
				}
			}

			if(!$updateSpecialPrice && $storeSpecialFromDateFlag && $storeSourcingFlag && $product->getStore()->getStoreId() == $storeId){
				$object->unsetData('special_price');
			}

			// if(($product->getData('sourcing') && $storeSourcingFlag) && empty($sourcingData)){
			if(($product->getData('sourcing') && $storeSourcingFlag) && (empty($sourcingData) && $product->getStore()->getStoreId() == $storeId)){
				$object->setSpecialPrice(null);
				$object->setSpecialFromDate(null);
				$object->setSpecialToDate(null);				
			}

			if($storeSpecialPriceFlag == false && $storeSourcingFlag == true && $product->getStore()->getStoreId() == $storeId){
				$object->setSpecialPrice(null);
				$object->setSpecialFromDate(null);
				$object->setSpecialToDate(null);				
			}
			
			if($request->getActionName() != NULL){
				if($storeSourcingFlag){
					$output = shell_exec("php /usr/share/nginx/tejar/shell/airTableHandler.php {$storeCode} product_id {$storeProduct->getId()}  > /dev/null&");
				}
			}

			Mage::getSingleton('catalog/product_action')->updateAttributes(array($productId), $object->getData() , $storeId);
		}

	}

	protected function removeAttributeValueForStore($product, $attributes){
		$adapter            = Mage::getSingleton('core/resource')->getConnection('catalog_write');
        $entityIdField      = $product->getResource()->getEntityIdField();
		$websiteAttributes  = array();
        $storeAttributes    = array();
		$globalAttributes    = array();
		$deleteAttributes = array();
		foreach ($attributes as $key => $attribute) {
			$attribute = $product->getResource()->getAttribute($attribute);
            if (empty($attribute)) {
                continue;
            }
			$deleteAttributes[$attribute->getBackend()->getTable()][] = array(
				'attribute_id'  => $attribute->getAttributeId(),
				'value_id'      => $attribute->getBackend()->getEntityValueId($product)
			);
			if ($attribute->isScopeStore()) {
				$storeAttributes[$attribute->getBackend()->getTable()][] = (int)$attribute->getAttributeId();
			} elseif ($attribute->isScopeWebsite()) {
				$websiteAttributes[$attribute->getBackend()->getTable()][] = (int)$attribute->getAttributeId();
			} else {
				$globalAttributes[$attribute->getBackend()->getTable()][] = (int)$attribute->getAttributeId();
			}
		}
		if(!empty($deleteAttributes)){
			foreach($deleteAttributes as $tableName => $dAttr){ 
				$condition = array(
					$entityIdField . ' = ?' => $product->getId(),
					'entity_type_id = ?'  => $product->getEntityTypeId()
				);
				/**
				 * Delete website scope attributes
				 */
				if (!empty($websiteAttributes) && array_key_exists($tableName,$websiteAttributes)) {
					$storeIds = $product->getWebsiteStoreIds();
					if (!empty($storeIds)) {
						$delCondition = $condition;
						$delCondition['attribute_id IN(?)'] = $websiteAttributes[$tableName];
						$delCondition['store_id IN(?)'] = $storeIds;
						$adapter->delete($tableName, $delCondition);
					}
				}
				/**
				* Delete store scope attributes
				*/
				if (!empty($storeAttributes) && array_key_exists($tableName,$storeAttributes)) {
					$delCondition = $condition;
					$delCondition['attribute_id IN(?)'] = $storeAttributes[$tableName];
					$delCondition['store_id = ?']       = (int)$product->getStoreId();
					$adapter->delete($tableName, $delCondition);
				}

				if (!empty($globalAttributes) && array_key_exists($tableName,$globalAttributes)) {
					$delCondition = $condition;
					$delCondition['attribute_id IN(?)'] = $globalAttributes[$tableName];
					$delCondition['store_id = ?']       = 0;
					$adapter->delete($tableName, $delCondition);
				}
			}
		}
		return $this;
	}


	/*
	*@name        catalogProductAttributeUpdateBefore
	*@description On Mass action/Bulk Update for attributes, this Observer function set prices per store based for currently inserted product..
	*@parameters  Varien_Event_Observer
	*@returns     Object
	*/
	public function catalogProductAttributeUpdateBefore(Varien_Event_Observer $observer){

		//Mage::log('HELLO WORLD!', null, 'confPricing.log');
			$block = $observer->getBlock();
		if($block instanceof Mage_Adminhtml_Block_Catalog_Product_Grid){

			/*$sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
				->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
				->load()
				->toOptionHash();*/

			$block->getMassactionBlock()->addItem('observpricing_updateBulkPrices', array(
				'label'=> Mage::helper('catalog')->__('Update Pricing'),
				'url'  => $block->getUrl('*/*/updateBulkPrices', array('_current'=>true)),
				/*'additional' => array(
					'visibility' => array(
						'name' => 'attribute_set',
						'type' => 'select',
						'class' => 'required-entry',
						'label' => Mage::helper('catalog')->__('Attribute Set'),
						'values' => $sets
					)
				)*/
			));
		}
	}

	/**
     * Event Product Save After
     *
     * @var Tejar_Observpricing_Model_Observer catalogProductSaveAfter
	 *
	 * @return Product
     */
	public function catalogProductMassupdateAttributeCustom(Varien_Event_Observer $observer){
		
		$event 		    		= 	$observer->getEvent();
		$productIds				=	$event->getProducts();
		$storeCollection		=	Mage::app()->getStores();
		$helper					=	Mage::helper('tejarobservpricing');
		$defaultStoreId		 	= 	Mage::app()->getWebsite()->getDefaultGroup()->getDefaultStoreId();

		foreach($productIds as $productId){

			$productId			 =	$productId;
			$defaultProduct 	 = 	Mage::getModel('catalog/product')->setStoreId($defaultStoreId)->load($productId);
			$defaultPrice		 =	$defaultProduct->getPrice();
			$defaultSpecialPrice = 	$defaultProduct->getSpecialPrice();
			$defaultListPrice  	 = 	$defaultProduct->getListPrice();
			$request			 = 	Mage::app()->getRequest();

			if($defaultListPrice){
				Mage::getSingleton('catalog/product_action')->updateAttributes(array($productId), array("price"=> $defaultListPrice), $defaultStoreId);
			}

			if($defaultProduct->getTypeId() == "configurable"){
				$products = $defaultProduct->getTypeInstance(true)->getUsedProductIds($defaultProduct);
				if(!empty($products)){
					$prices = array();
					$specialPrices = array();
					$stockPrices = array();
					$stockSpecialPrices = array();
					$disablePrices = array();
					$disableSpecialPrices = array();
					$disables = array();
					$notInStock = array();
					$is_in_stock = 1;
					$use_config_manage_stock = 1;
					$manage_stock = 1;
					$params = array("sourcing","list_price","special_price","price","status","special_from_date","special_to_date","news_from_date","news_to_date","availability_date");
					$parentProductCollection = $this->getStoreProductCollection(array($productId),$params);
					$simpleProductCollection = $this->getStoreProductCollection($products,$params);
					if(!empty($simpleProductCollection)){
						foreach($simpleProductCollection as $key => $items){
							$simpleObject = new Varien_Object();
							$prices[$key] = array();
							$specialPrices[$key] = array();
							$stockPrices[$key] = array();
							$stockSpecialPrices[$key] = array();
							$disablePrices[$key] = array();
							$disableSpecialPrices[$key] = array();
							$disables[$key] = array();
							$notInStock[$key] = array();
							$countItems = count($items);
							$configProduct = null;
							if(!empty($parentProductCollection) && array_key_exists($key,$parentProductCollection)){
								foreach($parentProductCollection[$key] as $parentProduct){
									$configProduct = $parentProduct;	
								}
							}
							foreach($items as $k => $item){
								if($item->getStatus() == 2){
									$disables[$key][$item->getId()] = $item->getStatus();
								}
								if(!$item->getIsSaleable()){
									$notInStock[$key][$item->getId()] = $item->getIsSaleable();
								}
								if($item->getIsSaleable() && $item->getStatus() == 1){
									if($item->getPrice()){
										$prices[$key][$item->getId()] = $item->getPrice();
									}
									if($item->getSpecialPrice() && ($this->conditionDate($key, $item->getSpecialFromDate(), $item->getSpecialToDate())) && ($item->getPrice() > $item->getSpecialPrice())){
										$specialPrices[$key][$item->getId()] = $item->getSpecialPrice();
									}
								} else if(!$item->getIsSaleable() && $item->getStatus() == 1){
									if($item->getPrice()){
										$stockPrices[$key][$item->getId()] = $item->getPrice();
									}
									if($item->getSpecialPrice() && ($this->conditionDate($key, $item->getSpecialFromDate(), $item->getSpecialToDate())) && ($item->getPrice() > $item->getSpecialPrice())){
										$stockSpecialPrices[$key][$item->getId()] = $item->getSpecialPrice();
									}
								} else if($item->getIsSaleable() && $item->getStatus() == 2){
									if($item->getPrice()){
										$stockPrices[$key][$item->getId()] = $item->getPrice();
									}
									if($item->getSpecialPrice() && ($this->conditionDate($key, $item->getSpecialFromDate(), $item->getSpecialToDate())) && ($item->getPrice() > $item->getSpecialPrice())){
										$stockSpecialPrices[$key][$item->getId()] = $item->getSpecialPrice();
									}
								} else if(!$item->getIsSaleable() && $item->getStatus() == 2){
									if($item->getPrice()){
										$disablePrices[$key][$item->getId()] = $item->getPrice();
									}
									if($item->getSpecialPrice() && ($this->conditionDate($key, $item->getSpecialFromDate(), $item->getSpecialToDate())) && ($item->getPrice() > $item->getSpecialPrice())){
										$disableSpecialPrices[$key][$item->getId()] = $item->getSpecialPrice();
									}
								}
							}
							if(!empty($notInStock[$key])){
								if(count($notInStock[$key])  == $countItems){
									$prices[$key] = $stockPrices[$key];
									$specialPrices[$key] = $stockSpecialPrices[$key];
								}
							}
							if(!empty($disables[$key])){
								if(count($disables[$key])  == $countItems){
									$prices[$key] = $disablePrices[$key];
									$specialPrices[$key] = $disableSpecialPrices[$key];
								}
							}
							if(!empty($notInStock[$key]) && !empty($disables[$key])){
								$margeTotal = array_merge($notInStock[$key],$disables[$key]);
								if(count($margeTotal) == $countItems){
									$prices[$key] = $stockPrices[$key];
									$specialPrices[$key] = $stockSpecialPrices[$key];
								}
							}
							$simpleProduct = null;
							$minSpecialPriceskey = array();
							$removeattribute = array();
							if(!empty($specialPrices[$key])){
								$minSpecialPriceskey = array_keys($specialPrices[$key], min($specialPrices[$key]));
								if(!empty($minSpecialPriceskey)){
									if(array_key_exists($minSpecialPriceskey[0],$simpleProductCollection[$key])){
										$simpleProduct = $simpleProductCollection[$key][$minSpecialPriceskey[0]];		
									}
								}
							}
							
							if(!empty($prices[$key])){
								$minPricekey = array_keys($prices[$key], min($prices[$key]));
								if(!empty($minPricekey)){
									if(empty($minSpecialPriceskey)){
										if(array_key_exists($minPricekey[0],$simpleProductCollection[$key])){
											$simpleProduct = $simpleProductCollection[$key][$minPricekey[0]];		
										}
									} else {
										$mergePrices = array();
										$mergePrices[$minPricekey[0]] = $prices[$key][$minPricekey[0]];
										$mergePrices[$minSpecialPriceskey[0]] = $specialPrices[$key][$minSpecialPriceskey[0]];
										$mergePricekey = array_keys($mergePrices, min($mergePrices));
										if(!empty($mergePricekey)){
											if(array_key_exists($mergePricekey[0],$simpleProductCollection[$key])){
												$simpleProduct = $simpleProductCollection[$key][$mergePricekey[0]];		
											}
										}
									}
								}
							}
							
							if($simpleProduct){
								if($simpleProduct->getPrice()) $simpleObject->setPrice($simpleProduct->getPrice()); else $simpleObject->setPrice(null);
								if($simpleProduct->getSpecialPrice()) $simpleObject->setSpecialPrice($simpleProduct->getSpecialPrice()); else $simpleObject->setSpecialPrice(null);
								if($simpleProduct->getStatus()) $simpleObject->setStatus($simpleProduct->getStatus());
								if($simpleProduct->getListPrice() && $simpleProduct->getExistsStoreValueFlag('list_price')) $simpleObject->setListPrice($simpleProduct->getListPrice()); else $removeattribute[] = "list_price";
								if($simpleProduct->getSourcing() && $simpleProduct->getExistsStoreValueFlag('sourcing')) $simpleObject->setSourcing($simpleProduct->getSourcing()); else $removeattribute[] = "sourcing";
								if($simpleProduct->getNewsFromDate()) $simpleObject->setNewsFromDate($simpleProduct->getNewsFromDate()); else $removeattribute[] = "news_from_date";
								if($simpleProduct->getNewsToDate()) $simpleObject->setNewsToDate($simpleProduct->getNewsToDate()); else $removeattribute[] = "news_to_date";
								if($simpleProduct->getSpecialFromDate()) $simpleObject->setSpecialFromDate($simpleProduct->getSpecialFromDate()); else $removeattribute[] = "special_from_date";
								if($simpleProduct->getSpecialToDate()) $simpleObject->setSpecialToDate($simpleProduct->getSpecialToDate()); else $removeattribute[] = "special_to_date";
								if($simpleProduct->getAvailabilityDate()) $simpleObject->setAvailabilityDate($simpleProduct->getAvailabilityDate()); else $removeattribute[] = "availability_date";
								if(!empty($removeattribute)){
									$currentStoreId = $defaultProduct->getStoreId();
									$defaultProduct->setStoreId($key);
									$this->removeAttributeValueForStore($defaultProduct,$removeattribute);	
									$defaultProduct->setStoreId($currentStoreId);
								}
							}
							if(!empty($disables[$key])){
								if(count($disables[$key])  == $countItems){
									$simpleObject->setStatus(2);
								}
							}
							if(!is_null($configProduct)){
								foreach($simpleObject->getData() as $attr => $val){
									if($configProduct->getData($attr) == $val){
										$simpleObject->unsetData($attr);
									}
								}
							}
							if(!empty($notInStock[$key])){
								if(count($notInStock[$key])  == $countItems){
									$is_in_stock = 0;
									$use_config_manage_stock = 0;
								}
							}
							if(!empty($simpleObject->getData())){
								Mage::getSingleton('catalog/product_action')->updateAttributes(array($productId), $simpleObject->getData(), $key);
							}
						}
						$inventoryData = array("is_in_stock" => $is_in_stock,"use_config_manage_stock" => $use_config_manage_stock,"manage_stock" => $manage_stock);
						$stockItem = Mage::getModel('cataloginventory/stock_item');
						$stockItem->setData(array());
						$stockItem->loadByProduct($productId)->setProductId($productId);
						$stockDataChanged = false;
						foreach ($inventoryData as $k => $v) {
							$stockItem->setDataUsingMethod($k, $v);
							if ($stockItem->dataHasChangedFor($k)) {
								$stockDataChanged = true;
							}
						}
						if ($stockDataChanged) {
							$stockItem->save();
						}
					}	
					return true;
				}
			}

			//--- Loop through available Stores...
			foreach($storeCollection as $store){
				$storeId			=	$store->getStoreId();
				$storeCode			=	$store->getCode();
				$storeCurrencyCode 	= 	$store->getBaseCurrency()->getCurrencyCode();
				$storeProduct		=	Mage::getModel('catalog/product')->setStoreId($storeId)->load($productId);
				$storePrice		 	=	$storeProduct->getPrice();
				$storeSpecialPrice 	= 	$storeProduct->getSpecialPrice();
				$storeListPrice    	= 	$storeProduct->getListPrice();
				$storeProductName	=	$storeProduct->getName();
				$storeListPriceFlag	=	$storeProduct->getExistsStoreValueFlag('list_price');
				$storeSpecialPriceFlag	=	$storeProduct->getExistsStoreValueFlag('special_price');
				$storeSourcingFlag	=	$storeProduct->getExistsStoreValueFlag('sourcing');
				$storeModelFlag		=	$storeProduct->getExistsStoreValueFlag('model');
				$storeWarrantyFlag	=	$storeProduct->getExistsStoreValueFlag('warranty');
				$storeStatusFlag	=	$storeProduct->getExistsStoreValueFlag('status');
				$storeSpecialFromDateFlag		=	$storeProduct->getExistsStoreValueFlag('special_from_date');
				$storeSpecialToDateFlag	=	$storeProduct->getExistsStoreValueFlag('special_to_date');
				$metaTitle			= 	$helper->getMetaTitle($store, $storeProductName);
				$metaDescription 	= 	$helper->getMetaDescription($store, $storeProductName);
				$object 			= 	new Varien_Object();

				$formulaPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultListPrice, $storeListPrice, $storeId, $storeCurrencyCode, $specialFormula = false);
				if($formulaPrice) $object->setPrice($formulaPrice);

				if($defaultSpecialPrice != null){
					$formulaSpecialPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultSpecialPrice, $storeSpecialPrice, $storeId, $storeCurrencyCode, $specialFormula = true);
					if($formulaSpecialPrice) $object->setSpecialPrice($formulaSpecialPrice);
				} else {
					$object->setSpecialPrice(null);
				}

				$storeFormulaDone = true;
				if($storeSourcingFlag){
					$defaultFormulaPrice = "";
					if(!$storeStatusFlag){
						$getSourcing = $storeProduct->getSourcing();
						$storeProduct->setSourcing($defaultProduct->getSourcing());
						$storeProduct->setListPrice($defaultProduct->getListPrice());
						$defaultFormulaPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultListPrice, $defaultListPrice, $storeId, $storeCurrencyCode, $specialFormula = false);
						$storeProduct->setSourcing($getSourcing);
						// Default formula price less then store formula price
						if($formulaPrice > $defaultFormulaPrice){
							$object->setPrice($defaultFormulaPrice);
							$removeattribute = array();
							if($storeModelFlag) $removeattribute[] = "model";
							if($storeSourcingFlag) $removeattribute[] = "sourcing";
							if($storeWarrantyFlag) $removeattribute[] = "warranty";
							if($storeSpecialFromDateFlag) $removeattribute[] = "special_from_date";
							if($storeSpecialToDateFlag) $removeattribute[] = "special_to_date";
							$storeFormulaDone = false;
							$this->removeAttributeValueForStore($storeProduct,$removeattribute);
						} else {
							if(!$storeModelFlag) $object->setModel($defaultProduct->getModel());	
							if(!$storeWarrantyFlag) $object->setWarranty($defaultProduct->getWarranty());
							if(!$storeSpecialFromDateFlag) $object->setSpecialFromDate($defaultProduct->getSpecialFromDate());	
							if(!$storeSpecialToDateFlag) $object->setSpecialToDate($defaultProduct->getSpecialToDate());
						}
					} else {
						if(!$storeModelFlag) $object->setModel($defaultProduct->getModel());	
						if(!$storeWarrantyFlag) $object->setWarranty($defaultProduct->getWarranty());
						if(!$storeSpecialFromDateFlag) $object->setSpecialFromDate($defaultProduct->getSpecialFromDate());	
						if(!$storeSpecialToDateFlag) $object->setSpecialToDate($defaultProduct->getSpecialToDate());
					}
				} else if(!$storeSourcingFlag){
					$storeProduct->setListPrice($defaultProduct->getListPrice());
					$formulaPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultListPrice, $storeListPrice, $storeId, $storeCurrencyCode, $specialFormula = false);
					if($formulaPrice) $object->setPrice($formulaPrice);
					$removeattribute = array();
					if($storeModelFlag) $removeattribute[] = "model";
					if($storeWarrantyFlag) $removeattribute[] = "warranty";
					if($storeSpecialFromDateFlag) $removeattribute[] = "special_from_date";
					if($storeSpecialToDateFlag) $removeattribute[] = "special_to_date";
					$this->removeAttributeValueForStore($storeProduct,$removeattribute);
				}
				if($storeFormulaDone){
					if($storeSpecialPrice != null && $storeSourcingFlag == true){
						$formulaSpecialPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultSpecialPrice, $storeSpecialPrice, $storeId, $storeCurrencyCode, $specialFormula = true);
						if($formulaSpecialPrice) $object->setSpecialPrice($formulaSpecialPrice);
					} elseif($storeSpecialPrice == null && $storeSourcingFlag == true){
						$object->setSpecialPrice(null);
					}
				} else {
					if($defaultSpecialPrice != null){
						$getSourcing = $storeProduct->getSourcing();
						$getSpecialPrice = $storeProduct->getSpecialPrice();
						$storeProduct->setSourcing($defaultProduct->getSourcing());
						$storeProduct->setSpecialPrice($defaultProduct->getSpecialPrice());
						$formulaSpecialPrice = $helper->getFinalCost($storeProduct, $defaultProduct, $defaultSpecialPrice, $defaultSpecialPrice, $storeId, $storeCurrencyCode, $specialFormula = true);
						if($formulaSpecialPrice) $object->setSpecialPrice($formulaSpecialPrice);
						$storeProduct->setSourcing($getSourcing);
						$storeProduct->setSpecialPrice($getSpecialPrice);
					} else {
						$object->setSpecialPrice(null);
					}
				}

				if($storeSpecialFromDateFlag && $storeSourcingFlag){
					$object->unsetData('special_price');
				}

				Mage::getSingleton('catalog/product_action')->updateAttributes(array($productId), $object->getData() , $storeId);
			}
		}
	}
	protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/products/' . $action);
    }	
}
