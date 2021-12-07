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
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Category View block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Catalog_Block_Category_View extends Mage_Catalog_Block_Category_View
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->getLayout()->createBlock('catalog/breadcrumbs');

        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $category = $this->getCurrentCategory();
            if ($title = $category->getMetaTitle()) {
              if($category->getCollectionType() == 1 && $category->getLevel() >= 3){
                $title = $this->getMetaTitle(Mage::app()->getStore(),$category->getMetaTitle());
              } else {
                $title = $category->getMetaTitle();
              }
        if(!empty($title)){
            $headBlock->setTitle($title);
        }
            } else {
				$title = $this->getMetaTitle(Mage::app()->getStore(),$category->getName());
                if(!empty($title)){
					$headBlock->setTitle($title);
				}
			}

            if ($description = $category->getMetaDescription()) {
                $headBlock->setDescription($description);
            } else {
				if($category->getCollectionType() == 1 && $category->getLevel() >= 3){
					$description = $this->getMetaDescription(Mage::app()->getStore(),$category->getMetaTitle());
				} else {
					$description = $this->getMetaDescription(Mage::app()->getStore(),$category->getName());
				}
				$headBlock->setDescription($description);
			}

            if ($keywords = $category->getMetaKeywords()) {
                $headBlock->setKeywords($keywords);
            }
            if ($this->helper('catalog/category')->canUseCanonicalTag()) {
                $headBlock->addLinkRel('canonical', $category->getUrl());
            }
            /*
            want to show rss feed in the url
            */
            if ($this->IsRssCatalogEnable() && $this->IsTopCategory()) {
                $title = $this->helper('rss')->__('%s RSS Feed',$this->getCurrentCategory()->getName());
                $headBlock->addItem('rss', $this->getRssLink(), 'title="'.$title.'"');
            }
        }

        return $this;
    }

	/**
     * Get catalog meta title
     *
     */
	protected function getMetaTitle($store, $productTitle){

		$storeId = $store->getId();
		$countryCode = Mage::getStoreConfig('general/country/default', $storeId);
		$country = Mage::getModel('directory/country')->loadByCode($countryCode);
		$countryName = $country->getName();

		$meta_title = Mage::getStoreConfig('design/product_meta/category_meta_title', $storeId);


		if($meta_title){
      $meta_title = str_replace("{{title}}",$productTitle,$meta_title);
$meta_title = str_replace("{{country}}",$countryName,$meta_title);
			return $meta_title;
		}

		return;
	}

	/**
     * Get catalog meta description
     *
     */
	public function getMetaDescription($store, $productTitle){

		$storeId = $store->getId();
		$countryCode = Mage::getStoreConfig('general/country/default', $storeId);
		$country = Mage::getModel('directory/country')->loadByCode($countryCode);
		$countryName = $country->getName();

		$meta_description = Mage::getStoreConfig('design/product_meta/category_meta_description', $storeId);
		if($meta_description){
      $meta_description = str_replace("{{title}}",$productTitle,$meta_description);
$meta_description = str_replace("{{country}}",$countryName,$meta_description);
			return $meta_description;
		}

		return;
	}


}
