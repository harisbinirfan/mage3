<?php
/**
 * Intenso Premium Theme
 * 
 * @category    Itactica
 * @package     Itactica_Billboard
 * @copyright   Copyright (c) 2014-2015 Itactica (http://www.itactica.com)
 * @license     http://getintenso.com/license
 */

class Tejar_Billboard_Model_Unit_Attribute_Source_Columns extends Itactica_Billboard_Model_Unit_Attribute_Source_Columns
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
                'label' => Mage::helper('itactica_featuredproducts')->__('One Columns'),
                'value' => 1
            ),
            array(
                'label' => Mage::helper('itactica_featuredproducts')->__('Two Columns'),
                'value' => 2
            ),
            array(
                'label' => Mage::helper('itactica_featuredproducts')->__('Three Columns'),
                'value' => 3
            ),
            array(
                'label' => Mage::helper('itactica_featuredproducts')->__('Four Columns'),
                'value' => 4
            ),
			array(
                'label' => Mage::helper('itactica_featuredproducts')->__('Five Columns'),
                'value' => 5
            ),
        );
        if ($withEmpty) {
            array_unshift($options, array('label'=>'', 'value'=>''));
        }
        return $options;

    }

}
