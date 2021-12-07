<?php
/* @category   Tejar
 * @package    Tejar_ProductAlert
 * @author     Zeeshan
 */
class Tejar_Catalog_Block_Product_View extends Mage_Catalog_Block_Product_View
{
  /**
     * Configuration Decimals
     */
    const XML_CATALOG_PRICE_DECIMALS       = 'catalog/price/decimals';

	/**
     * Get catalog whishlist id
     *
     */
    public function getWhishlistId()
    {
        $_collection = Mage::helper('wishlist')->getWishlistItemCollection();
        $ProductsId = "";
        if ($_collection->getSize()){
			foreach($_collection as $product){
				$ProductsId .= $product->getProductId() . ',';
			}
			$ProductsId = rtrim($ProductsId, ',');
			return $ProductsId;
        }
        return false;
    }

    /**
     * Add meta information from product to head block
     *
     * @return Mage_Catalog_Block_Product_View
     */
    protected function _prepareLayout()
    {
        $breadcrumbsBlock = $this->getLayout()->createBlock('catalog/breadcrumbs');
        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $product = $this->getProduct();
			$_product = Mage::getModel('catalog/product')->load($product->getId());
			if($_product->getCategoryIds()){
				$categories = Mage::getModel('catalog/category')->getCollection()
				->addAttributeToSelect('*')
				->addIsActiveFilter(1)
				->addAttributeToFilter('collection_type',array('null' => true))
				->addAttributeToFilter('entity_id',['in'=> $_product->getCategoryIds()])
				->addOrderField('name');
				if(count($categories) > 0 && $categories->getSize()){
					$breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs');
					$path  = array();
					$categoryId = "";
					foreach($categories as $category){
						if(!$category->hasChildren()){
							$categoryId = $category->getId();
						}
					}
					if($categoryId){
						$_category = Mage::getModel('catalog/category')->load($categoryId);
						$_parentCategories = $_category->getParentCategories();
						 // add category path breadcrumb
						foreach ($_parentCategories as $_category) {
							$path['category'.$_category->getId()] = array(
								'label' => $_category->getName(),
								'link' => $_category->getUrl()
							);
						}
					}
					$path['product'] = array('label'=>$this->getProduct()->getName());
					foreach ($path as $name => $breadcrumb) {
						$breadcrumbsBlock->addCrumb($name, $breadcrumb);
					}
				}
			}
            $title = $product->getMetaTitle();
            if ($title) {
                $headBlock->setTitle($title);
            } else {
				$title = $this->getMetaTitle(Mage::app()->getStore(),$product->getName());
				if(!empty($title)){
					$headBlock->setTitle($title);
				}
			}
			// var_dump($title); die;
            $keyword = $product->getMetaKeyword();
            $currentCategory = Mage::registry('current_category');
            if ($keyword) {
                $headBlock->setKeywords($keyword);
            }
            $description = $product->getMetaDescription();
            if ($description) {
                $headBlock->setDescription( ($description) );
            } else {
                // $headBlock->setDescription(Mage::helper('core/string')->substr($product->getDescription(), 0, 255));
				$description = $this->getMetaDescription(Mage::app()->getStore(),$product->getName());
				$headBlock->setDescription($description);
            }
            if ($this->helper('catalog/product')->canUseCanonicalTag()) {
                $params = array('_ignore_category' => true);
                $headBlock->addLinkRel('canonical', $product->getUrlModel()->getUrl($product, $params));
            }
        }
        return Mage_Catalog_Block_Product_Abstract::_prepareLayout();
    }

    /**
     * Get catalog meta title
     *
     */
	protected function getMetaTitle($store, $productTitle){
		$storeId = $store->getId();
		$countryCode = Mage::getStoreConfig('general/country/default', $storeId);
		$country = Mage::getModel('directory/country')->loadByCode($countryCode);
		$countryName = $country->getName();
		$meta_title = Mage::getStoreConfig('design/product_meta/meta_title', $storeId);
		if($meta_title){
      $meta_title = str_replace("{{title}}",$productTitle,$meta_title);
$meta_title = str_replace("{{country}}",$countryName,$meta_title);
			return $meta_title;
		}
		return;
	}
	/**
     * Get catalog meta description
     *
     */
	public function getMetaDescription($store, $productTitle){
		$storeId = $store->getId();
		$countryCode = Mage::getStoreConfig('general/country/default', $storeId);
		$country = Mage::getModel('directory/country')->loadByCode($countryCode);
		$countryName = $country->getName();
		$meta_description = Mage::getStoreConfig('design/product_meta/meta_description', $storeId);
		if($meta_description){
      $meta_description = str_replace("{{title}}",$productTitle,$meta_description);
$meta_description = str_replace("{{country}}",$countryName,$meta_description);
			return $meta_description;
		}
		return;
	}

	/**
     * Get catalog whishlist id
     *
     */
    public function getCompareId()
    {
        $_collection = Mage::helper('catalog/product_compare')->getItemCollection();
        $ProductsId = "";
         if ($_collection->getSize()){
			foreach($_collection as $product){
				$ProductsId .= $product->getProductId() . ',';
			}
			$ProductsId = rtrim($ProductsId, ',');
			return $ProductsId;
        }
        return false;
    }

  	/**
  	 * Get JSON encoded configuration array which can be used for JS dynamic
  	 * price calculation depending on product options
  	 *
  	 * @return string
  	 */
      public function getJsonConfig()
      {
          $config = array();
          if (!$this->hasOptions()) {
              return Mage::helper('core')->jsonEncode($config);
          }

          /* @var $product Mage_Catalog_Model_Product */
          $product = $this->getProduct();

          /** @var Mage_Catalog_Helper_Product_Type_Composite $compositeProductHelper */
          $compositeProductHelper = $this->helper('catalog/product_type_composite');
          $config = array_merge(
              $compositeProductHelper->prepareJsonGeneralConfig(),
              $compositeProductHelper->prepareJsonProductConfig($product)
          );

          $responseObject = new Varien_Object();
          Mage::dispatchEvent('catalog_product_view_config', array('response_object' => $responseObject));
          if (is_array($responseObject->getAdditionalOptions())) {
              foreach ($responseObject->getAdditionalOptions() as $option => $value) {
                  $config[$option] = $value;
              }
          }


  		/* 3SD CODE SET Price Format Decimals */
  		$precision = Mage::getStoreConfig(self::XML_CATALOG_PRICE_DECIMALS, Mage::app()->getStore());
  		if(!is_null($precision)){
  			$config['priceFormat']['precision'] = $precision;
  			$config['priceFormat']['requiredPrecision'] = $precision;
  		}

          return Mage::helper('core')->jsonEncode($config);
      }

}
