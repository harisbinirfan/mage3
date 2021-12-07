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
 * @package     Mage_CatalogSearch
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Edited: M. zeeshan
 */

/**
 * Autocomplete queries list
 */
class Tejar_CatalogSearch_Block_Autocomplete extends Mage_CatalogSearch_Block_Autocomplete
{

    protected $_suggestData = null;

    protected function _toHtml()
    {
        $html = '';

        if (!$this->_beforeToHtml()) {
            return $html;
        }

        $suggestData = $this->getSuggestData();
        if (!($count = count($suggestData))) {
            return $html;
        }

        $isAjaxSuggestionCountResultsEnabled = (bool) Mage::app()->getStore()
            ->getConfig(Mage_CatalogSearch_Model_Query::XML_PATH_AJAX_SUGGESTION_COUNT);

        $count--;

		if(!empty($suggestData["Products"]) || !empty($suggestData["Categories"])){
			$html = '<ul id="myAutoComplete" class="myAutoComplete"><li style="display:none" class="highlight-search-row"></li>';
		}


		// var_dump($suggestData); die;

		foreach($suggestData as $key => $suggest){
			if(sizeof($suggest) > 0){
				$html .= "<li class='search-title-label'><span>".$key."</span></li>";
			}
			foreach ($suggest as $index => $item) {
				if ($index == 0) {
					$item['row_class'] .= ' first';
				}

				if ($index == $count) {
					$item['row_class'] .= ' last';
				}

				$html .=  '<li onclick="window.location =\''.$item['url'].'\'" title="' . $this->escapeHtml($item['title']) . '" title="' . $this->escapeHtml($item['title']) . '" class="search-item ' . $item['row_class'] . '">';
				$html .= "<div class='search-detail'> ";
				if ($isAjaxSuggestionCountResultsEnabled) {
					$html .= '<span class="amount">' . $item['num_of_results'] . '</span>';
				}
				$html .= "<div class='search-title'> ";

				$titleWithCategory       = $item['title'];
				$titleWithCategoryLength = strlen($titleWithCategory)+7;

				//--- GET Current Theme(Desktop/Mobile/Tablet) Name based on Template...
				$currentTheme = Mage::getSingleton('core/design_package')->getTheme('template');
				if($currentTheme == "tejar-mobile"){
					$title = strlen( trim($item['title']))>=35 && $titleWithCategoryLength>=77?substr($item['title'], 0,35)."...":$item['title'];
				}elseif($currentTheme == "tejar-tablet"){
					$title = strlen( $item['title'])>=60 && $titleWithCategoryLength>=75?substr($item['title'], 0,60)."...":$item['title'];
				}elseif($currentTheme == "tejar"){
						//echo $titleWithCategoryLength;die;
					$title = strlen( $item['title'])>=80 && $titleWithCategoryLength>=90?substr($item['title'], 0,80)."...":$item['title'];

				}

				$html .= "<h2 class='name'>".$this->escapeHtml($title) . "</h2>";
				if(isset($item['category_name']) && isset($item['category_name'])){
					$html .=  "<span style='display: inline-block !important;'>in <a href='".$item['category_url']."'>".$this->escapeHtml($item['category_name'])."</a></span>";
				}
				$html .= "</div>";
				$html .= "</div>";
				$html .= '</li>';
			}
		}

		if(!empty($suggestData["Products"]) || !empty($suggestData["Categories"])){
			$html.= '</ul>';
		}

        return $html;
    }

    public function getSuggestData()
    {
        if (!$this->_suggestData) {
            $collection = $this->helper('catalogsearch')->getSuggestCollection();
			$_productCollection = $collection['product_collection'];
			$_categoryCollection = $collection['category_collection'];
			// $categorycollection = $this->helper('catalogsearch')->getCategoryCollection();
			$collectionArray = "";
            $query = $this->helper('catalogsearch')->getQueryText();
            $counter = 0;
            $data = array();

			foreach ($_productCollection as $item) {

				$categoryids = $item->getCategoryIds();

				//--- 3SD CODE If Flat Data enabled then use it but only on frontend
				$flatHelper = Mage::helper('catalog/category_flat');
				if ($flatHelper->isAvailable() && !Mage::app()->getStore()->isAdmin() && $flatHelper->isBuilt(true) && !$this->getDisableFlat()) {

					$collection = Mage::getResourceModel('catalog/category_flat_collection');
					$collection->addAttributeToSelect(array('name', 'url_key'));
					$collection->getSelect()->Where("(`is_active` = '1')  AND (`include_in_menu` = '1')  AND (`collection_type` IS NULL) AND (`entity_id` IN ('" . join("', '", $categoryids). "'))");

				} else {

					$collection = Mage::getResourceModel('catalog/category_collection')
					->addAttributeToSelect(array('name', 'url_key'))
					->addIsActiveFilter(1)
					->addAttributeToFilter('include_in_menu',['eq'=>1]);
					$collection->getSelect()->Where("`e`.`entity_id` IN ('" . join("', '", $categoryids). "')");
				}


				$categoryName = null;
				$categoryUrl = null;
				$i = 1;
				foreach($collection as $key => $catagory){
					if($i == count($collection)){
						$categoryName = $catagory->getName();
						$categoryUrl = $catagory->getUrl();
					}
					$i++;
				}

                $_data = array(
                    'title' => $item->getQueryText(),
					'url' => $item->getProductUrl(),
					'category_name'       => $categoryName,
					'category_url'       => $categoryUrl,
                    'row_class' => (++$counter)%2?'odd':'even',
                    'num_of_results' => $item->getNumResults()
                );

                // if ($item->getQueryText() == $query) {
                //     array_unshift($data, $_data);
                // }
                // else {
                    $data[] = $_data;
                // }
            }

			$collectionArray["Products"] = $data;

			if(sizeof($_categoryCollection) > 0){
				$counter = 0;
				$categoryData = array();
				foreach ($_categoryCollection as $item) {
					$_data = array(
						'title' => $item->getQueryText(),
						'row_class' => (++$counter)%2?'odd':'even',
						'url'            => $item->getUrl(),
						'num_of_results' => $item->getNumResults()
					);

					if ($item->getQueryText() == $query) {
						array_unshift($categoryData, $_data);
					}
					else {
						$categoryData[] = $_data;
					}
				}

				$collectionArray["Categories"] = $categoryData;

			}

            $this->_suggestData = $collectionArray;
        }
        return $this->_suggestData;
    }
/*
 *
*/
}
