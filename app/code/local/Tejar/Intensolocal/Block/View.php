<?php
/**
 * Intenso Premium Theme
 *
 * @category    Itactica
 * @package     Itactica_LogoSlider
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */

class Tejar_Intensolocal_Block_View extends Itactica_LogoSlider_Block_View
{

	public function getCollection(){

		$category = Mage::registry('current_category');
		$categoryIds = $category->getAllChildren();
		$categories = Mage::getModel('catalog/category')
				->getCollection()
				->addAttributeToSelect('*')
				->addAttributeToFilter('include_in_menu', 0)
				->addIsActiveFilter()
				->addLevelFilter(2)
				->addOrderField('name');

		$categories->getSelect()->join(array('category_product' => $categories->getTable('catalog/category_product')),
			'main_table.entity_id = category_product.category_id' , array())
		->join(array('products' => $categories->getTable('catalog/product')),
			'products.entity_id = category_product.product_id' , array())
		->join(array('cat_index' => $categories->getTable('catalog/category_product_index')),
			'cat_index.product_id = products.entity_id AND cat_index.category_id IN ('.$categoryIds.')' , array())
		->group('main_table.entity_id');

		return $categories;
	}

}
