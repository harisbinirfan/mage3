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
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Tejar_Catalog_Model_Convert_Adapter_Product
    extends Mage_Catalog_Model_Convert_Adapter_Product
{
   

    /**
     * Load product collection Id(s)
     */
    public function load()
    {
        // Add any additional attributes you want to filter on here
        $attrFilterArray = array(
            'dim_weight' => 'fromTo',
        );
		
		$filters = $this->_parseVars();
		
		if ($dimWeight = $this->getFieldValue($filters, 'dim_weight')) {
            $this->_filter[] = array(
                'attribute' => 'dim_weight',
                'from'      => $dimWeight['from'],
                'to'        => $dimWeight['to']
            );
            $this->setJoinAttr(array(
                'alias'     => 'dim_weight',
                'attribute' => 'catalog_product/dim_weight',
                'bind'      => 'entity_id',
                'joinType'  => 'LEFT'
            ));
        } 
		

        $this->setFilter($attrFilterArray, array());

        return parent::load();

    }

   
}
