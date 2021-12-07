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

class Tejar_Contacts_Model_Observer extends Mage_Captcha_Model_Observer {

	/**
		 * Returns captcha helper
		 *
		 * @return Mage_Captcha_Helper_Data
		 */
			protected function _getCustomerHelper()
			{
				if (empty($this->_customerHelper)) {
					$this->_customerHelper = Mage::helper('customer');
				}
				return $this->_customerHelper;
			}

	/**
	* Returns captcha helper
	*
	* @return Mage_Captcha_Helper_Data
	*/
    protected function _getHelper()
    {
        if (empty($this->_helper)) {
            $this->_helper = Mage::helper('tejar_captcha');
        }

        return $this->_helper;
    }


	public function checkContactPage($observer)
	{
		$controller = $observer->getControllerAction();
		$formId = 'contact_page_captcha';
		$captchaModel = Mage::helper('captcha')->getCaptcha($formId);
		if ($captchaModel->isRequired()) {
			$controller = $observer->getControllerAction();
			if (!$captchaModel->isCorrect($this->_getCaptchaString($controller->getRequest(), $formId))) {
				if($this->_getHelper() && $this->_getHelper()->getConfigNode('recaptcha_enable')){
					Mage::getSingleton('customer/session')->addError(Mage::helper('captcha')->__('You are not a human.'));
				} else {
					Mage::getSingleton('customer/session')->addError(Mage::helper('captcha')->__('Incorrect CAPTCHA.'));
				}
				$controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
				$controller->getResponse()->setRedirect(Mage::getUrl('*/'));
			}
		}
		$helper = $this->_getCustomerHelper();
				if($helper->getZeroBounce("contact_page")){
					$email = $controller->getRequest()->getPost('email');
					$data = $helper->getValidateZeroBounce($email);
					if(isset($data['status']) &&  $data['zerobounce_result']){
						$emailData = array("Form"=> "Contact Page","email" => $email, "error" => $data['sub_status']);
						Mage::getSingleton('customer/session')->addError(Mage::helper('captcha')->__("There was a problem with the email address: " . $data['sub_status']));
						$controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
						$controller->getResponse()->setRedirect(Mage::getUrl('*/'));
					}
				}

		return $this;
	}


	/**
     * Send the auto reply email after user submits successful contact email
     *
     * @param Varien_Event_Observer $observer Default event observer object
     */
    public function sendAutoReplyEmail(Varien_Event_Observer $observer)
    {
        /** @var Tejar_Contacts_Helper_Data $helper */
        $helper = Mage::helper('tejar_contacts');

        if (!$helper->isEnabled()) {
            return;
        }
        // Check if last added message is either empty or is not an error
        // type - to find out if contact email was submitted successfully
        // and whether we should proceed with sending auto reply
        /** @var Mage_Core_Model_Message_Collection $messages */
        $messages = Mage::getSingleton('customer/session')->getMessages();
        /** @var Mage_Core_Model_Message_Abstract $lastMessage */
        $lastMessage = $messages->getLastAddedMessage();
        $sendAutoReplyEmail = !$lastMessage
            || $lastMessage->getType() !== Mage_Core_Model_Message::ERROR;
        if (!$sendAutoReplyEmail) {
            return;
        }
        // Collect post data into Varien_Object and pass the data into an
        // email template. Make sure email is translated to current store
        // view language. Send auto reply email.
        /** @var Mage_Contacts_IndexController $controller */
        $controller = $observer->getEvent()->getControllerAction();
        $postData = new Varien_Object();
        $postData->setData($controller->getRequest()->getPost());
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);
        /* @var $emailTemplate Mage_Core_Model_Email_Template */
        $emailTemplate = Mage::getModel('core/email_template');
        try {
            $emailTemplate->setDesignConfig(array('area' => 'frontend'))
                ->sendTransactional(
                    $helper->getEmailTemplate(),
                    $helper->getEmailSender(),
                    $postData->getData('email'),
                    null,
                    array('data' => $postData)
                );
            $translate->setTranslateInline(true);
        } catch (Exception $exception) {
            // If something failed while sending auto reply email, there is
            // nothing we can do - this customer will simply not receive it
            $translate->setTranslateInline(true);
        }
    }

    public function googleCalendarLoad($observer){
		$collection = array();
		foreach(Mage::app()->getStores() as $store){
			$calendarApi = Mage::getStoreConfig('general/locale/calendar_api',$store->getStoreId());
			$ids = array(); 
			if($calendarApi){
				$curl = curl_init();
				curl_setopt_array($curl, array(
				  CURLOPT_URL => $calendarApi,
				  CURLOPT_RETURNTRANSFER => true,
				  CURLOPT_ENCODING => "",
				  CURLOPT_MAXREDIRS => 10,
				  CURLOPT_TIMEOUT => 30,
				  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  CURLOPT_CUSTOMREQUEST => "GET",
				  CURLOPT_HTTPHEADER => array(
					'Content-Type: application/json',
					'accept: application/json'
				  ),
				));
				$response = curl_exec($curl);
				$err = curl_error($curl);
				curl_close($curl);
				if ($err) {
					Mage::log($err, null, 'calendar.log');
				} else {
					$array = array();
					$data = Mage::helper('core')->jsonDecode($response);
					foreach($data['items'] as $key => $item){
						if($item['status'] == "confirmed"){
							if(isset($item['recurringEventId'])){
								if(in_array($item['recurringEventId'],$ids)){
									foreach($array as $k => $arr){
										if($arr['id'] == $item['recurringEventId']){
											unset($array[$k]);
										}
									}
								}
							}
							$array[$key] = array();
							$array[$key]['id'] = $item['id'];
							$array[$key]['summary'] = (isset($item['summary'])?$item['summary']:null);
							$array[$key]['status'] = (isset($item['status'])?$item['status']:null);
							$array[$key]['start'] = (isset($item['start']['date'])?$item['start']['date']:null);
							$array[$key]['end'] = (isset($item['end']['date'])?$item['end']['date']:null);
							$array[$key]['transparency'] = (isset($item['transparency'])?"Free":"Busy");
						}
					}
					if(!empty($array)){
						$collection[$store->getStoreId()] = $array;
					}
				}
			}
		}
		// if(!empty($collection)){
			$io = new Varien_Io_File();
			$io->setAllowCreateFolders(true);
			$io->open(array('path' => '/usr/share/nginx/tejar/'));
			$io->streamOpen('events.json');
			$io->streamWrite(Mage::helper('core')->jsonEncode($collection));
			$io->streamClose();
			Mage::log("generate", null, 'calendar.log');
		// }
    }
    
}
