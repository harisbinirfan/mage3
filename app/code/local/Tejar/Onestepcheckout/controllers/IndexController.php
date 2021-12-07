<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Onestepcheckout
 * @copyright   Copyright (c) 2017 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Class Magestore_Onestepcheckout_IndexController
 */
require_once 'Magestore/Onestepcheckout/controllers/IndexController.php';
class Tejar_Onestepcheckout_IndexController extends Magestore_Onestepcheckout_IndexController
{

  /**
  	* Returns captcha helper
  	*
  	* @return Mage_Captcha_Helper_Data
  	*/
  	protected function _getHelper()
  	{
  		if (empty($this->_helper)) {
  			$this->_helper = Mage::helper('customer');
  		}

  		return $this->_helper;
  	}


  	  /*
      /*
       * check if an email is valid
       */
      /**
       *
       */
      public function is_valid_emailAction()
      {
          $validator = new Zend_Validate_EmailAddress();
          $email_address = $this->getRequest()->getPost('email_address');
          $message = 'Invalid';
          $subErorr = "";
          if ($email_address != '') {
              // Check if email is in valid format
              if (!$validator->isValid($email_address)) {
                  $message = 'invalid';
              } else {

                  //if email is valid, check if this email is registered
                  if ($this->_emailIsRegistered($email_address)) {
                      $message = 'exists';
                  } else {
                      $message = 'valid';
                  }


  				$helper = $this->_getHelper();
  				if($helper->getZeroBounce("guest_checkout")){
  					$email = $email_address;
  					$data = $helper->getValidateZeroBounce($email);
  					if(isset($data['status']) &&  $data['zerobounce_result']){
  						$emailData = array("Form"=> "Guest Checkout","email" => $email, "error" => $data['sub_status']);
  						$message = 'zerobounce';
  						$subErorr = $data['sub_status'];
  					}
  				}

              }
          }
          $result = array('message' => $message, "suberorr" => $subErorr);
          $this->getResponse()->setBody(Zend_Json::encode($result));
      }

	/**
     *
     */
    public function indexAction()
    {

	//--- Check if a user attempts to access checkout page with old url, redirect to (buy/checkout)
		if(strstr(Mage::helper('core/url')->getCurrentUrl(), 'onestepcheckout')!==false){
			//echo Mage::helper('onestepcheckout')->getCheckoutUrl();die;
			header('location: '.Mage::helper('onestepcheckout')->getCheckoutUrl());
			exit;
		}

		//--- Check if user is ocming with additional 'register' parameter....
		$isRegister = array_key_exists('register', $this->getRequest()->getParams());
		//--- Get Admin test Value
		$allowGuestCheckout = Mage::getStoreConfig('checkout/options/guest_checkout');
		$requireCustomerToLogin = Mage::getStoreConfig('checkout/options/customer_must_be_logged');

		if(!Mage::getSingleton('customer/session')->isLoggedIn() && ($allowGuestCheckout==="0" && $requireCustomerToLogin==="1") || ($allowGuestCheckout==="0" && $requireCustomerToLogin==="0" && !$isRegister)){
			Mage::getSingleton('checkout/session')->addError($this->__('The checkout is disabled.'));
            $this->_redirect('checkout/cart');
            return;
		}else{
			return parent::indexAction();
		}
    }

   /**
     *
     */
    public function saveOrderAction()
    {
        $post = $this->getRequest()->getPost();

        if (!$post){
            Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Invalid Data! Please try to do it again!'));
            return $this->_redirect('*/*/index');
        }
        $error = false;
        $helper = Mage::helper('onestepcheckout');

        $billing_data = $this->getRequest()->getPost('billing', array());
        $shipping_data = $this->getRequest()->getPost('shipping', array());

        //2014.18.11 update VAT apply start
        if (isset($billing_data['taxvat'])) {
            $billing_data['vat_id'] = trim($billing_data['taxvat']);
            $shipping_data['vat_id'] = trim($billing_data['taxvat']);
        }
        //2014.18.11 update VAT apply end

        if (isset($billing_data['onestepcheckout_comment']))
            Mage::getModel('checkout/session')->setOSCCM($billing_data['onestepcheckout_comment']);

        $delivery_security_code = $this->getRequest()->getParam('delivery_security_code', false);
        if (!empty($delivery_security_code)) {
            Mage::getModel('checkout/session')->setData('delivery_security_code', $delivery_security_code);
        }

        if(isset($billing_data['telephone']) && $billing_data['telephone'] != ""){
			if(!preg_match('/^[0-9-+\s()]*$/', $billing_data['telephone'])){
				Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Please enter a valid Number'));
				return $this->_redirect('*/*/index');
			}	
			$phoneValidator = new Giggsey_Libphonenumber();
			$phoneUtil = $phoneValidator->call($billing_data['telephone']);
			if(!$phoneValidator->getIsValidation()){
				Mage::getSingleton('checkout/session')->addError($phoneUtil->getMessage());
				return $this->_redirect('*/*/index');
			} else if($phoneValidator->getIsValidation()){
                $billing_data['telephone'] = $phoneUtil->getRawInput();
            }
		}
		if((isset($shipping_data['telephone']) && $shipping_data['telephone'] != "") && Mage::getSingleton('checkout/session')->getData('different_shipping')){
			if(!preg_match('/^[0-9-+\s()]*$/', $shipping_data['telephone'])){
				Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Please enter a valid Number'));
				return $this->_redirect('*/*/index');
			}	
			$phoneValidator = new Giggsey_Libphonenumber();
			$phoneUtil = $phoneValidator->call($shipping_data['telephone']);
			if(!$phoneValidator->getIsValidation()){
				Mage::getSingleton('checkout/session')->addError($phoneUtil->getMessage());
				return $this->_redirect('*/*/index');
			} else if($phoneValidator->getIsValidation()){
                $shipping_data['telephone'] = $phoneUtil->getRawInput();
            }
		}

        //JSON reponse array for wirecard payment method
        $JSONresponse = array();

        //isAjax variable is the code name of payment method
        $isAjax = $this->getRequest()->getParam('isAjax');
        //set checkout method
        $checkoutMethod = '';
        if (!$this->_isLoggedIn()) {
            $checkoutMethod = 'guest';
            if ($helper->enableRegistration() || !$helper->allowGuestCheckout()) {
                $is_create_account = $this->getRequest()->getPost('create_account_checkbox');
                if(array_key_exists('email' , $billing_data)){
                $email_address = $billing_data['email'];
                $subErorr = "";
                $_helper = $this->_getHelper();
                if($_helper->getZeroBounce("guest_checkout")){
                	$email = $email_address;
                	$data = $_helper->getValidateZeroBounce($email);
                	if(isset($data['status']) &&  $data['zerobounce_result']){
                		$emailData = array("Form"=> "Guest Checkout","email" => $email, "error" => $data['sub_status']);
                		Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('There was a problem with the email address: ') . $data['sub_status']);
                		return $this->_redirect('*/*/index');
                	}
                }
              }
                if ($is_create_account || !$helper->allowGuestCheckout()) {
                    /* Changed By Adam (31/05/2016): Fix issue of expire session in onestepcheckout page */
                    if (!$email_address) {
                        Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Missing email address or session has been expired. Please enter information again.'));
                        return $this->_redirect('*/*/index');
                    }
                    /* End code */
                    if ($this->_emailIsRegistered($email_address)) {
                        $error = true;
                        Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Email is already registered.'));
                        $this->_redirect('*/*/index');
                    } else {
                        if (!$billing_data['customer_password'] || $billing_data['customer_password'] == '') {
                            $error = true;
                        } else if (!$billing_data['confirm_password'] || $billing_data['confirm_password'] == '') {
                            $error = true;
                        } else if ($billing_data['confirm_password'] !== $billing_data['customer_password']) {
                            $error = true;
                        }
                        if ($error) {
                            Mage::getSingleton('checkout/session')->addError(Mage::helper('onestepcheckout')->__('Please correct your password.'));
                            if ($isAjax) {
                                $JSONresponse['url'] = Mage::getUrl('onestepcheckout/index/index');
                            } else {
                                $this->_redirect('*/*/index');
                            }
                        } else {
                            $checkoutMethod = 'register';
                        }
                    }
                }
            }
        }
        if ($checkoutMethod != '')
            $this->getOnepage()->saveCheckoutMethod($checkoutMethod);

        //to ignore validation for disabled fields
        $this->setIgnoreValidation();

        //resave billing address to make sure there is no error if customer change something in billing section before finishing order
        $customerAddressId = $this->getRequest()->getPost('billing_address_id', false);
        /* Start: Modified by Daniel - Improve speed */
        $result = array();
        if (isset($customerAddressId)) {
            if (Mage::getSingleton('checkout/session')->getData('different_shipping')) {
                $billing_data["use_for_shipping"] = 0;
            } else {
                $billing_data["use_for_shipping"] = 1;
            }


            $result = $this->getOnepage()->saveBilling($billing_data, $customerAddressId);
        }
        /* End: Modified by Daniel - Improve speed */
        if (isset($result['error'])) {
            $error = true;
            if (is_array($result['message']) && isset($result['message'][0]))
                Mage::getSingleton('checkout/session')->addError($result['message'][0]);
            else
                Mage::getSingleton('checkout/session')->addError($result['message']);
            if ($isAjax) {
                $JSONresponse['url'] = Mage::getUrl('onestepcheckout/index/index');
            } else {
                $this->_redirect('*/*/index');
            }
        }

        //re-save shipping address
        $shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
        if ($helper->isShowShippingAddress()) {
            if (!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1') {
                /* Start: Modified by Daniel - Improve speed */
                $result = array();
                if (isset($shipping_address_id)) {
                    $result = $this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
                }
                /* End: Modified by Daniel - Improve speed */
                if (isset($result['error'])) {
                    $error = true;
                    if (is_array($result['message']) && isset($result['message'][0]))
                        Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                    else
                        Mage::getSingleton('checkout/session')->addError($result['message']);
                    $this->_redirect('*/*/index');
                }
            } else {
                $result['allow_sections'] = array('shipping');
                $result['duplicateBillingInfo'] = 'true';
                // $result = $this->getOnepage()->saveShipping($billing_data, $shipping_address_id);
            }


        }
		//---------------------------------------------- ZEE CODE ----------------------------------------------//
		if ($this->_isLoggedIn()) {
			//--- Check if Selected country is not in the Config Allowed Country List
			$selectedCountry    = $this->getOnepage()->getQuote()->getshippingAddress()->getCountry();
			$allowedCountryList = $this->getOnepage()->getQuote()->getStore()->getWebsite()->getConfig('general/country/allow');
			$allowedCountryList = explode(',', $allowedCountryList);

			if((!is_array($allowedCountryList) && $selectedCountry !== $allowedCountryList) || (is_array($allowedCountryList) && !in_array($selectedCountry, $allowedCountryList))){
				//echo "here";die;
				$countryName = Mage::app()->getLocale()->getCountryTranslation($selectedCountry);
				$result['error']   = 1;
				$result['message'] = "Sorry we do not deliver to ".$countryName."!";
				Mage::getSingleton('checkout/session')->addError($result['message']);
				$this->_redirect('*/*/index');
				//$this->_prepareDataJSON($result);
				return;
			}
		}
		//--------------------------------------------- ZEE CODE ------------------------------------------------//
        //re-save shipping method
        $shipping_method = $this->getRequest()->getPost('shipping_method', '');
        if (($shipping_method && $shipping_method != '') && !$this->isVirtual()) {
            $result = $this->getOnepage()->saveShippingMethod($shipping_method);
            if (isset($result['error'])) {
                $error = true;
                if (is_array($result['message']) && isset($result['message'][0])) {
                    Mage::getSingleton('checkout/session')->addError($result['message'][0]);
                } else {
                    Mage::getSingleton('checkout/session')->addError($result['message']);
                }
                if ($isAjax) {
                    $JSONresponse['url'] = Mage::getUrl('onestepcheckout/index/index');
                } else {
                    $this->_redirect('*/*/index');
                }
            } else {
                Mage::dispatchEvent('checkout_controller_onepage_save_shipping_method', array('request' => $this->getRequest(), 'quote' => $this->getOnepage()->getQuote()));
            }
        }

        $paymentRedirect = false;
        //save payment method
        try {
            $result = array();
            $payment = $this->getRequest()->getPost('payment', array());
            $result = $helper->savePaymentMethod($payment);
            if ($payment) {
                $this->getOnepage()->getQuote()->getPayment()->importData($payment);
            }
            $paymentRedirect = $this->getOnepage()->getQuote()->getPayment()->getCheckoutRedirectUrl();
        } catch (Mage_Payment_Exception $e) {
            if ($e->getFields()) {
                $result['fields'] = $e->getFields();
            }
            $result['error'] = $e->getMessage();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = $this->__('Unable to set Payment Method.');
        }

        if (isset($result['error'])) {
            $error = true;
            Mage::getSingleton('checkout/session')->addError($result['error']);
            if ($isAjax) {
                $JSONresponse['url'] = Mage::getUrl('onestepcheckout/index/index');
            } else {
                $this->_redirect('*/*/index');
            }
        }


        if ($paymentRedirect && $paymentRedirect != '') {
            return $this->_redirectUrl($paymentRedirect);
        }

        //only continue to process order if there is no error
        if (!$error) {
            try {
                $this->validateOrder();
                $result = $this->getOnepage()->saveOrder();
                //newsletter subscribe
                if ($helper->isShowNewsletter()) {
                    $news_billing = $this->getRequest()->getPost('billing');
                    if(isset($news_billing['newsletter_subscriber_checkbox'])){
                        $is_subscriber = $news_billing['newsletter_subscriber_checkbox'];
                        if ($is_subscriber) {
                            $subscribe_email = '';
                            //pull subscriber email from billing data
                            if (isset($billing_data['email']) && $billing_data['email'] != '') {
                                $subscribe_email = $billing_data['email'];
                            } else if ($this->_isLoggedIn()) {
                                $subscribe_email = Mage::helper('customer')->getCustomer()->getEmail();
                            }
                            if ($checkoutMethod == 'register') {
                                try {
                                    $customer = $this->getOnepage()->getQuote()->getCustomer();
                                    $customer->setIsSubscribed(1)
                                        ->save();
                                } catch (Exception $e) {
                                    Mage::getSingleton('checkout/session')->addError($e->getMessage());
                                    $this->_redirect('onestepcheckout');
                                    return;
                                }
                            } else {
                                //check if email is already subscribed
                                $subscriberModel = Mage::getModel('newsletter/subscriber')->loadByEmail($subscribe_email);
                                if ($subscriberModel->getId() === NULL) {
                                    Mage::getModel('newsletter/subscriber')->subscribe($subscribe_email);
                                } else if ($subscriberModel->getData('subscriber_status') != 1) {
                                    $subscriberModel->setData('subscriber_status', 1);
                                    try {
                                        $subscriberModel->save();
                                    } catch (Exception $e) {

                                    }
                                }
                            }
                        }
                    }
                }
                $redirectUrl = $this->getOnepage()->getCheckout()->getRedirectUrl();
            } catch (Mage_Core_Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('checkout/session')->addError($e->getMessage());
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $redirect = Mage::getUrl('onestepcheckout/index/index');
                if ($isAjax) {
                    $JSONresponse['url'] = $redirect;
                } else {
                    return $this->_redirect('*/*/index');
                }
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('checkout/session')->addError($e->getMessage());
                Mage::helper('checkout')->sendPaymentFailedEmail($this->getOnepage()->getQuote(), $e->getMessage());
                $redirect = Mage::getUrl('onestepcheckout/index/index');
                if ($isAjax) {
                    $JSONresponse['url'] = $redirect;
                } else {
                    return $this->_redirect('*/*/index');
                }
            }

            $this->getOnepage()->getQuote()->save();
            Mage::dispatchEvent('controller_action_postdispatch_checkout_onepage_saveOrder', array('post' => $post, 'controller_action' => $this));

            if ($redirectUrl) {
                $redirect = $redirectUrl;
            } else {
                $redirect = Mage::getUrl('checkout/onepage/success');
            }
            if ($isAjax == 'wirecard') {
                $this->getResponse()->setBody(json_encode($JSONresponse));
            } elseif ($isAjax == 'tco') {
                //Nothing to do here
                //tco payment response the JSON code automatically
            } else {
                $this->getResponse()->setRedirect($redirect);
                return;
            }
        } else {
            $this->_redirect('*/*/index');
        }
    }

	protected function _processValidateCustomer(Mage_Sales_Model_Quote_Address $address)
    {
        // set customer date of birth for further usage
        $dob = '';
        if ($address->getDob()) {
            $dob = Mage::app()->getLocale()->date($address->getDob(), null, null, false)->toString('yyyy-MM-dd');
            $this->getOnepage()->getQuote()->setCustomerDob($dob);
        }

        // set customer tax/vat number for further usage
        if ($address->getTaxvat()) {
            $this->getOnepage()->getQuote()->setCustomerTaxvat($address->getTaxvat());
        }

        // set customer gender for further usage
        if ($address->getGender()) {
            $this->getOnepage()->getQuote()->setCustomerGender($address->getGender());
        }

        // invoke customer model, if it is registering
        if (Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER == $this->getOnepage()->getQuote()->getCheckoutMethod()) {
            // set customer password hash for further usage
            $customer = Mage::getModel('customer/customer');
            $this->getOnepage()->getQuote()->setPasswordHash($customer->encryptPassword($address->getCustomerPassword()));

            // validate customer
            foreach (array(
                         'firstname'    => 'firstname',
                         'lastname'     => 'lastname',
                         'email'        => 'email',
                         'password'     => 'customer_password',
                         'confirmation' => 'confirm_password',
                         'taxvat'       => 'taxvat',
                         'gender'       => 'gender',
                     ) as $key => $dataKey) {
                $customer->setData($key, $address->getData($dataKey));
            }
            if ($dob) {
                $customer->setDob($dob);
            }
            $validationResult = $customer->validate();
            if (true !== $validationResult && is_array($validationResult)) {
                Mage::throwException(implode(', ', $validationResult));
            }
        } else if (Mage_Checkout_Model_Type_Onepage::METHOD_GUEST == $this->getOnepage()->getQuote()->getCheckoutMethod()) {
            // $email = $address->getData('email');
			$email = $this->getOnepage()->getQuote()->getBillingAddress()->getData('email');
            if (!Zend_Validate::is($email, 'EmailAddress')) {
                Mage::throwException(Mage::helper('checkout')->__('Invalid email address "%s"', $email));
            }
        }

        return true;
    }

	//check if email is registered

    /**
     * @param $email_address
     * @return bool
     */
    private function _emailIsRegistered($email_address)
    {
        $model = Mage::getModel('customer/customer');
        $model->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($email_address);
        if ($model->getId()) {
            return true;
        } else {
            return false;
        }
    }

    	 /**
     *
     */
    public function saveAddressOnestepcheckoutAction()
    {
        if(!$this->getRequest()->getParam('action')){
			return parent::saveAddressOnestepcheckoutAction();
		} else {
			if (!$this->checkFormKey()) {
				$result = array('error' => $this->__('Invalid Form Key. Please refresh the page.'));
				$this->getResponse()->setBody(Zend_Json::encode($result));
				return;
			}
			$billing_data = $this->getRequest()->getPost('billing', false);
			$shipping_data = $this->getRequest()->getPost('shipping', false);
			$billing_address_id = $this->getRequest()->getPost('billing_address_id', false);
            $shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
            if(!Mage::getSingleton('customer/session')->isLoggedIn()){
			if (!Zend_Validate::is($billing_data['email'], 'NotEmpty')) {
				$result = array();
				$result['error'] = 1;
				$result['message'] = array();
				$result['message'][] = "\"Email\" is a required value.";
				$this->getResponse()->setBody(Zend_Json::encode($result));
				return;
			}		
			if (!Zend_Validate::is($billing_data['email'], 'EmailAddress')) {
				$result = array();
				$result['error'] = 1;
				$result['message'] = array();
				$result['message'][] = "\"Email\" is invalid value.";
				$this->getResponse()->setBody(Zend_Json::encode($result));
				return;
			}
        }
			$result = $this->getOnepage()->saveBilling($billing_data, $billing_address_id);
			if(isset($result['error'])){
				$this->getResponse()->setBody(Zend_Json::encode($result));
				return;
			}
			$excludeAddress = array("address_id","country_id","use_for_shipping","telephone");
			$addressForm = Mage::getModel('customer/form');
			$addressForm->setFormCode('customer_address_edit')
            ->setEntityType('customer_address')
            ->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());
			if(!empty($billing_data) && !$billing_address_id){
				$result = array();
				$result['message'] = array();
				foreach($billing_data as $key => $billing){
					if(!is_array($billing) && !in_array($key,$excludeAddress) && !empty($billing)){
						if($match = $this->checkDisallowChar($billing)){
							if($attr = $addressForm->getAttribute($key)){
								$key = $attr->getData('frontend_label');
							}
							$result['error'] = 1;
							$result['message'][] = 'Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key;
						}
					} else if(is_array($billing) && !in_array($key,$excludeAddress) && !empty($billing)){
						foreach($billing as $k => $street){
							if($match = $this->checkDisallowChar($street)){
								if($attr = $addressForm->getAttribute($key)){
									$key = $attr->getData('frontend_label');
								}
								$result['error'] = 1;
								$result['message'][] = 'Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key;
							}
						}
					}
				}
				if(isset($result['error'])){
					$this->getResponse()->setBody(Zend_Json::encode($result));
					return;
				}
			}
			if (Mage::helper('onestepcheckout')->isShowShippingAddress()) {
                if(!$shipping_address_id){
				if ((!isset($billing_data['use_for_shipping']) || $billing_data['use_for_shipping'] != '1')
					|| (isset($shipping_data['different_shipping']) && $shipping_data['different_shipping'] == '1')
				) {
					$shipping_address_id = $this->getRequest()->getPost('shipping_address_id', false);
					$result = $this->getOnepage()->saveShipping($shipping_data, $shipping_address_id);
					if(isset($result['error'])){
						$this->getResponse()->setBody(Zend_Json::encode($result));
						return;
					}
					if(!empty($shipping_data)){
						$result = array();
						$result['message'] = array();
						foreach($shipping_data as $key => $shipping){
							if(!is_array($shipping) && !in_array($key,$excludeAddress) && !empty($shipping)){
								if($match = $this->checkDisallowChar($shipping)){
									if($attr = $addressForm->getAttribute($key)){
										$key = $attr->getData('frontend_label');
									}
									$result['error'] = 1;
									$result['message'][] = 'Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key;
								}
							} else if(is_array($shipping) && !in_array($key,$excludeAddress) && !empty($shipping)){
								foreach($shipping as $k => $street){
									if($match = $this->checkDisallowChar($street)){
										if($attr = $addressForm->getAttribute($key)){
											$key = $attr->getData('frontend_label');
										}
										$result['error'] = 1;
										$result['message'][] = 'Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key;
									}
								}
							}
						}
						if(isset($result['error'])){
							$this->getResponse()->setBody(Zend_Json::encode($result));
							return;
						}
					}
			$result = array();
			$result['success'] = 1;
			$result['message'] = array();
			$this->getResponse()->setBody(Zend_Json::encode($result));
			return;
				}
			}
        }
		}
    }
	protected function checkDisallowChar($string){
		if(preg_match_all('/[^a-zA-Z\d]/', $string,$matches)){
			if (preg_match_all("/[\~]|[\^]|[\$]|[\+]|[\=]|[\\]|[\"]|[\<]|[\>]|[\|]/iu", $string, $match)) {
				return $match;
			}
		}
		return false;
	}
}
