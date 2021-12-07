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
 * @package     Mage_Customer
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Customer Data Helper
 *
 * @category   Mage
 * @package    Mage_Customer
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Customer_Helper_Data extends Mage_Customer_Helper_Data
{

	 /**
     * Get ZeroBounce
     *
     * @param string $formId
     * @return Tejar_Customer_Helper_Data
     */
    public function getZeroBounce($formId)
    {
		$enable = $this->getConfigNode('enabled');
        if ($formId && $enable) {
            $forms = $this->getConfigNode('forms');
			$forms = explode(',',$forms);
			if(in_array($formId, $forms)){
				return true;
			}

        }
        return false;
    }


	/**
     * Get ZeroBounce
     *
     * @param string $formId
     * @return Tejar_Customer_Helper_Data
     */
    public function getValidateZeroBounce($email)
    {
		$api_key = trim($this->getConfigNode('api_key'));

		$json = array();
		$excludeDomain = array("example.com");
		$pattern = "/(?<=@)[a-zA-Z0-9_\-\.]+$/i";
		preg_match($pattern, $email, $matches);
		if(!empty($matches)){
			if(in_array($matches[0],$excludeDomain)){
				$json['status'] = "invalid";
				$json['sub_status'] = $this->__('Invalid email address.');
				$json['zerobounce_result'] = true;
				return $json;
			}
		}

		if($api_key){

			// Complete API Libraries and Wrappers can be found here:
			// https://www.zerobounce.net/docs/zerobounce-api-wrappers/#api_wrappers__v2__php
			//set the api key and email to be validated
			$emailToValidate = $email;
			$IPToValidate = '';
			// $IPToValidate = $_SERVER['REMOTE_ADDR'];
			// use curl to make the request
			$url = 'https://api.zerobounce.net/v2/validate?api_key='.$api_key.'&email='.urlencode($emailToValidate).'&ip_address='.urlencode($IPToValidate);

			$ch = curl_init($url);
			//PHP 5.5.19 and higher has support for TLS 1.2
			curl_setopt($ch, CURLOPT_SSLVERSION, 6);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_TIMEOUT, 150);
			$response = curl_exec($ch);
			curl_close($ch);

			//decode the json response
			$json = json_decode($response, true);

			$fullActionName = Mage::app()->getFrontController()->getAction()->getFullActionName();
			$storeId = Mage::app()->getStore()->getStoreId();
			Mage::log(array("StoreID" => $storeId, "FullActionName" => $fullActionName, "email:" => $email, "status:" => $json['status'], "substatus:" => $json['sub_status']), null, 'email_validation.log');

      $json['zerobounce_result'] = false;
        if (isset($json['status']) && ($json['status'] === "invalid" || $json['status'] === "spamtrap" || $json['status'] === "abuse" || $json['sub_status'] === "global_suppression")){
        	$json['zerobounce_result'] = true;
        }

			return $json;
		}

		return false;
    }

    /**
       * Returns value of the node with respect to current area (frontend or backend)
       *
       * @param string $id The last part of XML_PATH_$area_CAPTCHA_ constant (case insensitive)
       * @param Mage_Core_Model_Store $store
       * @return Mage_Core_Model_Config_Element
       */
      public function getConfigNode($id, $store = null)
      {
          $areaCode = 'customer';
  		$storeId = Mage::app()->getStore()->getStoreId();
          return Mage::getStoreConfig( $areaCode . '/zerobounce/' . $id, $storeId);
      }

}
