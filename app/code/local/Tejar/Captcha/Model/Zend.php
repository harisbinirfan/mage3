<?php


/**
 * The reCaptcha Model
 *
 * @category   ProxiBlue
 * @package    ProxiBlue_reCaptcha
 * @author     Lucas van Staden (sales@proxiblue.com.au)
 */
class Tejar_Captcha_Model_Zend extends Mage_Captcha_Model_Zend implements Mage_Captcha_Model_Interface
{
    /**
     * Get Block Name
     *
     * @return string
     */
    public function getBlockName()
    {
		if($this->_getHelper()->getConfigNode('recaptcha_enable')){

			return 'tejar_captcha/captcha_recaptcha';
		}
		 return parent::getBlockName();
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

    public function generate()
    {
		if($this->_getHelper()->getConfigNode('recaptcha_enable')){
			$this->_private_key = $this->_getHelper()->getConfigNode('private_key');
			$this->_public_key = $this->_getHelper()->getConfigNode('public_key');
      $this->_exception = $this->_getHelper()->getConfigNode('exception');
		}

		return parent::generate();
    }


	public function getPrivateKey()
    {
        return $this->_private_key;
    }

    public function getPublicKey()
    {
        return $this->_public_key;
    }

    public function getException()
        {
            return $this->_exception;
        }

	  /**
     * Checks whether captcha was guessed correctly by user
     *
     * @param string $word
     * @return bool
     */
    public function isCorrect($word)
    {

		if($this->_getHelper()->getConfigNode('recaptcha_enable')){

      $request = Mage::app()->getRequest();
			$storedWord = $this->getWord();
			// if(md5($storedWord) === $request->getParam('appToken')){
			// 	return true;
			// }
			$this->generate();
			$recaptcha = new Google_Recaptcha();
			$recaptcha = $recaptcha->call($this->getPublicKey() , $this->getPrivateKey());
			$resp = $recaptcha->setExpectedHostname($_SERVER['SERVER_NAME'])
				  // ->setExpectedAction($_GET['action'])
				  ->setScoreThreshold(0.3)
				  ->verify($request->getParam('g-recaptcha-token'), $_SERVER['REMOTE_ADDR']);

          $errors = $resp->getErrorCodes();
					if(!empty($errors)){
						$fullActionName = Mage::app()->getFrontController()->getAction()->getFullActionName();
						Mage::log(array($fullActionName => $errors), null, 'captcha.log');
					}
					$exception = $request->getParam('exception');
					if($exception && $exception === $this->getException()){
						return true;
					}

			  if($resp->isSuccess()){
				  return true;
			  } else {
				return false;
			  }
		}

		return parent::isCorrect($word);
    }




}
