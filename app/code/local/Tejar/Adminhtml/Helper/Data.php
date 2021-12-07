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
 * @package     Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml Catalog helper
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Adminhtml_Helper_Data extends Mage_Core_Helper_Abstract
{
	 /**
     * format category tree
     * @access public
     * @param Varien_Data_Tree_Node $node
     * @param array $values
     * @param int $level
     * @return array
     */
    private function _formatCategoryTree(Varien_Data_Tree_Node $node, $values, $level = 0) {
        $nonEscapableNbspChar = html_entity_decode('&#160;', ENT_NOQUOTES, 'UTF-8');
        $level++;

        if ($level > 1) {  
            $values[$node->getId()] = str_repeat($nonEscapableNbspChar, ($level - 2) * 4) . $node->getName();
        }
        
        foreach ($node->getChildren() as $child) {
            $values = $this->_formatCategoryTree($child, $values, $level);
        }
        
        return $values;
    }

    /**
     * retrieve category tree
     * @access public
     * @return array
     */
    public function getCategoryTree() {
        $tree = Mage::getResourceSingleton('catalog/category_tree')->load();    
        $root = $tree->getNodeById(1);
        
        if($root && $root->getId() == 1) {
            $root->setName(Mage::helper('catalog')->__('Root')); 
        }

        $collection = Mage::getModel('catalog/category')->getCollection();
		$collection->addAttributeToSelect('name');
		
        $tree->addCollectionData($collection, true); 

        return $this->_formatCategoryTree($root, array());    
    }
	
}