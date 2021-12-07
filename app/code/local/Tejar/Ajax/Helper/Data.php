<?php
/**
 * Intenso Premium Theme
 *
 * @category    Itactica
 * @package     Itactica_QuickView
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */
class Tejar_Ajax_Helper_Data extends Mage_Core_Helper_Abstract
{

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

	
	 public function initProductCustom($productId, $controller, $params = null)
    {
        // Prepare data for routine
        if (!$params) {
            $params = new Varien_Object();
        }

        // Init and load product
        Mage::dispatchEvent('catalog_controller_product_init_before', array(
            'controller_action' => $controller,
            'params' => $params,
        ));

        if (!$productId) {
            return false;
        }

        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($productId);

            $visibilitySwitch = Mage::getStoreConfig('catalog/frontend/visibility');
            $dataType = $controller->getRequest()->getParam('type');
            $visibleInCatalogIds = Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds();
            if(!$dataType && (($visibilitySwitch) || ($product->isVisibleInCatalog())) && !in_array($product->getVisibility(),$visibleInCatalogIds)){
                $parentIds = Mage::getSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
                if(!empty($parentIds)){
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
                    if (!$parentProduct->isVisibleInCatalog()) {
                        return false;
                    }
                    // $params = new Varien_Object();
                    $params->setCategoryId(false);
                    $params->setConfigureMode(true);
                    $buyRequest = new Varien_Object();
                    $buyRequest->setSuperAttribute($this->generateConfigData($parentProduct, $product)); // example format: array(525 => "99"));
                    $params->setBuyRequest($buyRequest);
                    // override visibility setting of configurable product
                    // in case only simple products should be visible in the catalog
                    // TODO: make this behaviour configurable
                    $params->setOverrideVisibility(true);
                    $product = $parentProduct;
                    $productHelper = Mage::helper('catalog/product');
                    $buyRequest = $params->getBuyRequest();
                    if ($buyRequest) {
                        $productHelper->prepareProductOptions($product, $buyRequest);
                    }
                    if ($params->hasConfigureMode()) {
                        $product->setConfigureMode($params->getConfigureMode());
                    }
                }
            }

     
        if (!in_array(Mage::app()->getStore()->getWebsiteId(), $product->getWebsiteIds())) {
            return false;
        }

        // Load product current category
        $categoryId = $params->getCategoryId();
        if (!$categoryId && ($categoryId !== false)) {
            $lastId = Mage::getSingleton('catalog/session')->getLastVisitedCategoryId();
            if ($product->canBeShowInCategory($lastId)) {
                $categoryId = $lastId;
            }
        } elseif (!$product->canBeShowInCategory($categoryId)) {
            $categoryId = null;
        }

        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $product->setCategory($category);
            Mage::register('current_category', $category);
        }

        // Register current data and dispatch final events
        Mage::register('current_product', $product);
        Mage::register('product', $product);

        try {
            Mage::dispatchEvent('catalog_controller_product_init', array('product' => $product));
            Mage::dispatchEvent('catalog_controller_product_init_after',
                            array('product' => $product,
                                'controller_action' => $controller
                            )
            );
        } catch (Mage_Core_Exception $e) {
            Mage::logException($e);
            return false;
        }

        return $product;
    }
	
	 /**
     * Check if a product can be shown
     *
     * @param  Mage_Catalog_Model_Product|int $product
     * @return boolean
     */
    public function canShowCustom($product, $where = 'catalog')
    {
        if (is_int($product)) {
            $product = Mage::getModel('catalog/product')->load($product);
        }

        /* @var $product Mage_Catalog_Model_Product */

        if (!$product->getId()) {
            return false;
        }

        return $product->isVisibleInCatalog() && $product->isVisibleInSiteVisibility();
    }
	
}