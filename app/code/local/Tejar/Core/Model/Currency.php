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
 * @package     Mage_Directory
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Currency model
 *
 * @category   Mage
 * @package    Mage_Directory
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Core_Model_Currency extends Mage_Directory_Model_Currency
{

    /**
     * Configuration Decimals
     */
    // const XML_CATALOG_PRICE_DECIMALS       = 'catalog/price/decimals';

    public function isDecimal( $val )
	{
		return is_numeric( $val ) && floor( $val ) != $val;
	}

    /**
     * Format price to currency format
     *
     * @param float $price
     * @param array $options
     * @param bool $includeContainer
     * @param bool $addBrackets
     * @return string
     */
    public function format($price, $options = array(), $includeContainer = true, $addBrackets = false)
    {
		// $precision = Mage::getStoreConfig(self::XML_CATALOG_PRICE_DECIMALS, Mage::app()->getStore());
    //
		// if(!is_null($precision)){
		// 	return $this->formatPrecision($price, $precision, $options, $includeContainer, $addBrackets);
		// }

    if($this->isDecimal($price) == false){
			return $this->formatPrecision($price, 0, $options, $includeContainer, $addBrackets);
		}

		return $this->formatPrecision($price, 2, $options, $includeContainer, $addBrackets);
    }

}
