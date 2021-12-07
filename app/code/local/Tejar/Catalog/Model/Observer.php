<?php


/**
 * Mestrona magento module
 *
 * LICENSE
 *
 * This source file is subject of Mestrona.
 * You may be not allowed to change the sources
 * without authorization of Mestrona GbR.
 *
 * @copyright  Copyright (c) 2012 Mestrona GbR (http://www.mestrona.net)
 * @author Mestrona GbR <support@mestrona.net>
 * @category Mestrona
 * @package Mestrona_ForwardToConfigurable
 */
class Tejar_Catalog_Model_Observer extends Mage_Core_Model_Abstract
{

    const XML_PATH_CATEGORY_WEBHOOK_URL		          = 'catalog/webhook/category_url';

	protected $currentProduct = null;

		/**
     * Prepares product view page - inits layout and all needed stuff
     *
     * $params can have all values as $params in Mage_Catalog_Helper_Product - initProduct().
     * Plus following keys:
     *   - 'buy_request' - Varien_Object holding buyRequest to configure product
     *   - 'specify_options' - boolean, whether to show 'Specify options' message
     *   - 'configure_mode' - boolean, whether we're in Configure-mode to edit product configuration
     *
     * @param int $productId
     * @param Mage_Core_Controller_Front_Action $controller
     * @param null|Varien_Object $params
     *
     * @return Mage_Catalog_Helper_Product_View
     */
    protected function prepareAndRender($productId, $controller, $params = null)
    {
        // Prepare data
        $productHelper = Mage::helper('catalog/product');
		$productViewHelper = Mage::helper('catalog/product_view');
		$currentProduct = $this->currentProduct;
        if (!$params) {
            $params = new Varien_Object();
        }
        // Standard algorithm to prepare and rendern product view page
        $product = $productHelper->initProduct($productId, $controller, $params);
		$exclude = array("entity_id","entity_type_id","attribute_set_id","type_id","has_options","required_options","created_at","updated_at","options_container","status","visibility","is_salable");
		$include = array("name","image","small_image","thumbnail","url_key","url_path","image_label","small_image_label","thumbnail_label","price","media_gallery","model","short_description","description");
        if(!empty($currentProduct)){
			foreach($currentProduct->getData() as $key => $value){
				if(!is_array($value)){
					if(!in_array($key,$exclude)){
						$product->setData($key,$value);
					}
				} else if(is_array($value) && $key == "media_gallery"){
					if(!in_array($key,$exclude)){
						$product->setData($key,$value);
					}
				}
			}
			$product->setUrl($currentProduct->getProductUrl());
		}
        if (!$product) {
            throw new Mage_Core_Exception($this->__('Product is not loaded'), $this->ERR_NO_PRODUCT_LOADED);
        }
        $buyRequest = $params->getBuyRequest();
        if ($buyRequest) {
            $productHelper->prepareProductOptions($product, $buyRequest);
        }
        if ($params->hasConfigureMode()) {
            $product->setConfigureMode($params->getConfigureMode());
        }
        Mage::register('associated_product', $currentProduct);
        Mage::dispatchEvent('catalog_controller_product_view', array('product' => $product));
        if ($params->getSpecifyOptions()) {
            $notice = $product->getTypeInstance(true)->getSpecifyOptionMessage();
            Mage::getSingleton('catalog/session')->addNotice($notice);
        }
        Mage::getSingleton('catalog/session')->setLastViewedProductId($product->getId());
        $productViewHelper->initProductLayout($product, $controller);
        $controller->initLayoutMessages(array('catalog/session', 'tag/session', 'checkout/session'))
            ->renderLayout();
        return $this;
    }

    /**
     * Generates config array to reflect the simple product's ($currentProduct)
     * configuration in its parent configurable product
     *
     * @param Mage_Catalog_Model_Product $parentProduct
     * @param Mage_Catalog_Model_Product $currentProduct
     * @return array array( configoptionid -> value )
     */
    protected function generateConfigData(Mage_Catalog_Model_Product $parentProduct, Mage_Catalog_Model_Product $currentProduct)
    {
        /* @var $typeInstance Mage_Catalog_Model_Product_Type_Configurable */
        $typeInstance = $parentProduct->getTypeInstance();
        if (!$typeInstance instanceof Mage_Catalog_Model_Product_Type_Configurable) {
            return; // not a configurable product
        }
        $configData = array();
        $attributes = $typeInstance->getUsedProductAttributes($parentProduct);
        foreach ($attributes as $code => $data) {
            $configData[$code] = $currentProduct->getData($data->getAttributeCode());
        }
        return $configData;
    }
    /**
     * Checks if the current product has a super-product assigned
     * Finds the super product
     * @param $observer Varien_Event_Observer $observer
     * @throws Exception
     */
    public function __forwardToConfigurable($observer)
    {


		//return false;
		//Mage::log('HELLO FROM CONFIG ASSOS OBSERVER', null ,'zeeshan.log');
        $controller = $observer->getControllerAction();
        $productId = (int)$controller->getRequest()->getParam('id');
        $parentIds = Mage::getModel('catalog/product_type_configurable')
            ->getParentIdsByChild($productId);

			$visibilitySwitch = Mage::getStoreConfig('catalog/frontend/visibility');

		$product = Mage::getModel('catalog/product')->load($productId);

		// Check for Parent Ids and Product Visibility
        if (empty($parentIds) || $product->getVisibility()==1 || $product->getStatus()==2) {
		// does not have a parent -> nothing to do
            return;
        }

        while (count($parentIds) > 0) {
            $parentId = array_shift($parentIds);
            /* @var $parentProduct Mage_Catalog_Model_Product */
            $parentProduct = Mage::getModel('catalog/product');
            $parentProduct->load($parentId);
            if (!$parentProduct->getId()) {
                throw new Exception(sprintf('Can not load parent product with ID %d', $parentId));
            }
            if ($parentProduct->isVisibleInCatalog()) {
                break;
            }
            // try to find other products if one parent product is not visible -> loop
        }

        if (!$parentProduct->isVisibleInCatalog()) {
            Mage::log(sprintf('Not enabled parent for product id %d found.', $productId), Zend_Log::WARN);
            return;
        }

        if (!empty($parentIds)) {
            Mage::log(sprintf('Product with id %d has more than one enabled parent. Choosing first.', $productId), Zend_Log::NOTICE);
        }

            /* @var $currentProduct Mage_Catalog_Model_Product */
        $currentProduct = Mage::getModel('catalog/product');
        $currentProduct->load($productId);
		if(($currentProduct->getStatus() == 2) || ((!$visibilitySwitch) && ($currentProduct->getVisibility()==1))){
			return;
		}
        $params = new Varien_Object();
        $params->setCategoryId(false);
        $params->setConfigureMode(true);
        $buyRequest = new Varien_Object();
        $buyRequest->setSuperAttribute($this->generateConfigData($parentProduct, $currentProduct)); // example format: array(525 => "99"));
        $params->setBuyRequest($buyRequest);
        // override visibility setting of configurable product
        // in case only simple products should be visible in the catalog
        // TODO: make this behaviour configurable
        $params->setOverrideVisibility(true);
        /* @var $productViewHelper Mage_Catalog_Helper_Product_View */
        $productViewHelper = Mage::helper('catalog/product_view');
        $controller->getRequest()->setDispatched(true);
        // avoid double dispatching
        // @see Mage_Core_Controller_Varien_Action::dispatch()

        $controller->setFlag('', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, true);

		//--- Check if parent product is set to 'Not visible Individually', load child product then..
		if($parentProduct->getVisibility()==="1"){
			//$productViewHelper->prepareAndRender($productId, $controller, $params);
		}else{
			//$productViewHelper->prepareAndRender($parentId, $controller, $params);
		}

		$this->prepareAndRender($parentId, $controller, $params);
    }

	    /**
     * Checks if the current product has a super-product assigned
     * Finds the super product
     * @param $observer Varien_Event_Observer $observer
     * @throws Exception
     */
    public function forwardToConfigurable($observer)
    {
        $controller	= $observer->getControllerAction();
		$event		= $observer->getEvent();
        $productId	= (int)$controller->getRequest()->getParam('id');
		$visibilitySwitch = Mage::getStoreConfig('catalog/frontend/visibility');
        /* @var $currentProduct Mage_Catalog_Model_Product */
        $currentProduct = Mage::getModel('catalog/product');
        $this->currentProduct = $currentProduct->load($productId);
		$visibleInCatalogIds = Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds();
		if(in_array($currentProduct->getVisibility(),$visibleInCatalogIds)){
			return;
		}
		// Load Parent Product
        $parentIds = Mage::getSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
		if (empty($parentIds)) {
            return;
        }
        while (count($parentIds) > 0) {
            $parentId = array_shift($parentIds);
            /* @var $parentProduct Mage_Catalog_Model_Product */
            $parentProduct = Mage::getModel('catalog/product');
            $parentProduct->load($parentId);
            if (!$parentProduct->getId()) {
                throw new Exception(sprintf('Can not load parent product with ID %d', $parentId));
            }
            if ($parentProduct->isVisibleInCatalog()) {
                break;
            }
        }
        if(!$parentProduct->isSalable()){
			return;
		}
        if (!$parentProduct->isVisibleInCatalog()) {
            Mage::log(sprintf('Not enabled parent for product id %d found.', $productId), Zend_Log::WARN);
            return;
        }
        if (!empty($parentIds)) {
            Mage::log(sprintf('Product with id %d has more than one enabled parent. Choosing first.', $productId), Zend_Log::NOTICE);
        }
		/* @var $currentProduct Mage_Catalog_Model_Product */
        // $currentProduct = Mage::getModel('catalog/product');
        // $currentProduct = $currentProduct->load($productId);
		$this->currentProduct = $currentProduct;
		if(($currentProduct->getStatus() == 2) || ((!$visibilitySwitch) && ($currentProduct->getVisibility()==1))){
			return;
		}
        $params = new Varien_Object();
        $params->setCategoryId(false);
        $params->setConfigureMode(true);
        $buyRequest = new Varien_Object();
        $buyRequest->setSuperAttribute($this->generateConfigData($parentProduct, $currentProduct)); // example format: array(525 => "99"));
        $params->setBuyRequest($buyRequest);
        // override visibility setting of configurable product
        // in case only simple products should be visible in the catalog
        // TODO: make this behaviour configurable
        $params->setOverrideVisibility(true);
        /* @var $productViewHelper Mage_Catalog_Helper_Product_View */
        $productViewHelper = Mage::helper('catalog/product_view');
        $controller->getRequest()->setDispatched(true);
        // avoid double dispatching
        // @see Mage_Core_Controller_Varien_Action::dispatch()
        $controller->setFlag('', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH, true);
		$this->prepareAndRender($parentId, $controller, $params);
    }

    /**
       * Refresh mostviewed report statistics for last day
       *
       * @param Mage_Cron_Model_Schedule $schedule
       * @return Tejar_Catalog_Model_Observer
       */
      public function aggregateCatalogReportMostViewedData($schedule)
      {
          Mage::app()->getLocale()->emulate(0);
          $currentDate = Mage::app()->getLocale()->date();
          $date = $currentDate->subHour(25);
          Mage::getResourceModel('reports/report_product_viewed')->aggregate($date);
          Mage::app()->getLocale()->revert();
          return $this;
      }

      /**
         * After Category Save Trigger
         *
         *
         * @return Tejar_Catalog_Model_Observer
         */
        public function CategorySaveAfter($observer)
        {

    		$category = $observer->getCategory();
    		$defaultStoreId = Mage::app()->getStore()->getId();
    		$currentStoreId = $category->getStore()->getId();
    		$meta_enable = Mage::getStoreConfig('design/product_meta/category_meta_enable', $category->getStore()->getId());
    		if($meta_enable){

    			/*** Get the resource model */
    			$resource = Mage::getSingleton('core/resource');
    			/*** Retrieve the read connection */
    			$readAdapter = $resource->getConnection('core_read');
    			$tableName = $resource->getTableName('catalog_category_entity_varchar');
          if($category->getData("collection_type") == 1 && $category->getLevel() > 2){
					 if($category->getData("level") == 3) {
						$select = $readAdapter->select()->from(array('e' => $tableName), array('store_id', 'value'))->where('e.entity_id IN(?)', array($category->getId(),$category->getData("parent_id")))->where('e.attribute_id =?', 41);
					} else if($category->getData("level") == 4) {
            $orderBy = "";
						$orderBy .= "CASE ";
						$orderBy .= "WHEN main_table.level = 2 THEN 1 ";
						$orderBy .= "WHEN main_table.level = 4 THEN 2 ";
						$orderBy .= "WHEN main_table.level = 3 THEN 3 ";
						$orderBy .= "ELSE 4 END, main_table.level";
						$select = $readAdapter->select()->from(array('e' => $tableName), array('store_id', 'value'))
						->joinLeft(array('main_table' => 'catalog_category_entity'),"( `main_table`.`entity_id` = `e`.`entity_id`)")
						->where('e.entity_id IN(?)', array($category->getParentIds()[2],$category->getId(),$category->getParentIds()[3]))
						->where('e.attribute_id =?', 41)
						->order(new Zend_Db_Expr($orderBy));
					} else {
						$select = $readAdapter->select()->from(array('e' => $tableName), array('store_id', 'value'))->where('e.entity_id =?', $category->getId())->where('e.attribute_id =?', 41);
					}
				 
    			$collectionName = $readAdapter->fetchAll($select);

          $_collectionName = array();
  				$_collectionName[] = "";

    			foreach($collectionName as $item){
    				$_collectionName[$item['store_id']]	.= " " . $item['value'];
    			}


    			$defaultName = $_collectionName[$defaultStoreId];
    			// --- Loop through available Stores...
    			foreach(Mage::app()->getStores() as $store){

    				$storeId = $store->getId();
    				if(isset($_collectionName[$storeId]) && !empty($_collectionName[$storeId])){
    					$categoryName = $_collectionName[$storeId];
    				} else {
    					$categoryName = $defaultName;
    				}

        //   $categoryName = preg_replace('/\s+/', ' ', $categoryName);
		// 			$categoryName = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $categoryName);
		// 			$categoryName = trim($categoryName);
                    $categoryName = $categoryName;

    				$array = array();
    				$array['meta_title'] = $categoryName;
    				//$array['meta_description'] = $this->getMetaDescription($store, $categoryName);
    				Mage::getSingleton('tejar_catalog/category_action')->updateAttributes(array($category->getId()),$array,$store->getId());
                }
            }
    		}
            $filePath = MAGENTO_ROOT . '/shell/airTableHandler.php';
            $airTableUrlSupplier = Mage::getStoreConfig('catalog/api/airtable_supplier', $storeId);	
            $airTableUrlCategory = Mage::getStoreConfig('catalog/api/airtable_category', $storeId);			
            if($airTableUrlSupplier && $category->getData("collection_type") == 1 && $category->getLevel() == 2){
                $output = shell_exec("php {$filePath} category_id {$category->getId()} table_name Supplier > /dev/null&");
            }
            if($airTableUrlCategory && $category->getData("collection_type") == 1 && $category->getLevel() == 2){ 
                $output = shell_exec("php {$filePath} category_id {$category->getId()} table_name Category > /dev/null&");
            }
    		return $this;
        }
        
        	/**
         * After Category Save Trigger
         *
         *
         * @return Tejar_Catalog_Model_Observer
         */
        public function catalogCategoryPrepareSave($observer)
        {   
            $event = $observer->getEvent();
            $category = $event->getCategory();
            $storeId = $category->getStore()->getStoreId();
        //     $webhookURL = Mage::getStoreConfig(self::XML_PATH_CATEGORY_WEBHOOK_URL, $storeId);
        //     if($webhookURL){
		// 	$adminSession = Mage::getSingleton('admin/session');
		// 	$user = $adminSession->getUser();
        //     $request = $event->getRequest();
		// 	$data = array();
		// 	$data['action'] = "new";
		// 	if($request->getParam('id')){
		// 		$data['action'] = "edit";
		// 	}
		// 	$data['user'] = array(
		// 		'userid'	=> $user->getUserId(),
		// 		'username' => $user->getUsername(),
		// 		'email' => $user->getEmail()
		// 	);
        //     $data['category'] = array();
		// 	$mCategory = Mage::getModel('catalog/category')->setStoreId($category->getStore()->getStoreId())->load($category->getId());
		// 	$mDataCollection = array();
		// 	foreach($mCategory->getData() as $mKey => $mData){
		// 		// if($mData){
		// 			$mDataCollection[$mKey] = $mData;
		// 		// }
		// 	}
		// 	$dataCollection = array();
		// 	$images = array("image","thumbnail","custom_thumbnail");
		// 	foreach($category->getData() as $key => $udata){
		// 		if(is_array($udata) && array_key_exists($key,$mDataCollection) && in_array($key,$images)){
		// 			$dataCollection[$key] = $udata['value'];
		// 		} else if(array_key_exists($key,$mDataCollection)){
		// 			$dataCollection[$key] = $udata;
		// 		}
		// 	}
		// 	$result = array_diff_assoc($dataCollection,$mDataCollection);
		// 	$origDataDiff = array();
		// 	foreach($result as $k => $r){
		// 		$origDataDiff[$k] = $mDataCollection[$k];
		// 	}
		// 	$data['category']['before_update'] = $origDataDiff;
		// 	$data['category']['updated'] = $category->getData();
        //     if(count($category->getData('posted_products'))){
		// 		$data['category']['updated']['product_count'] = count($category->getData('posted_products'));
		// 	}			
		// 	if(count($category->getData('posted_products')) != $mCategory->getProductCount()){
		// 		$data['category']['before_update']['product_count'] = $mCategory->getProductCount();
		// 	}
        //     $this->setCategoryWebhook($data,$category->getStore()->getStoreId());
        // }
		}		
		/**
         * After Category Save Trigger
         *
         *
         * @return Tejar_Catalog_Model_Observer
         */
        public function catalogCategoryDeleteAfterDone($observer)
        {   
            $event = $observer->getEvent();
            $category = $event->getCategory();
            $storeId = $category->getStore()->getStoreId();
            $webhookURL = Mage::getStoreConfig(self::XML_PATH_CATEGORY_WEBHOOK_URL, $storeId);
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
			$data['category'] = $category->getData();
            $this->setCategoryWebhook($data,$category->getStore()->getStoreId());
        }
		}
				 /**
		 * post product 
		 *
		 * @param string product
		 * @return array
		 */
		public function setCategoryWebhook($data,$storeId = 0)
		{
			if(!empty($data)){
                $webhookURL = Mage::getStoreConfig(self::XML_PATH_CATEGORY_WEBHOOK_URL, $storeId);
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

    		$meta_description = Mage::getStoreConfig('design/product_meta/category_meta_description', $storeId);
    		if($meta_description){
    			$metaDescription = sprintf($meta_description , $productTitle , $countryName);
    			return $metaDescription;
    		}

    		return;

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

    		$meta_title = Mage::getStoreConfig('design/product_meta/category_meta_title', $storeId);


    		if($meta_title){
    			$metaTitle = sprintf($meta_title , $productTitle , $countryName);
    			return $metaTitle;
    		}

    		return;
    	}
}
