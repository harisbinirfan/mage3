<?php
/**
 * Intenso Premium Theme
 *
 * @category    Itactica
 * @package     Itactica_LayeredNavigation
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */

class Tejar_LayeredNavigation_Helper_Data extends Itactica_LayeredNavigation_Helper_Data
{
    

    /**
     * Method to get url for layered navigation
     * @param array $filters      array with new filter values
     * @param boolean $noFilters  to add filters to the url or not
     * @param array $q            array with values to add to query string
     * @return string
     */
    public function getFilterUrl(array $filters, $noFilters = false, array $q = array())
    {
        // get current url to extract query string parameters
        $query = array(
            'isLayerAjax' => null, // this needs to be removed because of ajax request
            Mage::getBlockSingleton('page/html_pager')->getPageVarName() => null // exclude current page from urls
        );
        $query = array_merge($query, $q);
        $params = array(
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => $query,
            '_escape' => false,
        );
        $urlParts = Mage::getUrl('*/*/*', $params);

        // get filters and paths
        $currentUrl = Mage::helper('core/url')->getCurrentUrl();
        $layerParams = $this->getCurrentLayerParams($filters);
        $suffix = Mage::getStoreConfig('catalog/seo/category_url_suffix');
        $currentCategory = Mage::registry('current_category');
        $currentPath = str_replace($suffix, '', $currentCategory->getUrlPath());
        if (strpos($currentUrl, '/cat/') !== false) {
            $currentCategoryParts = explode('/', $currentPath);
            $normalizedPath = str_replace('/' . end($currentCategoryParts), '', $currentPath); // remove last cat
        } else {
            $currentCategoryParts = 0;
            $normalizedPath = $currentPath;
        }
        $urlPath = '';

        if (!$noFilters) {
            // add filters
            foreach ($layerParams as $key => $value) {
                if ($key == 'cat') {
                    // decode slash and remove url suffix
                    $value = str_replace(urlencode('/'), '/', urlencode($value));
                    $value = str_replace($suffix, '', $value);
                    // remove parent category and take only last category path
                    $catPath = explode('/',$value);
                    $value = end($catPath);
                    $urlPath .= "/{$key}/{$value}";
                    $url = (count($currentCategoryParts) > 1 && empty($filters)) ? $normalizedPath : $currentPath;
                } else {
                    // encode and replace escaped delimiter with the delimiter itself
					$str = urlencode($value);
					$pattern = "/".urlencode(self::MULTIPLE_FILTERS_DELIMITER)."/i";
					if(preg_match($pattern, $str)){
						$value = str_replace(urlencode(self::MULTIPLE_FILTERS_DELIMITER), self::MULTIPLE_FILTERS_DELIMITER, urlencode($value));
					}
					
                    $urlPath .= "/{$key}/{$value}";
                    // remove last category from url
                    $url = (count($currentCategoryParts) > 1 && !isset($filters['cat'])) ? $normalizedPath : $currentPath;
                }
                $url = Mage::getBaseUrl() . $url . $this->getRoutingSuffix() . $urlPath;
            }
        }

        // Skip adding routing suffix for links with no filters
        if (empty($urlPath) && empty($urlParts[1])) {
            if ($resetFilterUrl = Mage::getSingleton('catalog/session')->getResetFiltersUrl()) {
                return $resetFilterUrl;
            } else {
                return Mage::getBaseUrl() . $currentPath . $suffix;
            }
        } elseif (empty($urlPath) && !empty($urlParts[1])) {
            return $urlParts;
        }

        $urlParts = explode('?', $urlParts);
        $url = $url . $suffix;
        if (!empty($urlParts[1])) {
            $url .= '?' . $urlParts[1];
        }
        return $url;
    }

   

}
