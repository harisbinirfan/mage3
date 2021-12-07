<?php
/**
 * Tejar
 *
 * @category    Tejar
 * @package     Tejar_Intensolocal
 * @author      zeeshan
 */

class Tejar_Intensolocal_Helper_Mediafallback extends Itactica_Intenso_Helper_Mediafallback
{




 /**
    * For given product, get configurable images fallback array
    * Depends on following data available on product:
    * - product must have child attribute label mapping attached
    * - product must have media gallery attached which attaches and differentiates local images and child images
    * - product must have child products attached
    *
    * @param Mage_Catalog_Model_Product $product
    * @param array $imageTypes - image types to select for child products
    * @return array
    */
   public function getConfigurableImagesFallbackArray(Mage_Catalog_Model_Product $product, array $imageTypes,
       $keepFrame = false
   ) {
       if (!$product->hasConfigurableImagesFallbackArray()) {
           $mapping = $product->getChildAttributeLabelMapping();

           $mediaGallery = $product->getMediaGallery();
 //-------------------------------------------- ZEE CODE --------------------------------------------------//
   /* Homepage Sliders - Mini Swatch Fix: check if mediaGallery is empty then repopulate it
    * through product object by re-loading the product through Mage::getModel('catalog/product')..
    */
   if(count($mediaGallery)==0){
     $product = Mage::getModel('catalog/product')->load($product->getId());
     $mapping = $product->getChildAttributeLabelMapping();
     $mediaGallery = $product->getMediaGallery();
   }
 //-------------------------------------------- ZEE CODE --------------------------------------------------//
           if (!isset($mediaGallery['images'])) {
               return array(); //nothing to do here
           }

           // ensure we only attempt to process valid image types we know about
           $imageTypes = array_intersect(array('image', 'small_image'), $imageTypes);

           $imagesByLabel = array();
           $imageHaystack = array_map(function ($value) {
               return Mage_ConfigurableSwatches_Helper_Data::normalizeKey($value['label']);
           }, $mediaGallery['images']);

           // set the size for the additional images of the gallery (shown below main image)
            $additionalImagesSizes = array('thumbnail','medium_image','image');

           // load images from the configurable product for swapping

     // 3SD CODE ADD IF IS_ARRAY OR IS_OBJECT CHECK FOR REMOVING LOGS ERROR
     if (is_array($mapping) || is_object($mapping)){

           foreach ($mapping as $mapID => $map) {
               $imagePath = null;

               //search by store-specific label and then default label if nothing is found
               $imageKey = array_search($map['label'], $imageHaystack);
               // search additional images by store-specific label
               $additionalImageKeys = array_keys($imageHaystack, $map['label']);
               if ($imageKey === false) {
                   $imageKey = array_search($map['default_label'], $imageHaystack);
               }
               if ($additionalImageKeys === false) {
                   $additionalImageKeys = array_keys($imageHaystack, $map['default_label']);
               }

               //assign proper image file if found
               if ($imageKey !== false) {
                   $imagePath = $mediaGallery['images'][$imageKey]['file'];
               }
               // assign additional images
               $additionalImagePaths = array();
               foreach ($additionalImageKeys as $key) {
                   $additionalImagePaths[] = $mediaGallery['images'][$key]['file'];
               }


       $imagesByLabel[$mapID] = array();

               $imagesByLabel[$mapID][$map['label']] = array(
                   'configurable_product' => array(
                       Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_SMALL => null,
                       Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE => null,
                       'additional_images' => null,
                   ),
                   'products' => $map['product_ids'],
               );

               if ($imagePath) {
                   $imagesByLabel[$mapID][$map['label']]['configurable_product']
                       [Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_SMALL] =
                           $this->_resizeProductImage($product, 'small_image', 'small_image', $imagePath);

                   $imagesByLabel[$mapID][$map['label']]['configurable_product']
                       [Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE] =
                           $this->_resizeProductImage($product, 'image', 'image', $imagePath);
               }
               if (in_array('image', $imageTypes)) {
                   $arrayHelper = null;
                   foreach ($additionalImagePaths as $path) {
                       foreach ($additionalImagesSizes as $size) {
                           $arrayHelper[$size][] = $this->_resizeProductImage($product, 'image', $size, $path);
                       }
                   }
                   $imagesByLabel[$mapID][$map['label']]['configurable_product']['additional_images'] =
                       $arrayHelper;
               }
           }
     }
           $imagesByType = array(
               'image' => array(),
               'medium_image' => array(),
               'small_image' => array(),
               'additional_images' => array(),
           );

           // iterate image types to build image array, normally one type is passed in at a time, but could be two
           foreach ($imageTypes as $imageType) {
               // load image from the configurable product's children for swapping
               /* @var $childProduct Mage_Catalog_Model_Product */
               if ($product->hasChildrenProducts()) {
                   $additionalImages = array();
                   foreach ($product->getChildrenProducts() as $childProduct) {
                       if ($image = $this->_resizeProductImage($childProduct, $imageType, $imageType)) {
                           $imagesByType[$imageType][$childProduct->getId()] = $image;
                       }

                       // add medium sized main image and additional gallery images -  only for product page and quickview
                       if ($imageType == 'image') {
                           if ($image = $this->_resizeProductImage($childProduct, $imageType, 'medium_image')) {
                               $imagesByType['medium_image'][$childProduct->getId()] = $image;
                           }
                       }
                   }
               }

               // load image from configurable product for swapping fallback
               if ($image = $this->_resizeProductImage($product, $imageType, $imageType, null, true)) {
                   $imagesByType[$imageType][$product->getId()] = $image;
               }
               // add medium sized main image and additional gallery images - only for product page
               if ($imageType == 'image') {
                   if ($image = $this->_resizeProductImage($product, $imageType, 'medium_image', null, true)) {
                       $imagesByType['medium_image'][$product->getId()] = $image;
                   }
               }
           }

           $array = array(
               'option_labels' => $imagesByLabel,
               Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_SMALL => $imagesByType['small_image'],
               'medium_image' => $imagesByType['medium_image'],
               Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE => $imagesByType['image']
           );

           $product->setConfigurableImagesFallbackArray($array);
       }

       return $product->getConfigurableImagesFallbackArray();
   }



	/**
     * For given product, get configurable images fallback array
     * Depends on following data available on product:
     * - product must have child attribute label mapping attached
     * - product must have media gallery attached which attaches and differentiates local images and child images
     * - product must have child products attached
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $imageTypes - image types to select for child products
     * @return array
     */
    public function getSimpleImagesFallbackArray(Mage_Catalog_Model_Product $product, array $imageTypes,
        $keepFrame = false
    ) {

		// $product = Mage::getModel('catalog/product')->load($product->getId());
		$gallery = array();
		// set the size for the additional images of the gallery (shown below main image)
		$additionalImagesSizes = array('thumbnail','medium_image','image', 'label');
		foreach ($product->getMediaGallery() as $images) {
			foreach($images as $image){
				foreach ($additionalImagesSizes as $size) {
					if($size == "label"){
						$gallery[$product->getId()][$size][] = $image['label'];
						continue;
					}
					$helper = $this->_resizeProductImage($product, 'image', $size, $image['file']);
					$gallery[$product->getId()][$size][] = $helper;
				}
			}
		}

		$product->setConfigurableImagesFallbackArray($gallery);


        return $product->getConfigurableImagesFallbackArray();
    }

    /**
      * Set child_attribute_label_mapping on products with attribute label -> product mapping
      * Depends on following product data:
      * - product must have children products attached
      *
      * @param array $parentProducts
      * @param $storeId
      * @param bool $onlyListAttributes
      * @return void
      */
     public function attachProductChildrenAttributeMapping(array $parentProducts, $storeId, $onlyListAttributes = false)
     {
         /** @var  $listSwatchAttr Mage_Eav_Model_Attribute */
         $listSwatchAttr = Mage::helper('configurableswatches/productlist')->getSwatchAttribute();
         $swatchAttributeIds = array();
         if (!$onlyListAttributes) {
             $swatchAttributeIds = Mage::helper('configurableswatches')->getSwatchAttributeIds();
         }
         if ($listSwatchAttr->getId()) {
             $swatchAttributeIds[] = $listSwatchAttr->getId();
         }
         if (empty($swatchAttributeIds)) {
             return;
         }

         $parentProductIds = array();
         /* @var $parentProduct Mage_Catalog_Model_Product */
         foreach ($parentProducts as $parentProduct) {
             $parentProductIds[] = $parentProduct->getId();
         }

         $configAttributes = Mage::getResourceModel('configurableswatches/catalog_product_attribute_super_collection')
             ->addParentProductsFilter($parentProductIds)
             ->attachEavAttributes()
             ->addFieldToFilter('eav_attributes.attribute_id', array('in' => $swatchAttributeIds))
             ->setStoreId($storeId)
         ;

         $optionLabels = array();
         foreach ($configAttributes as $attribute) {
             $optionLabels += $attribute->getOptionLabels();
         }

         // normalize to all lower case before we start using them
         $optionLabels = array_map(function ($value) {
             return array_map('Mage_ConfigurableSwatches_Helper_Data::normalizeKey', $value);
         }, $optionLabels);

         foreach ($parentProducts as $parentProduct) {
             $mapping = array();
             $listSwatchValues = array();
             $listSwatchStockValues = array();

             /* @var $attribute Mage_Catalog_Model_Product_Type_Configurable_Attribute */
             foreach ($configAttributes as $attribute) {
                 /* @var $childProduct Mage_Catalog_Model_Product */
                 if (!is_array($parentProduct->getChildrenProducts())) {
                     continue;
                 }

                 foreach ($parentProduct->getChildrenProducts() as $childProduct) {

 					// $isEnabled = Mage::getSingleton('catalog/product_status')->getProductStatus($childProduct->getId());
 					// // product has no value for attribute or not available, we can't process it
 					// $isInStock = $childProduct->getStockItem()->getIsInStock();
 					// if (!$childProduct->hasData($attribute->getAttributeCode())
 					// 	  || (!$isInStock && !Mage::helper('cataloginventory')->isShowOutOfStock())
 					// 		|| $isEnabled[$childProduct->getId()]==2) {
 					//   continue;
 					// }

                    // product has no value for attribute or not available, we can't process it
                    $isInStock = $childProduct->getStockItem()->getIsInStock();
                    if (!$childProduct->hasData($attribute->getAttributeCode())
                        || (!$isInStock && !Mage::helper('cataloginventory')->isShowOutOfStock())) {
                        continue;
                    }

                     $optionId = $childProduct->getData($attribute->getAttributeCode());

                     // if we don't have a default label, skip it
                     if (!isset($optionLabels[$optionId][0])) {
                         continue;
                     }

                     // using default value as key unless store-specific label is present
                     $optionLabel = $optionLabels[$optionId][0];
                     if (isset($optionLabels[$optionId][$storeId])) {
                         $optionLabel = $optionLabels[$optionId][$storeId];
                     }

 				/*	if (!isset($mapping[$optionId])) {
                         $mapping[$optionId]= array(
                             [$optionLabel] => array(),
                         );
                     }*/

                     // initialize arrays if not present
                     if (!isset($mapping[$optionId][$optionLabel])) {
                         $mapping[$optionId][$optionLabel] = array(
                             'product_ids' => array(),
                         );
                     }

                     $mapping[$optionId]['product_ids'][] = $childProduct->getId();
                     $mapping[$optionId]['label'] = $optionLabel;
                     $mapping[$optionId]['default_label'] = $optionLabels[$optionId][0];
                     $mapping[$optionId]['labels'] = $optionLabels[$optionId];


                     if ($attribute->getAttributeId() == $listSwatchAttr->getAttributeId()
                         && !in_array($mapping[$optionId]['label'], $listSwatchValues)
                     ) {
                         $listSwatchValues[$optionId]      = $mapping[$optionId]['label'];
                         $listSwatchStockValues[$optionId] = $isInStock;
                     }
                 } // end looping child products
             } // end looping attributes


             foreach ($mapping as $key => $value) {
                 $mapping[$key]['product_ids'] = array_unique($mapping[$key]['product_ids']);
             }

             if (count($listSwatchValues)) {
                 $listSwatchValues = array_replace(array_intersect_key($optionLabels, $listSwatchValues),
                     $listSwatchValues);
             }
             $parentProduct->setChildAttributeLabelMapping($mapping)
                 ->setListSwatchAttrValues($listSwatchValues)
                 ->setListSwatchAttrStockValues($listSwatchStockValues);
         } // end looping parent products
     }

     /**
    * Resize specified type of image on the product for use in the fallback and returns the image URL
    * or returns the image URL for the specified image path if present
    *
    * @param Mage_Catalog_Model_Product $product
    * @param string $type
    * @param string $sizeType
    * @param string $image
    * @param bool $placeholder
    */
   protected function _resizeProductImage($product, $type, $sizeType, $image = null, $placeholder = false)
   {
       if (Mage::getSingleton('core/design_package')->getPackageName() != 'intenso') {
           return parent::_resizeProductImage($product, $type, $sizeType, $image, $placeholder);
       }

       $hasTypeData = $product->hasData($type) && $product->getData($type) != 'no_selection';
       if ($image == 'no_selection') {
           $image = null;
       }
       if ($hasTypeData || $image) {
           $keepAspectRatioProduct = Mage::getStoreConfig('intenso/product_page/keep_image_aspect_ratio', Mage::app()->getStore());
           $keepAspectRatioList = Mage::getStoreConfig('intenso/catalog/keep_image_aspect_ratio', Mage::app()->getStore());
           $helper = Mage::helper('catalog/image')
               ->init($product, $type, $image);

           if ($sizeType == 'thumbnail') {
               $width = 100;
               $height = 100;
           } elseif ($sizeType == 'small_image') {
               $width = 430;
               if ($keepAspectRatioList) {
                   $height = null;
               } else {
                   $height = Mage::getStoreConfig('intenso/catalog/catalog_product_height', Mage::app()->getStore());
               }
           } elseif ($sizeType == 'medium_image') {
               $width = 500;
               if ($keepAspectRatioProduct) {
                   $height = null;
               } else {
                   $height = Mage::getStoreConfig('intenso/product_page/image_fixed_height', Mage::app()->getStore());
               }
           } else {
               $width = null;
               $height = null;
           }

           // $helper->resize($width,$height);
     $helper->resize($width);

           return (string)$helper;
       }
       return false;
   }

}
