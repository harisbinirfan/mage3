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
 * Catalog breadcrumbs
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Catalog_Block_Breadcrumbs extends Mage_Catalog_Block_Breadcrumbs
{

    /**
     * Preparing layout
     *
     * @return Mage_Catalog_Block_Breadcrumbs
     */
    protected function _prepareLayout()
    {
        if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
            $breadcrumbsBlock->addCrumb('home', array(
                'label'=>Mage::helper('catalog')->__('Home'),
                'title'=>Mage::helper('catalog')->__('Go to Home Page'),
                'link'=>Mage::getBaseUrl()
            ));
			
			$title = array();
			$path  = Mage::helper('catalog')->getBreadcrumbPath();
			
			if($_product = Mage::registry('current_product')) {
				
				if($_product->getCategoryIds()){
							
					$categories = Mage::getModel('catalog/category')->getCollection()
					->addAttributeToSelect('*')
					->addIsActiveFilter(1)
					->addAttributeToFilter('collection_type',array('null' => true))
					->addAttributeToFilter('entity_id',['in'=> $_product->getCategoryIds()])
					->addOrderField('name');
				
					
					$path = array();
					
					if(count($categories) > 0 && $categories->getSize()){

						$categoryId = "";
						foreach($categories as $category){
							if(!$category->hasChildren()){
								$categoryId = $category->getId();
							}
						}
						
						if($categoryId){
							$_category = Mage::getModel('catalog/category')->load($categoryId);
							$_parentCategories = $_category->getParentCategories();
							
							 // add category path breadcrumb
							foreach ($_parentCategories as $_category) {
								$paths['category'.$_category->getId()] = array(
									'label' => $_category->getName(),
									'link' => $_category->getUrl()
								);
							}
						}
						
						$path['product'] = array('label' => $_product->getName());
						foreach ($paths as $name => $breadcrumb) {
							$breadcrumbsBlock->addCrumb($name, $breadcrumb);
						}
						
					}
				} 

			}

            foreach ($path as $name => $breadcrumb) {
                $breadcrumbsBlock->addCrumb($name, $breadcrumb);
                $title[] = $breadcrumb['label'];
            }

            if ($headBlock = $this->getLayout()->getBlock('head')) {
                $headBlock->setTitle(join($this->getTitleSeparator(), array_reverse($title)));
            }
        }
        return Mage_Core_Block_Template::_prepareLayout();
    }
}
