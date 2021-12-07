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

class Tejar_SocialConnect_Block_Facebook_Button extends Mage_Core_Block_Template
{
    /**
     *
     * @var Tejar_SocialConnect_Model_Facebook_Oauth2_Client
     */
    protected $client = null;

    /**
     *
     * @var Tejar_SocialConnect_Model_Facebook_Info_User
     */
    protected $userInfo = null;

    protected function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('tejar_socialconnect/facebook_oauth2_client');
        if(!($this->client->isEnabled())) {
            return;
        }
        $this->userInfo = Mage::registry('tejar_socialconnect_facebook_userinfo');

        //--- Zee Code CSRF FIX....
		$csrfSessionValue = Mage::getSingleton('core/session')->getFacebookCsrf();
		
		if(!isset($csrfSessionValue) && $csrfSessionValue!==""){
			Mage::getSingleton('core/session')->setFacebookCsrf($csrfSessionValue = md5(uniqid(rand(), true)));
		}
		$this->client->setState($csrfSessionValue);
		//--- Zee Code CSRF FIX....
        
		Mage::getSingleton('customer/session')
            ->setSocialConnectRedirect(Mage::helper('core/url')->getCurrentUrl());

        $this->setTemplate('tejar/socialconnect/facebook/button.phtml');
    }

    protected function _getButtonUrl()
    {
        if(is_null($this->userInfo) || !$this->userInfo->hasData()) {
            return $this->client->createAuthUrl();
        } else {
            return $this->getUrl('socialconnect/facebook/disconnect');
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