<?php 
class Tejar_CustomPayment_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
 
	//protected $_code = 'custompayment';
	 const PAYMENT_METHOD_CUSTOMPAYMENT_CODE = 'custompayment';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_CUSTOMPAYMENT_CODE;

    /**
     * Bank Transfer payment block paths
     *
     * @var string
     */
    protected $_formBlockType = 'custompayment/form_standard';
    protected $_infoBlockType = 'custompayment/info_standard';
	
	/*public function __construct(){
		$this->_isGateway               = true;
		$this->_canOrder                = true;
		$this->_canAuthorize            = true;
		$this->_canCapture              = true;
		$this->_canCapturePartial       = true;
		$this->_canCaptureOnce          = true;
		$this->_canRefund               = true;
		$this->_canRefundInvoicePartial = true;
		$this->_canVoid                 = true;
	}
	*/
	
	/**
	* Return Order place redirect url
	*
	* @return string
	*/
	public function getOrderPlaceRedirectUrl()
	{
		//when you click on place order you will be redirected on this url, if you don't want this action remove this method
		//return Mage::getUrl('customcard/standard/redirect', array('_secure' => true));
	}
	public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
	 
}