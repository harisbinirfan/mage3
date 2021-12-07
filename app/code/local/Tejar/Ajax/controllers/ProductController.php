<?php
/**
 * Intenso Premium Theme
 *
 * @category    Itactica
 * @package     Itactica_QuickView
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */

require_once 'Mage/Catalog/controllers/ProductController.php';
class Tejar_Ajax_ProductController extends Mage_Catalog_ProductController
{

	 protected function _initProductCustom()
    {
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');

        $params = new Varien_Object();
        $params->setCategoryId($categoryId);

        return Mage::helper('tejar_ajax/data')->initProductCustom($productId, $this, $params);
    }


		/**
	 * Default to base image type
	 *
	 * @return string
	 */
	public function getImageType() {
			$type = parent::getImageType();

			if (empty($type)) {
					$type = Mage_ConfigurableSwatches_Helper_Productimg::MEDIA_IMAGE_TYPE_BASE;
			}

			return $type;
	}

	/**
	 * instruct image image type to be loaded
	 *
	 * @return array
	 */
	protected function _getImageSizes() {
			return array('image');
	}


protected $_productListBlocks = array('product_list', 'search_result_list');


/**
	 * view action
	 * @return void
	 */
	public function viewAction() {

		$response = array();

		if ($product = $this->_initProductCustom()) {
			$this->_initProductLayout($product);
				$design = Mage::getSingleton('catalog/design');
			$settings = $design->getDesignSettings($product);

			// apply custom design
			if ($settings->getCustomDesign()) {
				$design->applyCustomDesign($settings->getCustomDesign());
			}

			Mage::getSingleton('catalog/session')->setLastViewedProductId($product->getId());

			$update = $this->getLayout()->getUpdate();
			$update->addHandle('default');

			$this->addActionLayoutHandles();
			$update->addHandle($product->getLayoutUpdateHandle());
			$update->addHandle('PRODUCT_' . $product->getId());

			$this->loadLayoutUpdates();


			// apply custom layout update once layout is loaded
			if (($layoutUpdates = $settings->getLayoutUpdates())) {
				if (is_array($layoutUpdates)) {
					foreach ($layoutUpdates as $layoutUpdate) {
						$update->addUpdate($layoutUpdate);
					}
				}
			}

			$this->generateLayoutXml()->generateLayoutBlocks();


			// apply custom layout (page) template once the blocks are generated
			if ($settings->getPageLayout()) {
				$this->getLayout()->helper('page/layout')->applyTemplate($settings->getPageLayout());
			}

			if (($root = $this->getLayout()->getBlock('root'))) {
				$root->addBodyClass('productpath-' . $product->getUrlPath())
									->addBodyClass('product-' . $product->getUrlKey());
			}

			$this->_initLayoutMessages('catalog/session');
			$this->_initLayoutMessages('checkout/session');


			if ($this->getRequest()->isAjax()) {
				$dataType = $this->getRequest()->getParam('type');

				if($dataType === "detail"){
					$attributes = "";
					$shortDescription = "";
					$description = "";
					$related = "";
					$truncateDescription = "";
					$alsobought = "";
					$productId = "";
					$upsell = "";
					$crosssell = "";

					if($product){
						$_description = $product->getDescription();
						$shortDescription = $product->getShortDescription();
						$_description = strip_tags($_description);
						if(strlen($_description) >= 200){
							$truncateDescription = Mage::helper('core/string')->truncate($_description, 180);
						} else {
							$truncateDescription = 	$_description;
						}
						$productId = $product->getId();
					}



					if($this->getLayout()->getBlock('product.attributes')){
						$attributes = $this->getLayout()->getBlock('product.attributes')->toHtml();
					}

					if($this->getLayout()->getBlock('product.description')){
						$description = $this->getLayout()->getBlock('product.description')->toHtml();
					}

					$label = "";
					$_isNewLabel = Mage::getStoreConfig('intenso/product_label/new_label', Mage::app()->getStore());
					if($_isNewLabel){
						$now = date("Y-m-d");
						$newsFrom= substr($product->getData('news_from_date'),0,10);
						$newsTo=  substr($product->getData('news_to_date'),0,10);
						if ($newsTo != '' || $newsFrom != ''){
							if (($newsTo != '' && $newsFrom != '' && $now>=$newsFrom && $now<=$newsTo) || ($newsTo == '' && $now >=$newsFrom) || ($newsFrom == '' && $now<=$newsTo))
							{
								$label = "<div class='intenso-product-label-wrapper position-top-left' style='margin: 6px;'>";
								$label .= "<span class='intenso-product-label' style='background-image: url(".Mage::getDesign()->getSkinUrl('images/discount.svg')."); color: #fff;padding: 15px 5px;font-size: 12px;width: 40px;height: 40px;font-weight: 600;'>New</span>";
								$label .= "</div>";
							}
						}
					}

					// response
					$response = array(
						'product_detail' => $data = array(
							$productId => $data = array(
								'attributes' => $attributes,
								'shortDescription' => $shortDescription,
								'description' => $description,
								'truncateDescription' => $truncateDescription,
								'label' => $label
							)
						)
					);

				} elseif($dataType === "media") {

					if($this->getLayout()->getBlock('product.info.media')){
						$mediaGallery = $this->getLayout()->getBlock('product.info.media')->toHtml();
					}

					$helper = Mage::helper('configurableswatches/mediafallback');

					$imageFallback = $helper->getSimpleImagesFallbackArray($product, $this->_getImageSizes(), null);

					$response = array(
						'additional_images' => $imageFallback
					);

				} else if(!$dataType){

					$swatches = "";
					if($this->getLayout()->getBlock('product.info')){
						$swatches = $this->getLayout()->getBlock('product.info')->toHtml();
					}

					$related = "";
					if($this->getLayout()->getBlock('catalog.product.related')){
						$related = $this->getLayout()->getBlock('catalog.product.related')->toHtml();
					}

					$alsobought = "";
					if($this->getLayout()->getBlock('catalog.product.alsobought')){
						$alsobought = $this->getLayout()->getBlock('catalog.product.alsobought')->toHtml();
					}

					$upsell = "";
					if($this->getLayout()->getBlock('product.info.upsell')){
						$upsell = $this->getLayout()->getBlock('product.info.upsell')->toHtml();
					}
					
					$crosssell = "";
					if($this->getLayout()->getBlock('product.info.upsell')){
						$crosssell = $this->getLayout()->getBlock('product.info.crosssell')->toHtml();
					}

					$response = array(
						'swatches' => $swatches,
						'related' => $related,
						'alsobought' => $alsobought,
						'upsell' => $upsell,
						'crosssell' => $crosssell
					);
				}

				$this->getResponse()->setHeader('Content-Type', 'application/json', true);
				$this->getResponse()->setBody(json_encode($response));
			} else {
				$this->renderLayout();
			}
		} elseif (!$this->getResponse()->isRedirect()) {
			$this->_forward('noRoute');
		}

	}

}
