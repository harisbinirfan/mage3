<?php
/**
 * Intenso Premium Theme
 * 
 * @category    Itactica
 * @package     Itactica_Billboard
 * @copyright   Copyright (c) 2014-2015 Itactica (http://www.itactica.com)
 * @license     http://getintenso.com/license
 */

class Tejar_Billboard_Model_Unit_Attribute_Source_Images extends Itactica_Billboard_Model_Unit_Attribute_Source_Images
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
                'label' => Mage::helper('itactica_featuredproducts')->__('First image'),
                'value' => 1
            ),
            array(
                'label' => Mage::helper('itactica_featuredproducts')->__('Second image'),
                'value' => 2
            ),
            array(
                'label' => Mage::helper('itactica_featuredproducts')->__('Third image'),
                'value' => 3
            ),
            array(
                'label' => Mage::helper('itactica_featuredproducts')->__('Fourth image'),
                'value' => 4
            ),
			array(
                'label' => Mage::helper('itactica_featuredproducts')->__('Fifth image'),
                'value' => 5
            ),
        );
        if ($withEmpty) {
            array_unshift($options, array('label'=>'', 'value'=>''));
        }
        return $options;

    }
   
}
