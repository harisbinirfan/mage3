<?php
/**
 * Intenso Premium Theme
 * 
 * @category    Itactica
 * @package     Itactica_FeaturedCategories
 * @copyright   Copyright (c) 2014-2015 Itactica (http://www.itactica.com)
 * @license     http://getintenso.com/license
 */
$installer = $this;
$this->startSetup();

$this->addAttribute(
    'catalog_category',
    'collection_type',
    array(
        'group' => 'General Information',
        'input' => 'select',
        'type' => 'int',
        'source' => 'tejar_catalog/category_attribute_source_collectiontype',
        'label' => 'Collection Type',
        'required' => 0,
        'unique' => 0,
        'sort_order' => 10,
        'user_defined' => 1,
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
	)
);

$this->endSetup();



