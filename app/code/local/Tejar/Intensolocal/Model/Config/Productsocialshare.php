<?php
/**
 * Intenso Premium Theme
 *
 * @category    Itactica
 * @package     Itactica_Intenso
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */
class Tejar_Intensolocal_Model_Config_Productsocialshare
{

	/**
     * google fonts styles
     * @var array
     */
    public function productSocialMultiselect()
    {
         return array(
            array('value' => 'facebook',
                  'label' => Mage::helper('itactica_intenso')->__('Facebook')),

            array('value' => 'twitter',
                  'label' => Mage::helper('itactica_intenso')->__('Twitter')),

            array('value' => 'googleplus',
                  'label' => Mage::helper('itactica_intenso')->__('Google plus')),

      			array('value' => 'tumblr',
      				'label' => Mage::helper('itactica_intenso')->__('Tumblr')),

      			array('value' => 'pinterest',
      				'label' => Mage::helper('itactica_intenso')->__('Pinterest')),

            array('value' => 'whatsapp',
            				'label' => Mage::helper('itactica_intenso')->__('Whatsapp'))
        );
    }
}
