<?php
/**
 * Intenso Premium Theme
 *
 * @category    Itactica
 * @package     Itactica_LayeredNavigation
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */

require_once 'Itactica/LayeredNavigation/controllers/CategoryController.php';

class Tejar_LayeredNavigation_CategoryController extends Itactica_LayeredNavigation_CategoryController
{


	// 3SD CODE Overwrite
    public function viewAction()
    {
		// echo "shariq"; die;
        if (($category = $this->_initCatagory())) {
            $design = Mage::getSingleton('catalog/design');
            $settings = $design->getDesignSettings($category);
			$displayMode = $category->getData('display_mode');
            // apply custom design
            if ($settings->getCustomDesign()) {
                $design->applyCustomDesign($settings->getCustomDesign());
            }

            Mage::getSingleton('catalog/session')->setLastViewedCategoryId($category->getId());

            $update = $this->getLayout()->getUpdate();
            $update->addHandle('default');

            if (!$category->hasChildren()) {
                $update->addHandle('catalog_category_layered_nochildren');
            }

            $this->addActionLayoutHandles();
            $update->addHandle($category->getLayoutUpdateHandle());
            $update->addHandle('CATEGORY_' . $category->getId());
            // apply custom ajax layout
            if ($this->getRequest()->isAjax()) {
                $update->addHandle('catalog_category_layered_ajax_layer');
            }
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
                $root->addBodyClass('categorypath-' . $category->getUrlPath())
                    ->addBodyClass('category-' . $category->getUrlKey());
            }

            $this->_initLayoutMessages('catalog/session');
            $this->_initLayoutMessages('checkout/session');

            // return json formatted response for ajax
            if ($this->getRequest()->isAjax()) {
				$topMenu = $this->getLayout()->getBlock('mega.menu')->toHtml();
				// if($displayMode  == "PRODUCTS" || $displayMode == "PRODUCTS_AND_PAGE"){
					$listing = $this->getLayout()->getBlock('product_list')->toHtml();
					$layer = $this->getLayout()->getBlock('catalog.leftnav')->toHtml();

					// Fix urls that contain '___SID=U'
					$urlModel = Mage::getSingleton('core/url');
					$listing = $urlModel->sessionUrlVar($listing);
					$layer = $urlModel->sessionUrlVar($layer);

					$catalogLayer = Mage::getSingleton('catalog/layer');

          // get name of child category
					$appliedFilters = $catalogLayer->getState()->getFilters();
					$CategoryName = $category->getName();
					$appliedFiltersCount = 0;
					$filter = array();
					foreach ($appliedFilters as $item) {
						$filter[] = $item->getFilter()->getRequestVar();
						if ($item->getFilter()->getRequestVar() == 'cat') {
							$CategoryName = $item->getLabel();
						}
						$appliedFiltersCount ++;
					}

					// link to clear all filters
					$clearUrl = Mage::helper('itactica_layerednavigation')->getClearFiltersUrl();
					$clearLink = '';
					if ($appliedFiltersCount > 0) {
						$clearLink = '<a href="'.$clearUrl.'" class="filter-reset">'. $this->__('Reset Filters') .'</a>';
					}

					// amount
					$lastPageNum = $catalogLayer->getProductCollection()->getLastPageNumber();
					$size = $catalogLayer->getProductCollection()->getSize();
					if ($lastPageNum > 1 && !Mage::helper('itactica_intenso')->isInfiniteScroll()) {
						$curPage = $catalogLayer->getProductCollection()->getCurPage();
						$count = $catalogLayer->getProductCollection()->count();
						$limit = $catalogLayer->getProductCollection()->getPageSize();
						$firstNum = $limit * ($curPage - 1) + 1;
						$lastNum = $limit * ($curPage - 1) + $count;
						$amount = $this->__('Items %s to %s of %s total', $firstNum, $lastNum, $size);
					} else {
						$amount = '<strong>'. $this->__('%s Item(s)', $size) . '</strong>';
					}

					// toolbar pager
					$toolbar= Mage::getBlockSingleton('catalog/product_list')->getToolbarBlock()->setTemplate('catalog/product/list/pager.phtml');
					$toolbar->setCollection($catalogLayer->getProductCollection());
					$pager = $this->getLayout()->createBlock('itactica_layerednavigation/catalog_product_list_pager', 'product_list_toolbar_pager');
					$toolbar->setChild('product_list_toolbar_pager', $pager);

					// orders
					$toolbarSingleton = Mage::getBlockSingleton('catalog/product_list_toolbar');
					$availableOrders = $toolbarSingleton->getAvailableOrders();
					$orders = '';
					foreach ($availableOrders as $_key=>$_order) {
						$asc = "";
						$desc = "";
						if($_key === "price"){
							$asc = $this->__($_order) . $this->__(' - Low to High');
							$desc = $this->__($_order) . $this->__(' - High to Low');
						} else if($_key === "name"){
							$asc = $this->__($_order) . $this->__(' - A to Z');
							$desc = $this->__($_order) . $this->__(' - Z to A');
						} else if($_key === "position"){
							$asc = $this->__($_order) .  $this->__(' asc.');
							$desc = $this->__($_order) . $this->__(' desc.');
						} else if($_key === "updated_at"){
							$desc = $this->__($_order) .  $this->__(' desc.');
						} else {
							$asc = $this->__($_order) .  $this->__(' asc.');
							$desc = $this->__($_order) . $this->__(' desc.');
						}
						
						if($asc){
							// ascending order
							$orders .= '<option value="'. $toolbarSingleton->getOrderUrl($_key, 'asc') .'"';
							// 3SD CODE Replace code with sorter.phtml
							// if ($toolbarSingleton->isOrderCurrent($_key)) {
							if ($toolbarSingleton->isOrderCurrent($_key) && $toolbarSingleton->getCurrentDirection() == 'asc') {
								$orders .= ' selected="selected">';
							} else {
								$orders .= '>';
							}
							$orders .= $asc . '</option>';
						}
						
						if($desc){
							// descending order
							$orders .= '<option value="'. $toolbarSingleton->getOrderUrl($_key, 'desc') .'"';
							// 3SD CODE Replace code with sorter.phtml
							// if ($toolbarSingleton->isOrderCurrent($_key)) {
							if ($toolbarSingleton->isOrderCurrent($_key) && $toolbarSingleton->getCurrentDirection() == 'desc') {
								$orders .= ' selected="selected">';
							} else {
								$orders .= '>';
							}
							$orders .= $desc . '</option>';
						}
					}

					// limits
					$availableLimit = $toolbarSingleton->getAvailableLimit();
					$limits = '';
					foreach ($availableLimit as $_key=>$_limit) {
						$limits .= '<option value="'. $toolbarSingleton->getLimitUrl($_key) .'"';
						if ($toolbarSingleton->isLimitCurrent($_key)) {
							$limits .= ' selected="selected">';
						} else {
							$limits .= '>';
						}
						$limits .= $this->__($_limit) . ' ' . $this->__('items per page') . '</option>';
					}

					// breadcrumbs
					if (Mage::getStoreConfig('intenso/catalog/remove_breadcrumbs', Mage::app()->getStore())) {
						$breadcrumbs = '';
					} else {
						$fullCategoryPath = $category->getUrlPath();
						$categoryPathWithoutSuffix = str_replace(Mage::helper('catalog/category')->getCategoryUrlSuffix(), '', $fullCategoryPath);
						$crumbs = explode('/', $categoryPathWithoutSuffix);
						$breadcrumbs = $this->getLayout()->createBlock('page/html_breadcrumbs');
						$breadcrumbs->addCrumb('home', array(
							'label' => Mage::helper('itactica_layerednavigation')->__('Home'),
							'title' => Mage::helper('itactica_layerednavigation')->__('Go to Home Page'),
							'link' => Mage::getBaseUrl()
						));
						foreach ($crumbs as $crumb) {
							if ($crumb === end($crumbs)) { // last element
								$breadcrumbs->addCrumb('category'.$category->getId(), array(
									'label' => $category->getName()
								));
							} else {
								$categoryHelper = Mage::getModel ('catalog/category')
									->getCollection ()
									->addAttributeToSelect ('name')
									->addAttributeToFilter ('url_key', $crumb)
									->getFirstItem ();
								$breadcrumbs->addCrumb('category'.$categoryHelper->getId(), array(
									'label' => $categoryHelper->getName(),
									'link' => $categoryHelper->getUrl()
								));
							}
						}
						$breadcrumbs = $breadcrumbs->toHtml();
					}

				// }

				// response
				if($this->getRequest()->getParam('slider') == false){
					$response = array();
					$response['topMenu'] = $topMenu;
				}


				if(($displayMode  == "PRODUCTS" || $displayMode == "PRODUCTS_AND_PAGE") || !empty($filter)){
					$response['listing'] = $listing;
					$response['layer'] = $layer;
					$response['categoryName'] = $CategoryName;
					$response['clearLink'] = $clearLink;
					$response['amount'] = $amount;
					$response['pager'] = $toolbar->toHtml();
					$response['orders'] = $orders;
					$response['limits'] = $limits;
					$response['breadcrumbs'] = $breadcrumbs;
				}



				if($displayMode  == "PAGE" || $displayMode == "PRODUCTS_AND_PAGE"){
					if($this->getRequest()->getParam('slider') == true){

						if($this->getRequest()->getParam('type') == "deals"){
						$deals = Mage::app()->getLayout()->createBlock('filterproducts/sale_home_list')
								->setShowPager(1)
                ->setPageNumber($this->getRequest()->getParam('sp'))
								->setProductsPerPage($this->getRequest()->getParam('productCount'))
								->setProductsCount(40)
								->setTemplate('filterproducts/list.phtml')->toHtml();
							$response['deals'] = $deals;
						} elseif($this->getRequest()->getParam('type') == "bestseller"){

							$bestSeller = Mage::app()->getLayout()->createBlock('filterproducts/bestsellers_home_list')
							->setShowPager(1)
              ->setPageNumber($this->getRequest()->getParam('sp'))
							->setProductsPerPage($this->getRequest()->getParam('productCount'))
							->setProductsCount(40)
							->setTemplate('filterproducts/list.phtml')->toHtml();
							$response['bestseller'] = $bestSeller;
						} elseif($this->getRequest()->getParam('type') == "latest"){

							$latest = Mage::app()->getLayout()->createBlock('filterproducts/latest_home_list')
							->setShowPager(1)
              ->setPageNumber($this->getRequest()->getParam('sp'))
							->setProductsPerPage($this->getRequest()->getParam('productCount'))
							->setProductsCount(40)
							->setTemplate('filterproducts/list.phtml')->toHtml();
							$response['latest'] = $latest;
						} elseif($this->getRequest()->getParam('type') == "mostviewed"){

							$mostviewed = Mage::app()->getLayout()->createBlock('filterproducts/mostviewed_home_list')
							->setShowPager(1)
              ->setPageNumber($this->getRequest()->getParam('sp'))
							->setProductsPerPage($this->getRequest()->getParam('productCount'))
							->setProductsCount(40)
							->setTemplate('filterproducts/list.phtml')->toHtml();
							$response['mostviewed'] = $mostviewed;
						} elseif($this->getRequest()->getParam('type') == "newarrival"){

							$newarrival = Mage::app()->getLayout()->createBlock('filterproducts/newproduct_home_list')
							->setShowPager(1)
              ->setPageNumber($this->getRequest()->getParam('sp'))
							->setProductsPerPage($this->getRequest()->getParam('productCount'))
							->setProductsCount(40)
							->setTemplate('filterproducts/list.phtml')->toHtml();
							$response['newarrival'] = $newarrival;

						} elseif($this->getRequest()->getParam('type') == "featured"){

							$featured = Mage::app()->getLayout()->createBlock('filterproducts/featured_home_list')
							->setShowPager(1)
              ->setPageNumber($this->getRequest()->getParam('sp'))
							->setProductsPerPage($this->getRequest()->getParam('productCount'))
							->setProductsCount(40)
							->setTemplate('filterproducts/list.phtml')->toHtml();
							$response['featured'] = $featured;
						}

					}
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
