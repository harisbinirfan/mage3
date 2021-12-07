<?php
/**
 * Intenso Premium Theme
 * 
 * @category    Itactica
 * @package     Itactica_MegaMenu
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */

class Tejar_Catalog_Model_Category_Attribute_Source_Collectiontype extends Mage_Eav_Model_Entity_Attribute_Source_Table
{
    /**
     * get possible values
     * @access public
     * @param bool $withEmpty
     * @param bool $defaultValues
     * @return array
     */
    public function getAllOptions($withEmpty = true, $defaultValues = false){
        $options =  array(
		   array(
                'label' => Mage::helper('catalog')->__('Brand'),
                'value' => 1
            ),
            array(
                'label' => Mage::helper('catalog')->__('Deals'),
                'value' => 2
            ),
            array(
                'label' => Mage::helper('catalog')->__('Best Seller'),
                'value' => 3
            ),
			array(
                'label' => Mage::helper('catalog')->__('Most Viewed'),
                'value' => 4
            ),
			array(
                'label' => Mage::helper('catalog')->__('New Arrival'),
                'value' => 5
            ),
			array(
                'label' => Mage::helper('catalog')->__('Latest'),
                'value' => 6
            ),
			array(
                'label' => Mage::helper('catalog')->__('Featured'),
                'value' => 7
            )
        );
		
        if ($withEmpty) {
            array_unshift($options, array('label'=>'', 'value'=>''));
        }
        return $options;

    }
    /**
     * get options as array
     * @access public
     * @param bool $withEmpty
     * @return string
     */
    public function getOptionsArray($withEmpty = true) {
        $options = array();
        foreach ($this->getAllOptions($withEmpty) as $option) {
            $options[$option['value']] = $option['label'];
        }
        return $options;
    }
    /**
     * get option text
     * @access public
     * @param mixed $value
     * @return string
     */
    public function getOptionText($value) {
        $options = $this->getOptionsArray();
        if (!is_array($value)) {
            $value = array($value);
        }
        $texts = array();
        foreach ($value as $v) {
            if (isset($options[$v])) {
                $texts[] = $options[$v];
            }
        }
        return implode(', ', $texts);
    }
}
