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

// on some installations the default attribute "thumbnail" is missing due to an unknown bug.
// addAttribute() will check whether the attribute exists and will insert or update it accordingly
$this->addAttribute('catalog_category', 'custom_thumbnail', array(
    'type'          => 'varchar',
    'label'         => 'Custom Thumbnail Image',
    'input'         => 'image',
    'backend'       => 'catalog/category_attribute_backend_image',
    'required'      => false,
    'sort_order'    => 5,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'group'         => 'General Information'
));

$this->addAttribute(
    'catalog_category',
    'category_slider',
    array(
        'group' => 'General Information',
        'input' => 'select',
        'type' => 'int',
        'source' => 'eav/entity_attribute_source_boolean',
        'label' => 'Category Slider',
        'required' => 0,
        'unique' => 0,
        'sort_order' => 10,
        'user_defined' => 1,
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    ));


$this->endSetup();



