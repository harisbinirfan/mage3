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

class Tejar_SocialConnect_Model_Twitter_Oauth_Client
{
    const REDIRECT_URI_ROUTE = 'socialconnect/twitter/connect';
    const REQUEST_TOKEN_URI_ROUTE = 'socialconnect/twitter/request';

    const OAUTH_URI = 'https://api.twitter.com/oauth';
    const OAUTH2_SERVICE_URI = 'https://api.twitter.com/1.1';    

    const XML_PATH_ENABLED = 'customer/tejar_socialconnect_twitter/enabled';
    const XML_PATH_CLIENT_ID = 'customer/tejar_socialconnect_twitter/client_id';
    const XML_PATH_CLIENT_SECRET = 'customer/tejar_socialconnect_twitter/client_secret';

    protected $clientId = null;
    protected $clientSecret = null;
    protected $redirectUri = null;
    protected $client = null;
    protected $token = null;

    public function __construct()
     {
         if(($this->isEnabled = $this->_isEnabled())) {
             $this->clientId = $this->_getClientId();
             $this->clientSecret = $this->_getClientSecret();
             $this->redirectUri = Mage::getModel('core/url')->sessionUrlVar(
                 Mage::getUrl(self::REDIRECT_URI_ROUTE)
             );

            $this->client = new Zend_Oauth_Consumer(
                array(
                    'callbackUrl' => $this->redirectUri,
                    'siteUrl' => self::OAUTH_URI,
                    'authorizeUrl' => self::OAUTH_URI.'/authenticate',
                    'consumerKey' => $this->clientId,
                    'consumerSecret' => $this->clientSecret
                )
            );
         }
    }

    public function isEnabled()
    {
        return (bool) $this->isEnabled;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    public function setAccessToken($token)
    {
        $this->token = unserialize($token);
    }

    public function getAccessToken()
    {
        if(empty($this->token)) {
            $this->fetchAccessToken();
        }

        return serialize($this->token);
    }

    public function createAuthUrl()
    {
        return Mage::getUrl(self::REQUEST_TOKEN_URI_ROUTE);
    }

    public function fetchRequestToken()
    {
        if(!($requestToken = $this->client->getRequestToken())) {
            throw new Exeption(
                Mage::helper('tejar_socialconnect')
                    ->__('Unable to retrieve request token.')
            );
        }

        Mage::getSingleton('core/session')
            ->setTwitterRequestToken(serialize($requestToken));

        $this->client->redirect();
    }

    protected function fetchAccessToken()
    {
        if (!($params = Mage::app()->getFrontController()->getRequest()->getParams())
            ||
            !($requestToken = Mage::getSingleton('core/session')
                ->getTwitterRequestToken())
            ) {
            throw new Exception(
                Mage::helper('tejar_socialconnect')
                    ->__('Unable to retrieve access code.')
            );
        }

        if(!($token = $this->client->getAccessToken(
                    $params,
                    unserialize($requestToken)
                )
            )
        ) {
            throw new Exeption(
                Mage::helper('tejar_socialconnect')
                    ->__('Unable to retrieve access token.')
            );
        }

        Mage::getSingleton('core/session')->unsTwitterRequestToken();

        return $this->token = $token;
    }

    public function api($endpoint, $method = 'GET', $params = array())
    {
        if(empty($this->token)) {
            throw new Exception(
                Mage::helper('tejar_socialconnect')
                    ->__('Unable to proceed without an access token.')
            );
        }

        $url = self::OAUTH2_SERVICE_URI.$endpoint; 
        
        $response = $this->_httpRequest($url, strtoupper($method), $params);

        return $response;
    }

    protected function _httpRequest($url, $method = 'GET', $params = array())
    {
        $client = $this->token->getHttpClient(
            array(
                'callbackUrl' => $this->redirectUri,
                'siteUrl' => self::OAUTH_URI,
                'consumerKey' => $this->clientId,
                'consumerSecret' => $this->clientSecret
            )
        );

        $client->setUri($url);
        
        switch ($method) {
            case 'GET':
                $client->setMethod(Zend_Http_Client::GET);
                $client->setParameterGet($params);
                break;
            case 'POST':
                $client->setMethod(Zend_Http_Client::POST);
                $client->setParameterPost($params);
                break;
            case 'DELETE':
                $client->setMethod(Zend_Http_Client::DELETE);
                break;
            default:
                throw new Exception(
                    Mage::helper('tejar_socialconnect')
                        ->__('Required HTTP method is not supported.')
                );
        }

        $response = $client->request();

        Tejar_SocialConnect_Helper_Data::log($response->getStatus().' - '. $response->getBody());

        $decodedResponse = json_decode($response->getBody());

        if($response->isError()) {
            $status = $response->getStatus();
            if(($status == 400 || $status == 401 || $status == 429)) {
                if(isset($decodedResponse->error->message)) {
                    $message = $decodedResponse->error->message;
                } else {
                    $message = Mage::helper('tejar_socialconnect')
                        ->__('Unspecified OAuth error occurred.');
                }

                throw new Tejar_SocialConnect_Model_Twitter_Oauth_Exception($message);
            } else {
                $message = sprintf(
                    Mage::helper('tejar_socialconnect')
                        ->__('HTTP error %d occurred while issuing request.'),
                    $status
                );

                throw new Exception($message);
            }
        }

        return $decodedResponse;
    }

    protected function _isEnabled()
    {
        return $this->_getStoreConfig(self::XML_PATH_ENABLED);
    }

    protected function _getClientId()
    {
        return $this->_getStoreConfig(self::XML_PATH_CLIENT_ID);
    }

    protected function _getClientSecret()
    {
        return $this->_getStoreConfig(self::XML_PATH_CLIENT_SECRET);
    }

    protected function _getStoreConfig($xmlPath)
    {
        return Mage::getStoreConfig($xmlPath, Mage::app()->getStore()->getId());
    }

}