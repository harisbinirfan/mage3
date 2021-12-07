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
 * Catalog category controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
 
require_once 'Mage/Adminhtml/controllers/Catalog/CategoryController.php';
class Tejar_Adminhtml_Catalog_CategoryController extends Mage_Adminhtml_Catalog_CategoryController
{
   
	const XML_PATH_CATEGORY_WEBHOOK_URL		          = 'catalog/webhook/category_url';

    /**
     * Move category action
     */
    public function moveAction()
    {
		if($this->_isAllowedAction('move')){
			$category = $this->_initCategory();
			if (!$category) {
				$this->getResponse()->setBody(Mage::helper('catalog')->__('Category move error'));
				return;
			}
			/**
			 * New parent category identifier
			 */
			$parentNodeId   = $this->getRequest()->getPost('pid', false);
			/**
			 * Category id after which we have put our category
			 */
			$prevNodeId     = $this->getRequest()->getPost('aid', false);
			$category->setData('save_rewrites_history', Mage::helper('catalog')->shouldSaveUrlRewritesHistory());
			try {
				$category->move($parentNodeId, $prevNodeId);
				$this->getResponse()->setBody("SUCCESS");
			}
			catch (Mage_Core_Exception $e) {
				$this->getResponse()->setBody($e->getMessage());
			}
			catch (Exception $e){
				$this->getResponse()->setBody(Mage::helper('catalog')->__('Category move error %s', $e));
				Mage::logException($e);
			}
			
			
		} else {
			
			$this->_forward('denied');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return $this;
			
		}

    }

	// 		/**
    //  * Category save
    //  */
    // public function saveAction()
    // {
    //     if (!$category = $this->_initCategory()) {
    //         return;
    //     }
    //     $storeId = $this->getRequest()->getParam('store');
    //     $refreshTree = 'false';
    //     if ($data = $this->getRequest()->getPost()) {
    //         $category->addData($data['general']);
    //         if (!$category->getId()) {
    //             $parentId = $this->getRequest()->getParam('parent');
    //             if (!$parentId) {
    //                 if ($storeId) {
    //                     $parentId = Mage::app()->getStore($storeId)->getRootCategoryId();
    //                 }
    //                 else {
    //                     $parentId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
    //                 }
    //             }
    //             $parentCategory = Mage::getModel('catalog/category')->load($parentId);
    //             $category->setPath($parentCategory->getPath());
    //         }
    //         /**
    //          * Check "Use Default Value" checkboxes values
    //          */
    //         if ($useDefaults = $this->getRequest()->getPost('use_default')) {
    //             foreach ($useDefaults as $attributeCode) {
    //                 $category->setData($attributeCode, false);
    //             }
    //         }
    //         /**
    //          * Process "Use Config Settings" checkboxes
    //          */
    //         if ($useConfig = $this->getRequest()->getPost('use_config')) {
    //             foreach ($useConfig as $attributeCode) {
    //                 $category->setData($attributeCode, null);
    //             }
    //         }
    //         /**
    //          * Create Permanent Redirect for old URL key
    //          */
    //         if ($category->getId() && isset($data['general']['url_key_create_redirect']))
    //         // && $category->getOrigData('url_key') != $category->getData('url_key')
    //         {
    //             $category->setData('save_rewrites_history', (bool)$data['general']['url_key_create_redirect']);
    //         }
    //         $category->setAttributeSetId($category->getDefaultAttributeSetId());
    //         if (isset($data['category_products']) &&
    //             !$category->getProductsReadonly()
    //         ) {
    //             $products = Mage::helper('core/string')->parseQueryStr($data['category_products']);
    //             $category->setPostedProducts($products);
    //         }
    //         Mage::dispatchEvent('catalog_category_prepare_save', array(
    //             'category' => $category,
    //             'request' => $this->getRequest()
    //         ));
    //         /**
    //          * Proceed with $_POST['use_config']
    //          * set into category model for proccessing through validation
    //          */
    //         $category->setData("use_post_data_config", $this->getRequest()->getPost('use_config'));
    //         try {
    //             $validate = $category->validate();
    //             if ($validate !== true) {
    //                 foreach ($validate as $code => $error) {
    //                     if ($error === true) {
    //                         Mage::throwException(Mage::helper('catalog')->__('Attribute "%s" is required.', $category->getResource()->getAttribute($code)->getFrontend()->getLabel()));
    //                     }
    //                     else {
    //                         Mage::throwException($error);
    //                     }
    //                 }
    //             }
    //             /**
    //              * Unset $_POST['use_config'] before save
    //              */
    //             $category->unsetData('use_post_data_config');
    //             $category->save();
	// 			$storeId = $category->getStore()->getStoreId();
	// 			$webhookURL = Mage::getStoreConfig(self::XML_PATH_CATEGORY_WEBHOOK_URL, $storeId);
	// 			if($webhookURL){
	// 				$adminSession = Mage::getSingleton('admin/session');
	// 				$user = $adminSession->getUser();
	// 				$request = $this->getRequest();
	// 				$data = array();
	// 				$data['action'] = "new";
	// 				if($request->getParam('id')){
	// 					$data['action'] = "edit";
	// 				}
	// 				$data['user'] = array(
	// 					'userid'	=> $user->getUserId(),
	// 					'username' => $user->getUsername(),
	// 					'email' => $user->getEmail()
	// 				);
	// 				$data['category'] = array();
	// 				$mCategory = Mage::getModel('catalog/category')->setStoreId($category->getStore()->getStoreId())->load($category->getId());
	// 				$mDataCollection = array();
	// 				foreach($mCategory->getData() as $mKey => $mData){
	// 						$mDataCollection[$mKey] = $mData;
	// 				}
	// 				$dataCollection = array();
	// 				$images = array("image","thumbnail","custom_thumbnail");
	// 				foreach($category->getData() as $key => $udata){
	// 					if(is_array($udata) && array_key_exists($key,$mDataCollection) && in_array($key,$images)){
	// 						$dataCollection[$key] = $udata['value'];
	// 					} else if(array_key_exists($key,$mDataCollection)){
	// 						$dataCollection[$key] = $udata;
	// 					}
	// 				}
	// 				$result = array_diff_assoc($dataCollection,$mDataCollection);
	// 				$origDataDiff = array();
	// 				foreach($result as $k => $r){
	// 					$origDataDiff[$k] = $mDataCollection[$k];
	// 				}
	// 				$data['category']['before_update'] = $origDataDiff;
	// 				$data['category']['updated'] = $category->getData();
	// 				$defaultCategory = Mage::getModel('catalog/category')->setStoreId(0)->load($category->getId());
	// 				foreach($data['category']['updated'] as $kd => $dt){
	// 					if($dt == false){
	// 						if($defaultCategory->hasData($kd)){
	// 							$data['category']['updated'][$kd] = $defaultCategory->getData($kd);
	// 						}
	// 					}
	// 				}
	// 				if(count($category->getData('posted_products'))){
	// 					$data['category']['updated']['product_count'] = count($category->getData('posted_products'));
	// 				}			
	// 				if(count($category->getData('posted_products')) != $mCategory->getProductCount()){
	// 					$data['category']['before_update']['product_count'] = $mCategory->getProductCount();
	// 				}

	// 				$data['category']['website_status'] = array();
	// 				$storeCollection	 =	Mage::app()->getStores();
	// 				foreach($storeCollection as $store){
	// 					$storeId			=	$store->getStoreId();
	// 					$storeCode			=	$store->getCode();
	// 					$storeCategory		=	Mage::getModel('catalog/category')->setStoreId($storeId)->load($category->getId());
	// 					$data['category']['website_status'][$storeId] = $storeCategory->getIsActive();
	// 				}

	// 				$this->setCategoryWebhook($data,$category->getStore()->getStoreId());
	// 			}
    //             Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('catalog')->__('The category has been saved.'));
    //             $refreshTree = 'true';
    //         }
    //         catch (Exception $e){
    //             $this->_getSession()->addError($e->getMessage())
    //                 ->setCategoryData($data);
    //             $refreshTree = 'false';
    //         }
    //     }
    //     $url = $this->getUrl('*/*/edit', array('_current' => true, 'id' => $category->getId()));
    //     $this->getResponse()->setBody(
    //         '<script type="text/javascript">parent.updateContent("' . $url . '", {}, '.$refreshTree.');</script>'
    //     );
    // }

			/**
     * Category save
     */
    public function saveAction()
    {
        if (!$category = $this->_initCategory()) {
            return;
        }
        $storeId = $this->getRequest()->getParam('store');
        $refreshTree = 'false';
        if ($data = $this->getRequest()->getPost()) {
            $category->addData($data['general']);
            if (!$category->getId()) {
                $parentId = $this->getRequest()->getParam('parent');
                if (!$parentId) {
                    if ($storeId) {
                        $parentId = Mage::app()->getStore($storeId)->getRootCategoryId();
                    }
                    else {
                        $parentId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
                    }
                }
                $parentCategory = Mage::getModel('catalog/category')->load($parentId);
                $category->setPath($parentCategory->getPath());
            }
            /**
             * Check "Use Default Value" checkboxes values
             */
            if ($useDefaults = $this->getRequest()->getPost('use_default')) {
                foreach ($useDefaults as $attributeCode) {
                    $category->setData($attributeCode, false);
                }
            }
            /**
             * Process "Use Config Settings" checkboxes
             */
            if ($useConfig = $this->getRequest()->getPost('use_config')) {
                foreach ($useConfig as $attributeCode) {
                    $category->setData($attributeCode, null);
                }
            }
            /**
             * Create Permanent Redirect for old URL key
             */
            if ($category->getId() && isset($data['general']['url_key_create_redirect']))
            // && $category->getOrigData('url_key') != $category->getData('url_key')
            {
                $category->setData('save_rewrites_history', (bool)$data['general']['url_key_create_redirect']);
            }
            $category->setAttributeSetId($category->getDefaultAttributeSetId());
            if (isset($data['category_products']) &&
                !$category->getProductsReadonly()
            ) {
                $products = Mage::helper('core/string')->parseQueryStr($data['category_products']);
                $category->setPostedProducts($products);
            }
            Mage::dispatchEvent('catalog_category_prepare_save', array(
                'category' => $category,
                'request' => $this->getRequest()
            ));
            /**
             * Proceed with $_POST['use_config']
             * set into category model for proccessing through validation
             */
            $category->setData("use_post_data_config", $this->getRequest()->getPost('use_config'));
            try {
                $validate = $category->validate();
                if ($validate !== true) {
                    foreach ($validate as $code => $error) {
                        if ($error === true) {
                            Mage::throwException(Mage::helper('catalog')->__('Attribute "%s" is required.', $category->getResource()->getAttribute($code)->getFrontend()->getLabel()));
                        }
                        else {
                            Mage::throwException($error);
                        }
                    }
                }
                /**
                 * Unset $_POST['use_config'] before save
                 */
                $category->unsetData('use_post_data_config');
                $category->save();
				$storeId = $category->getStore()->getStoreId();
				$webhookURL = Mage::getStoreConfig(self::XML_PATH_CATEGORY_WEBHOOK_URL, $storeId);
				if($webhookURL){
					$adminSession = Mage::getSingleton('admin/session');
					$user = $adminSession->getUser();
					$request = $this->getRequest();
					$postData = array();
					$postData['action'] = "new";
					if($request->getParam('id')){
						$postData['action'] = "edit";
					}
					$postData['user'] = array(
						'userid'	=> $user->getUserId(),
						'username' => $user->getUsername(),
						'email' => $user->getEmail()
					);
					$postData['category'] = array();
					if($request->getParam('id')){
						$mCategory = Mage::getModel('catalog/category')->setStoreId($category->getStore()->getStoreId())->load($category->getId());
						$mDataCollection = array();
						foreach($mCategory->getData() as $mKey => $mData){
								$mDataCollection[$mKey] = $mData;
						}
						$dataCollection = array();
						$images = array("image","thumbnail","custom_thumbnail");
						foreach($category->getData() as $key => $udata){
							if(is_array($udata) && array_key_exists($key,$mDataCollection) && in_array($key,$images)){
								$dataCollection[$key] = $udata['value'];
							} else if(array_key_exists($key,$mDataCollection)){
								$dataCollection[$key] = $udata;
							}
						}
						$result = array_diff_assoc($dataCollection,$mDataCollection);
						$origDataDiff = array();
						foreach($result as $k => $r){
							$origDataDiff[$k] = $mDataCollection[$k];
						}
						$postData['category']['before_update'] = $origDataDiff;
						$postData['category']['updated'] = $category->getData();
						$defaultCategory = Mage::getModel('catalog/category')->setStoreId(0)->load($category->getId());
						foreach($postData['category']['updated'] as $kd => $dt){
							if($dt == false){
								if($defaultCategory->hasData($kd)){
									$postData['category']['updated'][$kd] = $defaultCategory->getData($kd);
								}
							}
						}
						if(count($category->getData('posted_products'))){
							$postData['category']['updated']['product_count'] = count($category->getData('posted_products'));
						}			
						if(count($category->getData('posted_products')) != $mCategory->getProductCount()){
							$postData['category']['before_update']['product_count'] = $mCategory->getProductCount();
						}

						$postData['category']['website_status'] = array();
						$storeCollection	 =	Mage::app()->getStores();
						foreach($storeCollection as $store){
							$storeId			=	$store->getStoreId();
							$storeCode			=	$store->getCode();
							$storeCategory		=	Mage::getModel('catalog/category')->setStoreId($storeId)->load($category->getId());
							$postData['category']['website_status'][$storeId] = $storeCategory->getIsActive();
						}
						
					} else {
						$postData['category'] = $category->getData();
					}
					echo "<script>console.log('".json_encode($postData['category'])."');</script>";

					$this->setCategoryWebhook($postData,$category->getStore()->getStoreId());
				}
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('catalog')->__('The category has been saved.'));
                $refreshTree = 'true';
            }
            catch (Exception $e){
                $this->_getSession()->addError($e->getMessage())
                    ->setCategoryData($data);
                $refreshTree = 'false';
            }
        }
        $url = $this->getUrl('*/*/edit', array('_current' => true, 'id' => $category->getId()));
        $this->getResponse()->setBody(
            '<script type="text/javascript">parent.updateContent("' . $url . '", {}, '.$refreshTree.');</script>'
        );
    }

	 /**
	 * post product 
	 *
	 * @param string product
	 * @return array
	 */
	public function setCategoryWebhook($data,$storeId = 0)
	{
		if(!empty($data)){
			$webhookURL = Mage::getStoreConfig(self::XML_PATH_CATEGORY_WEBHOOK_URL, $storeId);
			if($webhookURL){
			$data = json_encode($data);
			$ch = curl_init($webhookURL);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data))
			);
			$response = curl_exec($ch);
			$err = curl_error($ch);
			curl_close($ch);
		}
		}
	}

    /**
     * Delete category action
     */
    public function deleteAction()
    {
		if($this->_isAllowedAction('delete')){
			if ($id = (int) $this->getRequest()->getParam('id')) {
				try {
					$category = Mage::getModel('catalog/category')->load($id);
					Mage::dispatchEvent('catalog_controller_category_delete', array('category'=>$category));

					Mage::getSingleton('admin/session')->setDeletedPath($category->getPath());

					$category->delete();
					Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('catalog')->__('The category has been deleted.'));
				}
				catch (Mage_Core_Exception $e){
					Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
					$this->getResponse()->setRedirect($this->getUrl('*/*/edit', array('_current'=>true)));
					return;
				}
				catch (Exception $e){
					Mage::getSingleton('adminhtml/session')->addError(Mage::helper('catalog')->__('An error occurred while trying to delete the category.'));
					$this->getResponse()->setRedirect($this->getUrl('*/*/edit', array('_current'=>true)));
					return;
				}
			}
			$this->getResponse()->setRedirect($this->getUrl('*/*/', array('_current'=>true, 'id'=>null)));
			
		} else {
			
			$this->_forward('denied');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return $this;
			
		}
    }

    

    /**
     * Check if admin has permissions to visit related pages
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/categories');
    }
	
	protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/categories/' . $action);
    }		
}
