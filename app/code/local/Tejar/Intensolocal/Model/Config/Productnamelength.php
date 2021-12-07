<?php
/**
 * Intenso Premium Theme
 *
 * @category    Itactica
 * @package     Itactica_Intenso
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */
class Tejar_Intensolocal_Model_Config_Productnamelength extends Itactica_Intenso_Model_Config_Productnamelength
{
    public function toOptionArray()
    {
        return array(
            array('value' => '1',
                  'label' => Mage::helper('itactica_intenso')->__('1 Line')),

            array('value' => '2',
                  'label' => Mage::helper('itactica_intenso')->__('2 Lines')),

            array('value' => '3',
                  'label' => Mage::helper('itactica_intenso')->__('3 Lines')),
				  
			array('value' => '4',
				'label' => Mage::helper('itactica_intenso')->__('2 Line with Dots'))
        );
    }
}