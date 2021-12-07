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

/**
 * Catalog product model
 *
 * @method Mage_Catalog_Model_Resource_Product getResource()
 * @method Mage_Catalog_Model_Product setHasError(bool $value)
 * @method null|bool getHasError()
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Catalog_Model_Product extends Mage_Catalog_Model_Product
{
	  /**
     * Product object customization (not stored in DB)
     *
     * @var array
     */
    protected $_brand = array();
	
	
	
	

	/**
     * Get product type identifier
     *
     * @return string
     */
    public function getLastCategory()
    {
	
		$collection = Mage::getModel('catalog/category')->getCollection();
		$collection->addAttributeToSelect(array('name','collection_type'))
		->addAttributeToFilter('entity_id',['in'=>$this->getCategoryIds()])
		->addIsActiveFilter(1);
		
		$levels = array();
		$products = array();
		foreach($collection as $category){
			$levels[] = $category->getLevel();
			$products[$category->getLevel()] = $category;
	
			if($category->getCollectionType() == 1){
				$this->_brand = $category;
			}
		}
		
		
		
		
		
		return $products[max($levels)];
		
    }
	
	
	/**
	 * Get product type identifier
	 *
	 * @return string
	 */
    public function getBrandCategory()
    {
		
		if(empty($this->_brand)){
			$this->getLastCategory();
		}
		
		
		return $this->_brand;
    }


}
