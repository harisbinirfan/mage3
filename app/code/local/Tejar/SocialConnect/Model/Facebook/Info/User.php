<?php
/**
 * Tejar is not affiliated with or in any way responsible for this code.
 *
 * Commercial support is available directly from the [extension author](http://www.techytalk.info/contact/).
 *
 * @category Marko-M
 * @package SocialConnect
 * @author Marko MartinoviÄ‡ <marko@techytalk.info>
 * @copyright Copyright (c) Marko MartinoviÄ‡ (http://www.techytalk.info)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

class Tejar_SocialConnect_Model_Facebook_Info_User extends Tejar_SocialConnect_Model_Facebook_Info
{

    /**
     *
     * @var type Mage_Core_Model_Customer
     */
    protected $customer = null;


    /**
     * Load customer user info
     *
     * @param null|int $id Customer Id
     * @return Tejar_SocialConnect_Model_Facebook_Userinfo
     */
    public function load($id = null)
    {
        if(is_null($id) && Mage::getSingleton('customer/session')->isLoggedIn()) {
            $this->customer = Mage::getSingleton('customer/session')->getCustomer();
        } else if(is_int($id)){
            $this->customer = Mage::getModel('customer/customer')->load($id);
            
            // TODO: Implement
        }

        if(!$this->customer->getId()) {
            return $this;
        }

        if(!($socialconnectFid = $this->customer->getTejarSocialconnectFid()) ||
                !($socialconnectFtoken = $this->customer->getTejarSocialconnectFtoken())) {
            return $this;
        }

        $this->setAccessToken(unserialize($socialconnectFtoken));
        $this->_load();

        return $this;
    }

    protected function _onException($e) {
        parent::_onException($e);

        $helper = Mage::helper('tejar_socialconnect/facebook');
        /* @var $helper Tejar_SocialConnect_Helper_Facebook */

        $helper->disconnect($this->customer);
    }

}