<?php

/**
 * Catalog data helper
 *
 * @category   Tejar
 * @package    Tejar_Catalog
 * @author     Zeeshan
 */
 
class Tejar_Catalog_Helper_Eav extends Mage_Eav_Helper_Data{
	
	/**
     * Return default frontend classes value labal array
     *
     * @return array
     */
    protected function _getDefaultFrontendClasses()
    {
        return array(
            array(
                'value' => '',
                'label' => Mage::helper('eav')->__('None')
            ),
            array(
                'value' => 'validate-number',
                'label' => Mage::helper('eav')->__('Decimal Number')
            ),
            array(
                'value' => 'validate-digits',
                'label' => Mage::helper('eav')->__('Integer Number')
            ),
            array(
                'value' => 'validate-email',
                'label' => Mage::helper('eav')->__('Email')
            ),
            array(
                'value' => 'validate-url',
                'label' => Mage::helper('eav')->__('URL')
            ),
            array(
                'value' => 'validate-alpha',
                'label' => Mage::helper('eav')->__('Letters')
            ),
			array(
                'value' => 'validate-number validate-zero-or-greater validate-number-range number-range-0-99999999.9999',
                'label' => Mage::helper('eav')->__('Zero or Greater and Number Range')
            ),
            array(
                'value' => 'validate-alphanum',
                'label' => Mage::helper('eav')->__('Letters (a-z, A-Z) or Numbers (0-9)')
            )
        );
    }

}