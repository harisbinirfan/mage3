<?php
/**
 * Intenso Premium Theme
 *
 * @category    Tejar
 * @package     Tejar_Contacts
 * @author      zeeshan <zeeshan_zeeshan123@gmail.com>
 */

require_once 'Mage/Contacts/controllers/IndexController.php';
class Tejar_Contacts_IndexController extends Mage_Contacts_IndexController{

	function indexAction(){

		//--- Redirect to 404 if 'brand' is included within the URL: to avoid SEO issues ;
		$currURL =  Mage::helper('core/url')->getCurrentUrl();

		if(stristr($currURL, '/index')){
			$this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
			$this->getResponse()->setHeader('Status','404 File not found');
			$this->_forward('defaultNoRoute');
			//Mage::app()->getResponse()
			//->setRedirect(substr($currURL,0,strlen($currURL)-6), 301)
			//->sendResponse();
		}else{
			$this->loadLayout();
            $contactCmsId =  Mage::getModel('cms/page')->checkIdentifier('contact',Mage::app()->getStore()->getStoreId());
			$contactCms =  Mage::getModel('cms/page')->load($contactCmsId);
			$blockHead = $this->getLayout()->getBlock('head');
			if($title = $contactCms->getTitle()){
				$blockHead->setTitle($title);
			}
			if($meta_description = $contactCms->getMetaDescription()){
				$blockHead->setDescription($meta_description);
			}
			if($keywords = $contactCms->getMetaKeywords()){
				$blockHead->setkeywords($keywords);
			}
			$block = $this->getLayout()->getBlock('contactForm');
			$childBlock = $this->getLayout()->createBlock('core/template')->setContentHeading($contactCms->getContentHeading())->setTemplate('cms/content_heading.phtml');
            $block->setChild('page_content_heading',$childBlock);
            $content = $contactCms->getContent();
			$helper = Mage::helper('cms');
			$processor = $helper->getPageTemplateProcessor();
			$html = $processor->filter($content);
			$childBlock2 = $this->getLayout()->createBlock('core/template')->setText($html)->setTemplate('cms/widget/static_block/default.phtml');
			$block->setChild('page_content',$childBlock2);
			$this->getLayout()->getBlock('contactForm')
				->setFormAction( Mage::getUrl('*/*/post', array('_secure' => $this->getRequest()->isSecure())) );

			$this->_initLayoutMessages('customer/session');
			$this->_initLayoutMessages('catalog/session');
			$this->renderLayout();
		}
	}

	public function postAction()
    {
        $post = $this->getRequest()->getPost();
        if ( $post ) {
            $translate = Mage::getSingleton('core/translate');
            /* @var $translate Mage_Core_Model_Translate */
            $translate->setTranslateInline(false);
            try {
                $postObject = new Varien_Object();
                $postObject->setData($post);

                $error = false;

                if (!Zend_Validate::is(trim($post['name']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['comment']) , 'NotEmpty')) {
                    $error = true;
                }

                if (!Zend_Validate::is(trim($post['email']), 'EmailAddress')) {
                    $error = true;
                }

                if (Zend_Validate::is(trim($post['hideit']), 'NotEmpty')) {
                    $error = true;
                }

				$errorMessage = "";
				if(isset($post['telephone'])){
					if(!preg_match('/^[0-9-+\s()]*$/', $post['telephone'])){
						$error = true;
						$errorMessage = Mage::helper('onestepcheckout')->__('Please enter a valid Number');
					}	
					$phoneValidator = new Giggsey_Libphonenumber();
					$phoneUtil = $phoneValidator->call($post['telephone']);
                    if(!$phoneValidator->getIsValidation()){
						$error = true;
						$errorMessage = $phoneUtil->getMessage();
					} else if($phoneValidator->getIsValidation()){
						$postObject['telephone'] = $phoneUtil->getRawInput();
					}
				}
				if($errorMessage){
                    Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__($errorMessage));
                    $this->_redirect("contact");
				}    

                if ($error) {
                    throw new Exception();
                }
                $mailTemplate = Mage::getModel('core/email_template');
                /* @var $mailTemplate Mage_Core_Model_Email_Template */
                $mailTemplate->setDesignConfig(array('area' => 'frontend'))
                    ->setReplyTo($post['email'])
                    ->sendTransactional(
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER),
                        Mage::getStoreConfig(self::XML_PATH_EMAIL_RECIPIENT),
                        null,
                        array('data' => $postObject)
                    );

                if (!$mailTemplate->getSentSuccess()) {
                    throw new Exception();
                }

                if ($mailTemplate->getSentSuccess()) {
                    $webhookUrl = Mage::getStoreConfig('contacts/contacts/webhook_url');
				if($webhookUrl){
					$data = $this->getRequest()->getPost();
					$data['store_id'] = Mage::app()->getStore()->getId();
					$data = json_encode($data);
					$ch = curl_init($webhookUrl);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Content-Type: application/json')
					);
					curl_exec($ch);
					curl_close($ch);
				}
                }

                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addSuccess(Mage::helper('contacts')->__('Your inquiry was submitted and will be responded to as soon as possible. Thank you for contacting us.'));
                //$this->_redirect('*/*/');
				//--- Redirect to contact instead of contact/index
				$this->_redirect("contact");
                return;
            } catch (Exception $e) {
                $translate->setTranslateInline(true);

                Mage::getSingleton('customer/session')->addError(Mage::helper('contacts')->__('Unable to submit your request. Please, try again later'));
                $this->_redirect('contact');
                return;
            }

        } else {
            $this->_redirect('*/*/');
        }
    }


}

?>
