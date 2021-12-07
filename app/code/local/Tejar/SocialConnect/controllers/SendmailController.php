<?php

class Tejar_SocialConnect_SendmailController extends Mage_Core_Controller_Front_Action
{
	/*
	* sendmailAction - to send product link to a friend using Ajax Call
	*
	*/
	public function indexAction()
    {
		
		$message     = null;
		$messageType = null;
		//echo $this->getRequest()->getParam('form_key');die;
        if (!$this->_validateFormKey()) {
			
            //return $this->_redirect('*/*/send', array('_current' => true));
            return false;
        }

        $product    = $this->_initProduct();
        $model      = $this->_initSendToFriendModel();
        $data       = $this->getRequest()->getPost();

        if (!$product || !$data) {
            $this->_forward('noRoute');
            return;
        }

        $categoryId = $this->getRequest()->getParam('cat_id', null);
        if ($categoryId) {
            $category = Mage::getModel('catalog/category')
                ->load($categoryId);
            $product->setCategory($category);
            Mage::register('current_category', $category);
        }

        $model->setSender($this->getRequest()->getPost('sender'));
        $model->setRecipients($this->getRequest()->getPost('recipients'));
        $model->setProduct($product);

        try {
            $validate = $model->validate();
            if ($validate === true) {
                $model->send();
				$message = $this->__('The link to a friend was sent.');
				$messageType = "success";
               // Mage::getSingleton('catalog/session')->addSuccess($this->__('The link to a friend was sent.'));
                //$this->_redirectSuccess($product->getProductUrl());
               // return;
            }
            else {
                if (is_array($validate)) {
                    foreach ($validate as $errorMessage) {
                        Mage::getSingleton('catalog/session')->addError($errorMessage);
                    }
                }
                else {
					$message = $this->__('There were some problems with the data.');
					$messageType = "error";
                   // Mage::getSingleton('catalog/session')->addError($this->__('There were some problems with the data.'));
                }
            }
        }
        catch (Mage_Core_Exception $e) {
           // Mage::getSingleton('catalog/session')->addError($e->getMessage());
			$message = $e->getMessage();
			$messageType = "error";
        }
        catch (Exception $e) {
			$message = $this->__('Some emails were not sent.');
			$messageType = "error";
            //Mage::getSingleton('catalog/session')->addException($e, $this->__('Some emails were not sent.'));
        }
		//echo $message;die;
		$response = array(
                    'response' => $message,
					'responseType' => $messageType
                );
		echo json_encode($response);exit;
        // save form data
        //Mage::getSingleton('catalog/session')->setSendfriendFormData($data);

        //$this->_redirectError(Mage::getURL('*/*/send', array('_current' => true)));
    }
	
	 /**
     * Initialize Product Instance
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _initProduct()
    {
        $productId  = (int)$this->getRequest()->getParam('id');
		
        if (!$productId) {
            return false;
        }
        $product = Mage::getModel('catalog/product')
            ->load($productId);
        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            return false;
        }

        Mage::register('product', $product);
        return $product;
    }
	
	 /**
     * Initialize send friend model
     *
     * @return Mage_Sendfriend_Model_Sendfriend
     */
    protected function _initSendToFriendModel()
    {
        $model  = Mage::getModel('sendfriend/sendfriend');
        $model->setRemoteAddr(Mage::helper('core/http')->getRemoteAddr(true));
        $model->setCookie(Mage::app()->getCookie());
        $model->setWebsiteId(Mage::app()->getStore()->getWebsiteId());

        Mage::register('send_to_friend_model', $model);

        return $model;
    }
}