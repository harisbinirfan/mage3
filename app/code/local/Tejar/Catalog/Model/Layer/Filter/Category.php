<?php
/**
 * Intenso Premium Theme
 *
 * @category    Itactica
 * @package     Itactica_LayeredNavigation
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */

class Tejar_Catalog_Model_Layer_Filter_Category extends Itactica_LayeredNavigation_Model_Catalog_Layer_Filter_Category
{
    

    /**
     * Get data array for building category filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $key = $this->getLayer()->getStateKey().'_SUBCATEGORIES';
        $data = $this->getLayer()->getAggregator()->getCacheData($key);

        if ($data === null) {
            $categoty   = $this->getCategory();
			
            /** @var $categoty Mage_Catalog_Model_Categeory */
            $categories = $categoty->getChildrenCategories();

			//--- 3SD CODE FILTER INCLUDE IN MENU
            $this->getLayer()->getProductCollection()->addCountToCategories($categories);

            $data = array();
            foreach ($categories as $category) {
                if ($category->getIsActive() && $category->getProductCount()) {
					
					$urlKey = $category->getUrlKey();
					/***** TO DO *******/
					// check for category name or category id if flat catalog is enabled
					if (true || empty($urlKey)) {
						$urlPath = explode('/',$category->getRequestPath());
						if (is_array($urlPath) && end($urlPath)) {
							$categoryUrlSuffix = Mage::helper('catalog/category')->getCategoryUrlSuffix();
							$urlKey = str_replace($categoryUrlSuffix, '', end($urlPath));
						} else {
							$urlKey = $category->getId();
						}
					}
					
                    $data[] = array(
                        'label' => Mage::helper('core')->escapeHtml($category->getName()),
                        'value' => $urlKey,
                        'count' => $category->getProductCount(),
                    );
                }
            }
            $tags = $this->getLayer()->getStateTags();
            $this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
        }
        return $data;
    }
	
}
