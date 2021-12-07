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
 * @copyright  Copyright (c) 2006-2016 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog url model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Adminhtml_Model_Observer extends Mage_Core_Model_Abstract
{

	const XML_PATH_ATTRIBUTESET_WEBHOOK_URL		          = 'catalog/webhook/attributeset_url';
	
	public function attributeSetAction(Varien_Event_Observer $observer){
		$postData 		= array();
		$event 			= $observer->getEvent();
		$attributeSet 	= $event->getAttributeSet();
		$request 		= $event->getRequest();
		$action 		= $event->getAction();
		$storeId 		= Mage::app()->getStore()->getStoreId();
		$webhookURL 	= Mage::getStoreConfig(self::XML_PATH_ATTRIBUTESET_WEBHOOK_URL, $storeId);
		if($webhookURL){
			$isNewSet       = $request->getParam('gotoEdit', false) == '1';
			$adminSession 	= Mage::getSingleton('admin/session');
			$user 			= $adminSession->getUser();
			$postData['action'] = "edit";
			if($isNewSet){
				$postData['action'] = "new";
			}
			if($action == "delete"){
				$postData['action'] = "delete";
			}
			$postData['user'] = array(
				'userid'	=> $user->getUserId(),
				'username' => $user->getUsername(),
				'email' => $user->getEmail()
			);
			$zendDate = Zend_Date::now()->setLocale(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE , $storeId))->setTimezone(Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE , $storeId));
			$zendDate = date('Y-m-d H:i:s',(strtotime($zendDate)));
			$postData['attributeSet'] = array(
				"attribute_set_id" => $attributeSet->getData('attribute_set_id'),
				"entity_type_id" => $attributeSet->getData('entity_type_id'),
				"attribute_set_name" => $attributeSet->getData('attribute_set_name'),
				"sort_order" => $attributeSet->getData('sort_order')
			);
			if($action == "delete"){
				$postData['attributeSet'][0]['deleted_at'] = $zendDate;
			} else {
				$postData['attributeSet'][0]['updated_at'] = $zendDate;
			}
			$this->setAttributeSetWebhook($postData,$storeId);
		}
	}
	 /**
	 * post attribute set 
	 *
	 * @param string attribute set
	 * @return array
	 */
	public function setAttributeSetWebhook($data,$storeId = 0)
	{
		if(!empty($data)){
			$webhookURL = Mage::getStoreConfig(self::XML_PATH_ATTRIBUTESET_WEBHOOK_URL, $storeId);
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

    public function updateSection($observer)
    {

		try {
			$this->generateXml($observer);
			$this->generateHTML($observer);

			$this->setSitemapTime(Mage::getSingleton('core/date')->gmtDate('Y-m-d H:i:s'));
			$this->save();

			return $this;
		}
		catch (Exception $e) {
			$errors[] = $e->getMessage();
		}
    }


	/**
     * write XML file to disk
     * @access protected
     * @param $options
     * @return string
     */
    protected function generateXml($observer){

		$adminSession = Mage::getSingleton('adminhtml/session');

		try {
			$getSitemapFilename = "store.xml";
			$path = "/usr/share/nginx/tejar";
			$io = new Varien_Io_File();
			$io->setAllowCreateFolders(true);
			$io->open(array('path' => $path));

			if ($io->fileExists($getSitemapFilename) && !$io->isWriteable($getSitemapFilename)) {
				Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $getSitemapFilename, $path));
			}

			$io->streamOpen($getSitemapFilename);

			$io->streamWrite('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
			$io->streamWrite('<storeset>'. "\n");


			$date = Mage::getSingleton('core/date')->gmtDate('Y-m-d');

			/**
			 * Generate cms pages sitemap
			 */

			$collection = Mage::app()->getStores();
			foreach ($collection as $store) {
				$storeId = $store->getId();
				$name = (string)Mage::getStoreConfig('general/store_information/name', $storeId);
				$title = (string)Mage::getStoreConfig('design/head/default_title', $storeId);
				$title_404 = (string)Mage::getStoreConfig('design/errors/title_404', $storeId);
				$title_503 = (string)Mage::getStoreConfig('design/errors/title_503', $storeId);
				$description_404 = (string)Mage::getStoreConfig('design/errors/description_404', $storeId);
				$description_503 = (string)Mage::getStoreConfig('design/errors/description_503', $storeId);
				$title_suffix = (string)Mage::getStoreConfig('design/head/title_suffix', $storeId);
        $format = Mage::getStoreConfig('design/footer/copyright', $storeId);
        $year = Mage::getModel('core/date')->date('Y');
        $copyright = sprintf($format, $year, $name);
        $copyright = (string)$copyright;
				$url =  $baseUrl = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);
				$xml = sprintf(
					'	<store>'. "\n" . '		<url>%s</url>' . "\n" .'		<name>%s</name>' . "\n" .'		<title>%s</title>' . "\n" . '		<title_suffix>%s</title_suffix>' . "\n" . '		<copyright>%s</copyright>' . "\n" . '		<title_404>%s</title_404>' . "\n" .'		<description_404>%s</description_404>' . "\n" .'		<title_503>%s</title_503>' . "\n" .'		<description_503>%s</description_503>' . "\n" . '	</store>'. "\n",
					htmlspecialchars($url),
					$name,
					htmlspecialchars($title),
					htmlspecialchars($title_suffix),
					htmlspecialchars($copyright),
					htmlspecialchars($title_404),
					htmlspecialchars($description_404),
					htmlspecialchars($title_503),
					htmlspecialchars($description_503)
				);
				$io->streamWrite($xml);
			}
			unset($collection);

			$io->streamWrite('</storeset>');
			$io->streamClose();


			$adminSession->addSuccess(Mage::helper('sitemap')->__('The "%s" has been generated.', $path . '/' . $getSitemapFilename));

		} catch (Exception $e) {

			$adminSession->addError(Mage::helper('sitemap')->__('Permission denied for write to "%s".', $path . '/' . $getSitemapFilename));
		}
	}


	/**
     * write HTML file to disk
     * @access protected
     * @param $options
     * @return string
     */
	protected function generateHTML($observer){

		$adminSession = Mage::getSingleton('adminhtml/session');

		$model = Mage::getModel('cms/page');
		$path = "/usr/share/nginx/tejar";
    $filename = "404";

		$collection = Mage::app()->getStores();
			foreach ($collection as $store) {
				try {
					$storeCode = $store->getCode();
					$storeId = $store->getId();
					// $path = "/usr/share/nginx/tejar/";
					$getSitemapFilename = $filename . $storeCode . ".html";
					if($storeCode === "default"){
						$getSitemapFilename = $filename . ".html";
					}


					$title_suffix = (string)Mage::getStoreConfig('design/head/title_suffix', $storeId);
					$url = Mage::app()->getStore($storeId)->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

					$io = new Varien_Io_File();
					$io->setAllowCreateFolders(true);
					$io->open(array('path' => $path));

					if ($io->fileExists($getSitemapFilename) && !$io->isWriteable($getSitemapFilename)) {
						Mage::throwException(Mage::helper('sitemap')->__('File "%s" cannot be saved. Please, make sure the directory "%s" is writeable by web server.', $getSitemapFilename, $path));
					}

					$cmsCollection = $model->getCollection()
					->addFieldToSelect('title')
					->addFieldToSelect('identifier')
					->addFieldToSelect('content')
					->addFieldToFilter('identifier', "no-route")
					->getLastItem();

					$title = sprintf(
						'<title>%s</title>' . "\n",
						htmlspecialchars($cmsCollection->getTitle()) .' '. htmlspecialchars($title_suffix));

					$io->streamOpen($getSitemapFilename);
					$io->streamWrite('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">' . "\n");
					$io->streamWrite('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">'. "\n");
					$io->streamWrite('<head>'. "\n");
					$io->streamWrite($title);
					$io->streamWrite('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'. "\n");
					$io->streamWrite('<meta name="viewport" content="width=device-width, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, initial-scale=1.0">'. "\n");
					$io->streamWrite('</head>'. "\n");
					$io->streamWrite('<body>'. "\n");
					$io->streamWrite('<iframe id="myIFrame" src="/no-route" style="width: 100%;height: 100%;overflow: hidden;position: absolute;left: 0;right: 0;top: 0px;border: none;"></iframe>');
					$io->streamWrite('<script type="text/javascript">window.onload = function(){ var anchor = window.frames["myIFrame"].contentDocument.getElementsByTagName("a"),  i; for (i = 0; i < anchor.length; i++) { anchor[i].target = "_parent"; } }</script>');
					$io->streamWrite('</body>'. "\n");
					$io->streamWrite('</html>'. "\n");

					$date = Mage::getSingleton('core/date')->gmtDate('Y-m-d');

					$io->streamClose();

					$adminSession->addSuccess(
                    Mage::helper('sitemap')->__('The "%s" has been generated.', $path . '/' . $getSitemapFilename));

				} catch (Exception $e) {
					$adminSession->addError(Mage::helper('sitemap')->__('Permission denied for write to "%s".', $path . '/' . $getSitemapFilename));
				}
			}


	}

	public function catalogProductAttributeEditPrepareForm($observer){
		$attributes = $observer->getAttribute();
		$form = $observer->getForm();
		$id = Mage::app()->getRequest()->getParam('attribute_id');
		if(($this->_isAllowedAction('configuration') == false && $id != null)){
			$form->getElement('is_unique')->setDisabled(1);
			$form->getElement('frontend_class')->setDisabled(1);
			$form->getElement('frontend_class')->setReadOnly(1);
			$form->getElement('is_global')->setDisabled(1);
			$form->getElement('is_configurable')->setDisabled(1);
			$form->getElement('is_required')->setDisabled(1);
			$form->getElement('apply_to')->setDisabled(1);
			$form->getElement('default_value_text')->setDisabled(1);
			$form->getElement('default_value_textarea')->setDisabled(1);
			$form->getElement('default_value_yesno')->setDisabled(1);
			$form->getElement('default_value_date')->setDisabled(1);
		}
	}

	public function adminSalesOrderPlaceAfter($observer)
    {
		$event = $observer->getEvent();
		$order = $event->getOrder();
		$customer = $order->getCustomer();
		if($customerId = $customer->getId()){
			$bind    = array('entity_id' => (int)$customerId);
			$resource = Mage::getModel('core/resource');
			$readConnection = $resource->getConnection('core_read');
			$select  = $readConnection->select()->from($readConnection->getTableName('customer_entity'), 'entity_id')->where('entity_id = :entity_id')->limit(1);
			$result = $readConnection->fetchOne($select,$bind);
			if(!$result){
				if($customer->getConfirmation()){
					$customer->sendNewAccountEmail('custom_confirmation', '', $customer->getStoreId());
				}
			}
		}
    }

	protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/attributes/attributes/' . $action);
    }

}
