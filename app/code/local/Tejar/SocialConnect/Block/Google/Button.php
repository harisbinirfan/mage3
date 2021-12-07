<?php
/**
 * Tejar is not affiliated with or in any way responsible for this code.
 *
 * Commercial support is available directly from the [extension author](http://www.techytalk.info/contact/).
 *
 * @category Marko-M
 * @package SocialConnect
 * @author Marko Martinović <marko@techytalk.info>
 * @copyright Copyright (c) Marko Martinović (http://www.techytalk.info)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Tejar_SocialConnect_Block_Google_Button extends Mage_Core_Block_Template
{
    /**
     *
     * @var Tejar_SocialConnect_Model_Google_Oauth2_Client
     */
    protected $client = null;
    
    /**
     *
     * @var Tejar_SocialConnect_Model_Google_Info_User
     */
    protected $userInfo = null;

    protected function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('tejar_socialconnect/google_oauth2_client');
        if(!($this->client->isEnabled())) {
            return;
        }
        $this->userInfo = Mage::registry('tejar_socialconnect_google_userinfo');
		
		//--- Zee Code CSRF FIX....
		$csrfSessionValue = Mage::getSingleton('core/session')->getGoogleCsrf();
		//echo $csrfSessionValue; die;
		if(!isset($csrfSessionValue) && $csrfSessionValue!==""){
			Mage::getSingleton('core/session')->setGoogleCsrf($csrfSessionValue = md5(uniqid(rand(), true)));
		}
		$this->client->setState($csrfSessionValue);
        //--- Zee Code CSRF FIX....
		
		Mage::getSingleton('customer/session')
            ->setSocialConnectRedirect(Mage::helper('core/url')->getCurrentUrl());

        $this->setTemplate('tejar/socialconnect/google/button.phtml');
    }

    protected function _getButtonUrl()
    {
        if(is_null($this->userInfo) || !$this->userInfo->hasData()) {
            return $this->client->createAuthUrl();
        } else {
            return $this->getUrl('socialconnect/google/disconnect');
        }
    }

    protected function _getButtonText()
    {
        if(is_null($this->userInfo) || !$this->userInfo->hasData()) {
            if(!($text = Mage::registry('tejar_socialconnect_button_text'))){
                $text = $this->__('Connect');
            }
        } else {
            $text = $this->__('Disconnect');
        }

        return $text;
    }

}
