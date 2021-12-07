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

class Tejar_SocialConnect_Model_Facebook_Info extends Varien_Object
{
    protected $params = array(
        'id',
        'name',
        'first_name',
        'last_name',
        'link',
        'birthday',
        'gender',
        'email',
        'picture.type(large)'
    );

    /**
     * Facebook client model
     *
     * @var Tejar_SocialConnect_Model_Facebook_Oauth2_Client
     */
    protected $client = null;

    public function _construct() {
        parent::_construct();

        $this->client = Mage::getSingleton('tejar_socialconnect/facebook_oauth2_client');
        if(!($this->client->isEnabled())) {
            return $this;
        }
    }

        /**
     * Get Facebook client model
     *
     * @return Tejar_SocialConnect_Model_Facebook_Oauth2_Client
     */
    public function getClient()
    {
        return $this->client;
    }

    public function setClient(Tejar_SocialConnect_Model_Facebook_Oauth2_Client $client)
    {
        $this->client = $client;
    }

    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
    }

    /**
     * Get Facebook client's access token
     *
     * @return stdClass
     */
    public function getAccessToken()
    {
        return $this->client->getAccessToken();
    }

    public function load($id = null)
    {
        $this->_load();

        return $this;
    }

    protected function _load()
    {
        try{
            $response = $this->client->api(
                '/me',
                'GET',
                array('fields' => implode(',', $this->params))
            );

            foreach ($response as $key => $value) {
                $this->{$key} = $value;
            }

        } catch(Tejar_SocialConnect_Facebook_OAuth2_Exception $e) {
            $this->_onException($e);
        } catch(Exception $e) {
            $this->_onException($e);
        }
    }

    protected function _onException($e)
    {
        if($e instanceof Tejar_SocialConnect_Facebook_OAuth2_Exception) {
            Mage::getSingleton('core/session')->addNotice($e->getMessage());
        } else {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
    }

}