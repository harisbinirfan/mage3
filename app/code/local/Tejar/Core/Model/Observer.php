<?php
/**
 * ObservPricing Observer
 *
 * @category   Tejar
 * @package    Tejar_ObservPricing
 * @class      Tejar_Observpricing_Model_Observer
 * @author     Zeeshan <zeeshan.zeeshan123@gmail.com>
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Tejar_Core_Model_Observer extends Varien_Event_Observer
{

	
		/**
	     * Event Attribute PrePare Save
	     *
	     * @var Tejar_Observpricing_Model_Observer preDispatchAttributes
	 	 *
	 	 * @return Product
	     */
	 	public function preDispatchAttributes(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			if(!empty($event->getControllerAction()->getRequest()->getPost())){
				$postData = $event->getControllerAction()->getRequest()->getPost();
				foreach($postData['option']['value'] as $key => $item){
					foreach($postData['option']['value'][$key] as $k => $a){
						$postData['option']['value'][$key][$k] = $this->cleanSpace($postData['option']['value'][$key][$k]);
					}
				}
				if(!empty($postData) && is_array($postData)){
					$event->getControllerAction()->getRequest()->setPost($postData);
				}
			}	
		}
		
		/**
	     * Function Remove Any Space
	     *
	     * @var Tejar_Observpricing_Model_Observer cleanSpace
	 	 *
	 	 * @return Product
		*/
		protected function cleanSpace($string){
			$chr_map = array(
				"\xEF\xBB\xBF"    => "",   // U+FEFF Zero Width No-Break Space (BOM, ZWNBSP),
				"\xC2\xA0" 	      => " ",   // U+00A0 No-Break Space (NBSP)
				"\xE1\xA0\x8E" 	  => "",   // U+180E Mongolian Vowel Separator (MVS)
				"\xE1\x9A\x80" 	  => "-",   // U+1680 Ogham Space Mark
				"\xE3\x80\x80" 	  => " ",   // U+3000 Ideographic Space
				"\xE2\x81\x9F" 	  => " ",   // U+205F Medium Mathematical Space (MMSP)
				"\xE2\x80\xAF" 	  => " ",   // U+202F Narrow No-Break Space (NNBSP)
				"\xE2\x80\x8B" 	  => "",   // U+200B Zero Width Space (ZWSP)
				"\xE2\x80\x8A" 	  => " ",   // U+200A Hair Space
				"\xE2\x80\x89" 	  => " ",   // U+2009 Thin Space
				"\xE2\x80\x88" 	  => " ",   // U+2008 Punctuation Space
				"\xE2\x80\x87" 	  => " ",   // U+2007 Figure Space
				"\xE2\x80\x86" 	  => " ",   // U+2006 Six-Per-Em Space
				"\xE2\x80\x85" 	  => " ",   // U+2005 Four-Per-Em Space
				"\xE2\x80\x84" 	  => " ",   // U+2004 Three-Per-Em Space
				"\xE2\x80\x83" 	  => " ",   // U+2003 Em Space
				"\xE2\x80\x82" 	  => " ",   // U+2002 En Space
				"\xE2\x80\x81" 	  => " ",   // U+2001 Em Quad
				"\xE2\x80\x80" 	  => " "   // U+2000 En Quad
			);
			
			$chr = array_keys  ($chr_map); // but: for efficiency you should
			$rpl = array_values($chr_map); // pre-calculate these two arrays
			
			$string = str_replace($chr, $rpl, html_entity_decode($string, ENT_QUOTES, "UTF-8"));
			// $string = preg_replace('/\s+/', ' ', $string);
			// $string = preg_replace('!\s+!', ' ', $string);
			// $string = preg_replace("[\t\n\r]", " ", $string);
			$string = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $string);
			$string = preg_replace('/(?![\x{000d}\x{000a}\x{0009}])\p{C}/u', '', $string);
			// $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
			$string = $this->removeAccents($string);
			$string = preg_replace('/\h+/', ' ', $string);
			$string = trim($string);
			
			
			return $string;
		}
		
		
		/**
	      * Event Category PrePare Save
	      *
	      * @var Tejar_Observpricing_Model_Observer preDispatchCategory
	 	  *
	 	  * @return Product
	     */
	 	public function preDispatchCategory(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			if(!empty($event->getControllerAction()->getRequest()->getPost())){
				$postData = $event->getControllerAction()->getRequest()->getPost();
				$exclude = array('id','path','is_active','url_key','thumbnail','image','custom_thumbnail','include_in_menu','category_slider','collection_type','display_mode','landing_page','is_anchor','featured_category_slider','category_orbitslider','custom_use_parent_settings','custom_apply_to_products','custom_design','custom_design_from','custom_design_to','page_layout','custom_layout_update','intenso_menu_style');
				foreach($postData['general'] as $key => $post){
					if(!is_array($post) && !in_array($key,$exclude) && !empty($post)){
						$postData['general'][$key] = $this->cleanSpace($post);
						$postData['general'][$key] = str_replace('\\', '/', $postData['general'][$key]);
						// echo "<script>console.log('".$key." => ".$postData['general'][$key]."');</script>";
					}
					$event->getControllerAction()->getRequest()->setPost($postData);
				}
			}
		}
		
		
		/**
	     * Event Order Address PrePare Save
	     *
	     * @var Tejar_Observpricing_Model_Observer preDispatchCategory
	 	 *
	 	 * @return Order
	    */
	 	public function preDispatchOrderAddress(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			if(!empty($event->getControllerAction()->getRequest()->getPost())){
				$postData = $event->getControllerAction()->getRequest()->getPost();
				$excludeAddress = array('form_key');
				foreach($postData as $key => $address){
					if(!is_array($address) && !in_array($key,$excludeAddress) && !empty($address)){
						$postData[$key] = $this->cleanSpace($address);
					} else if(is_array($address) && !in_array($key,$excludeAddress) && !empty($address)){
						foreach($address as $k => $street){
							$postData[$key][$k] = $this->cleanSpace($street);
						}
					}
				}
				$event->getControllerAction()->getRequest()->setPost($postData);
			}
		}
		
		
		/**
	      * Event Order Product PrePare Save
	      *
	      * @var Tejar_Observpricing_Model_Observer preDispatchCategory
		  *
	 	  * @return Order
	    */
	 	public function preDispatchOrderProduct(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			if(!empty($event->getControllerAction()->getRequest()->getPost())){
				$items = $event->getControllerAction()->getRequest()->getPost('item');
				if($items){
					foreach($items as $key => $item){
						foreach($items[$key] as $k => $data){
							if(is_array($items[$key][$k])){
								foreach($items[$key][$k] as $o => $option){
									if(isset($items[$key][$k][$o]) && !empty($items[$key][$k][$o]) && !is_array($items[$key][$k][$o])){
										$items[$key][$k][$o] = $this->cleanSpace($items[$key][$k][$o]);
									}
								}
							}
						}
					}
				}
				
				$event->getControllerAction()->getRequest()->setPost('item',$items);
				// Mage::log($event->getControllerAction()->getRequest()->getPost(), null, 'product.log');			
			}
		}
		
		
		
		/**
	     * Event Order PrePare Save
	     *
	     * @var Tejar_Observpricing_Model_Observer preDispatchCategory
	 	 *
	 	 * @return Order
	    */
	 	public function preDispatchOrder(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			if(!empty($event->getControllerAction()->getRequest()->getPost())){
				$postData = $event->getControllerAction()->getRequest()->getPost();
				
				// Email Address
				if(isset($postData['order']['account']['email']) && !empty($postData['order']['account']['email'])){
					$postData['order']['account']['email'] = $this->cleanSpace($postData['order']['account']['email']);
				}
				
				
				// Order Commnets
				if(isset($postData['order']['comment']['customer_note']) && !empty($postData['order']['comment']['customer_note'])){
					$postData['order']['comment']['customer_note'] = $this->cleanSpace($postData['order']['comment']['customer_note']);
				}
				
				
				$excludeAddress = array("customer_address_id","country_id");
				// Billing Address
				if(isset($postData['order']['billing_address']) && !empty($postData['order']['billing_address'])){
					foreach($postData['order']['billing_address'] as $key => $billing){
						if(!is_array($billing) && !in_array($key,$excludeAddress) && !empty($billing)){
							$postData['order']['billing_address'][$key] = $this->cleanSpace($billing);
						} else if(is_array($billing) && !in_array($key,$excludeAddress) && !empty($billing)){
							foreach($billing as $k => $street){
								$postData['order']['billing_address'][$key][$k] = $this->cleanSpace($street);
							}
						}
					}
				}
				
				// Shipping Address
				if(isset($postData['order']['shipping_address']) && !empty($postData['order']['shipping_address']) && !$postData['shipping_as_billing']){
					foreach($postData['order']['shipping_address'] as $key => $shipping){
						if(!is_array($shipping) && !in_array($key,$excludeAddress) && !empty($shipping)){
							$postData['order']['shipping_address'][$key] = $this->cleanSpace($shipping);
						} else if(is_array($shipping) && !in_array($key,$excludeAddress) && !empty($shipping)){
							foreach($shipping as $k => $street){
								$postData['order']['shipping_address'][$key][$k] = $this->cleanSpace($street);
							}
						}
					}
				}
				
				// Gift Messages
				if(isset($postData['giftmessage'])){
					foreach($postData['giftmessage'] as $key => $giftmessage){
						foreach($postData['giftmessage'][$key] as $k => $gift){
							if(!is_array($gift) && !empty($gift) && $k != "type"){
								$postData['giftmessage'][$key][$k] = $this->cleanSpace($gift);
							}
						}
					}
				}
				
				$event->getControllerAction()->getRequest()->setPost($postData);
				
			}
		}
		
		
		/**
	      * Event Product PrePare Save
	      *
	      * @var Tejar_Observpricing_Model_Observer preDispatch
	 	 *
	 	 * @return Product
	      */
	 	public function preDispatchProduct(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			if(!empty($event->getControllerAction()->getRequest()->getPost())){
				$postData = $event->getControllerAction()->getRequest()->getPost();
				$exclude = array('website_ids','media_gallery','thumbnail','small_image','image','sku','visibility','status','options_container','country_of_manufacture','url_key','msrp_enabled','msrp_display_actual_price_type','use_config_gift_message_available');
				$textarea = array("description","short_description","in_the_box");
				$option_attr = array();
				$removeOpt = array();
				$removeOpt[] = "'";
				$removeOpt[] = '"';
				$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
				$query = $readConnection->select()
					->from('eav_attribute')
					->where('entity_type_id = ?', 4)
					->where('backend_type = ?', "int");
				$query->reset(Zend_Db_Select::COLUMNS);
				$query->columns(array('attribute_code'));
				$results = $readConnection->fetchAll($query);
				foreach($results as $row){
					$exclude[] = $row['attribute_code'];
					$option_attr[] = $row['attribute_code'];
				}
				
				// $chr = array_keys($chr_map); // but: for efficiency you should
				// $rpl = array_values($chr_map); // pre-calculate these two arrays

				foreach($postData['product'] as $key => $item){
					if(in_array($key,$option_attr)){
						$postData['option_attr'][] = $key;
					}
					if(!is_array($item) && !in_array($key,$exclude) && !empty($item)){
						$postData['product'][$key] = $this->cleanSpace($item);
						if(in_array($key,$textarea)){
							$postData['product'][$key] = preg_replace('/\s+/', ' ', $postData['product'][$key]);
						}
						$postData['product'][$key] = str_replace('\\', '/', $postData['product'][$key]);
					}
					// if($key == "model"){
					// 	$postData['product'][$key] = preg_replace('/[^A-Za-z0-9\-]/', '', $postData['product'][$key]);
					// }
					if($key == "model"){
						$postData['product'][$key] = str_replace( $removeOpt, "", $postData['product'][$key]);
					}
				}
				
				$event->getControllerAction()->getRequest()->setPost($postData);
			}
			
		}

		
		
		// /**
	    //  * Event Customer PrePare Save
	    //  *
	    //  * @var Tejar_Observpricing_Model_Observer preDispatchCustomer
	 	//  *
	 	//  * @return Order
	    // */
		// public function preDispatchCustomer(Varien_Event_Observer $observer){
		// 	$event = $observer->getEvent();
		// 	if(!empty($event->getControllerAction()->getRequest()->getPost())){
		// 		$postData = $event->getControllerAction()->getRequest()->getPost();
		// 		$exclude = array("success_url","error_url","form_key","persistent_remember_me","is_subscribed","current_password","gender","dob","month","year","day","country_id","password","confirmation","hideit");
		// 		foreach($postData as $key => $post){
		// 			if(!is_array($post) && !in_array($key,$exclude) && !empty($post)){
		// 				$postData[$key] = $this->cleanSpace($post);
		// 			} else if(is_array($post) && !in_array($key,$exclude) && !empty($post)){
		// 				foreach($post as $k => $value){
		// 					if(!is_array($value) && !empty($value)){
		// 						echo $postData[$key][$k] = $this->cleanSpace($value);
		// 					}
		// 				}
		// 			}
		// 		}
		// 		$event->getControllerAction()->getRequest()->setPost($postData);
			
		// 	}
		// }


				/**
	     * Event Customer PrePare Save
	     *
	     * @var Tejar_Observpricing_Model_Observer preDispatchCustomer
	 	 *
	 	 * @return Order
	    */
		public function preDispatchCustomer(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			$controller = $event->getControllerAction();
			$request = $controller->getRequest();
			$customer = Mage::getSingleton('customer/session');
			$error = 0;
			$redirectUrl = "";
			if($request->getActionName() == "createpost" && $request->getModuleName() == "customer"){
				$redirectUrl = Mage::getUrl("*/*/create", array('_secure' => true));
			} else if($request->getActionName() == "post" && $request->getModuleName() == "contact"){
				$redirectUrl = Mage::getUrl("*/");
			} else if($request->getActionName() == "formPost" && $request->getControllerName() == "address"){
				if($request->getParam('id')){
					$redirectUrl = Mage::getUrl('*/*/edit', array('id' => $request->getParam('id')));
				} else {
					$customer->setAddressFormData($request->getPost());
					$redirectUrl = Mage::getUrl('*/*/edit');
				}
			} else if($request->getActionName() == "editPost" && $request->getControllerName() == "account" && $request->getModuleName() == "customer"){
				$redirectUrl = Mage::getUrl("*/*/edit");
			}
			if(!empty($controller->getRequest()->getPost())){
				$postData = $event->getControllerAction()->getRequest()->getPost();
				$exclude = array("success_url","error_url","form_key","persistent_remember_me","is_subscribed","telephone","current_password","gender","dob","month","year","day","country_id","password","confirmation","hideit");
				$labelToName = array("name" => "Name","email"=>"Email","telephone"=>"Telephone","comment"=>"Comment","firstname"=>"First Name","lastname"=>"Last Name","prefix"=>"Prefix","suffix"=>"Suffix","street"=>"Street Address","city"=>"City","postcode"=>"Zip/Postal Code");
				foreach($postData as $key => $post){
					if(!is_array($post) && !in_array($key,$exclude) && !empty($post)){
						if($match = $this->checkDisallowChar($post)){
							if(array_key_exists($key, $labelToName)){
								$key = $labelToName[$key];
							}
							$error = 1;
							$customer->addError('Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key);
						}
						$postData[$key] = $this->cleanSpace($post);
					} else if(is_array($post) && !in_array($key,$exclude) && !empty($post)){
						foreach($post as $k => $value){
							if(!is_array($value) && !empty($value)){
								if($match = $this->checkDisallowChar($value)){
									if(array_key_exists($key, $labelToName)){
										$key = $labelToName[$key];
									}
									$error = 1;
									$customer->addError('Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key);
								}
								$postData[$key][$k] = $this->cleanSpace($value);
							}
						}
					}
				}
				if($redirectUrl && $error){
					$controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
					$controller->getResponse()->setRedirect($redirectUrl);
				}	
				$event->getControllerAction()->getRequest()->setPost($postData);
			}
		}
		

		public function checkDisallowChar($string){
			if(preg_match_all('/[^a-zA-Z\d]/', $string,$matches)){
				if (preg_match_all("/[\~]|[\^]|[\$]|[\+]|[\=]|[\\]|[\"]|[\<]|[\>]|[\|]/iu", $string, $match)) {
					return $match;
				}
			}
			return false;
		}

				/**
	     * Event Checkout PrePare Save
	     *
	     * @var Tejar_Observpricing_Model_Observer checkout_save
	 	 *
	 	 * @return Product
	    */
		public function preDispatchCheckout(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			if(!empty($event->getControllerAction()->getRequest()->getPost())){
				$postData = $event->getControllerAction()->getRequest()->getPost();
				$excludeAddress = array("address_id","country_id","use_for_shipping","telephone","customer_password","confirm_password","save_in_address_book");
				$addressForm = Mage::getModel('customer/form');
				$addressForm->setFormCode('customer_address_edit')
				->setEntityType('customer_address')
				->setIsAjaxRequest(Mage::app()->getRequest()->isAjax());
				$error = false;
				$message = array();
				// Billing Address
				if(isset($postData['billing']) && !empty($postData['billing'])){
					foreach($postData['billing'] as $key => $billing){
						if(!is_array($billing) && !in_array($key,$excludeAddress) && !empty($billing)){
							if($match = $this->checkDisallowChar($billing)){
								if($attr = $addressForm->getAttribute($key)){
									$key = $attr->getData('frontend_label');
								}
								$error = true;
								$message[] = 'Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key;
							}
							$postData['billing'][$key] = $this->cleanSpace($billing);
						} else if(is_array($billing) && !in_array($key,$excludeAddress) && !empty($billing)){
							foreach($billing as $k => $street){
								if($match = $this->checkDisallowChar($street)){
									if($attr = $addressForm->getAttribute($key)){
										$key = $attr->getData('frontend_label');
									}
									$error = true;
									$message[] = 'Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key;
								}
								$postData['billing'][$key][$k] = $this->cleanSpace($street);
							}
						}
					}
					if($error && !empty($message)){
						$response = array(
							"success" => false,
							"error" => $error,
							"message" => $message
						);
						$observer->getControllerAction()->getResponse()->setHeader('Content-type', 'application/json', true);
						echo $observer->getControllerAction()->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
						exit;
					}
				}
				// Shipping Address
				if(isset($postData['shipping']) && !empty($postData['shipping'])){
					foreach($postData['shipping'] as $key => $shipping){
						if(!is_array($shipping) && !in_array($key,$excludeAddress) && !empty($shipping)){
							if($match = $this->checkDisallowChar($shipping)){
								if($attr = $addressForm->getAttribute($key)){
									$key = $attr->getData('frontend_label');
								}
								$error = true;
								$message[] = 'Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key;
							}
							$postData['shipping'][$key] = $this->cleanSpace($shipping);
						} else if(is_array($shipping) && !in_array($key,$excludeAddress) && !empty($shipping)){
							foreach($shipping as $k => $street){
								if($match = $this->checkDisallowChar($street)){
									if($attr = $addressForm->getAttribute($key)){
										$key = $attr->getData('frontend_label');
									}
									$error = true;
									$message[] = 'Please make sure tha you do not include the characters "'.join(array_unique($match[0]),'').'" in your ' . $key;
								}
								$postData['shipping'][$key][$k] = $this->cleanSpace($street);
							}
						}
					}
					if($error && !empty($message)){
						$response = array(
							"success" => false,
							"error" => $error,
							"message" => $message
						);
						$observer->getControllerAction()->getResponse()->setHeader('Content-type', 'application/json', true);
						echo $observer->getControllerAction()->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
						exit;
					}
				}
				// Gift Messages
				if(isset($postData['giftmessage'])){
					foreach($postData['giftmessage'] as $key => $giftmessage){
						foreach($postData['giftmessage'][$key] as $k => $gift){
							if(!is_array($gift) && !empty($gift) && $k != "type"){
								$postData['giftmessage'][$key][$k] = $this->cleanSpace($gift);
							}
						}
					}
				}
				$event->getControllerAction()->getRequest()->setPost($postData);
			}
		}
		
		
		/**
	     * Event Checkout PrePare Save
	     *
	     * @var Tejar_Observpricing_Model_Observer checkout_save
	 	 *
	 	 * @return Product
	    */
	 	public function preDispatchOneStepCheckout(Varien_Event_Observer $observer){
			$event = $observer->getEvent();
			if(!empty($event->getControllerAction()->getRequest()->getPost())){
				$postData = $event->getControllerAction()->getRequest()->getPost();
				$excludeAddress = array("address_id","country_id","use_for_shipping","telephone","customer_password","confirm_password","save_in_address_book");
				
				
				// Billing Address
				if(isset($postData['billing']) && !empty($postData['billing'])){
					foreach($postData['billing'] as $key => $billing){
						if(!is_array($billing) && !in_array($key,$excludeAddress) && !empty($billing)){
							$postData['billing'][$key] = $this->cleanSpace($billing);
						} else if(is_array($billing) && !in_array($key,$excludeAddress) && !empty($billing)){
							foreach($billing as $k => $street){
								$postData['billing'][$key][$k] = $this->cleanSpace($street);
							}
						}
					}
				}
				
				// Shipping Address
				if(isset($postData['shipping']) && !empty($postData['shipping'])){
					foreach($postData['shipping'] as $key => $shipping){
						if(!is_array($shipping) && !in_array($key,$excludeAddress) && !empty($shipping)){
							$postData['shipping'][$key] = $this->cleanSpace($shipping);
						} else if(is_array($shipping) && !in_array($key,$excludeAddress) && !empty($shipping)){
							foreach($shipping as $k => $street){
								$postData['shipping'][$key][$k] = $this->cleanSpace($street);
							}
						}
					}
				}
				
				
				// Gift Messages
				if(isset($postData['giftmessage'])){
					foreach($postData['giftmessage'] as $key => $giftmessage){
						foreach($postData['giftmessage'][$key] as $k => $gift){
							if(!is_array($gift) && !empty($gift) && $k != "type"){
								$postData['giftmessage'][$key][$k] = $this->cleanSpace($gift);
							}
						}
					}
				}
				
				
				$event->getControllerAction()->getRequest()->setPost($postData);
			
			}
		}
		
			 /**
	 * Converts all accent characters to ASCII characters.
	 *
	 * If there are no accent characters, then the string given is just returned.
	 *
	 * **Accent characters converted:**
	 *
	 * Currency signs:
	 *
	 * @since 1.2.1
	 * @since 4.6.0 Added locale support for `de_CH`, `de_CH_informal`, and `ca`.
	 * @since 4.7.0 Added locale support for `sr_RS`.
	 * @since 4.8.0 Added locale support for `bs_BA`.
	 *
	 * @param string $string Text that might have accent characters
	 * @return string Filtered string with replaced "nice" characters.
	 */
	public function removeAccents($string) {
		if(!preg_match('/[\x80-\xff]/', $string)) return $string;
		if ($this->seems_utf8($string)) {
			$chars = array(
				// Decompositions for Latin-1 Supplement
				'ª' => 'a', 'º' => 'o','À' => 'A', 'Á' => 'A','Â' => 'A', 'Ã' => 'A','Ä' => 'A', 'Å' => 'A','Æ' => 'AE','Ç' => 'C','È' => 'E', 'É' => 'E','Ê' => 'E', 'Ë' => 'E','Ì' => 'I', 'Í' => 'I','Î' => 'I', 'Ï' => 'I','Ð' => 'D', 'Ñ' => 'N','Ò' => 'O', 'Ó' => 'O','Ô' => 'O', 'Õ' => 'O','Ö' => 'O', 'Ù' => 'U','Ú' => 'U', 'Û' => 'U','Ü' => 'U', 'Ý' => 'Y','Þ' => 'TH','ß' => 's','à' => 'a', 'á' => 'a','â' => 'a', 'ã' => 'a','ä' => 'a', 'å' => 'a','æ' => 'ae','ç' => 'c','è' => 'e', 'é' => 'e','ê' => 'e', 'ë' => 'e','ì' => 'i', 'í' => 'i','î' => 'i', 'ï' => 'i','ð' => 'd', 'ñ' => 'n','ò' => 'o', 'ó' => 'o','ô' => 'o', 'õ' => 'o','ö' => 'o', 'ø' => 'o','ù' => 'u', 'ú' => 'u','û' => 'u', 'ü' => 'u','ý' => 'y', 'þ' => 'th','ÿ' => 'y', 'Ø' => 'O',
				// Decompositions for Latin Extended-A
				'Ā' => 'A', 'ā' => 'a','Ă' => 'A', 'ă' => 'a','Ą' => 'A', 'ą' => 'a','Ć' => 'C', 'ć' => 'c','Ĉ' => 'C', 'ĉ' => 'c','Ċ' => 'C', 'ċ' => 'c','Č' => 'C', 'č' => 'c','Ď' => 'D', 'ď' => 'd','Đ' => 'D', 'đ' => 'd','Ē' => 'E', 'ē' => 'e','Ĕ' => 'E', 'ĕ' => 'e','Ė' => 'E', 'ė' => 'e','Ę' => 'E', 'ę' => 'e','Ě' => 'E', 'ě' => 'e','Ĝ' => 'G', 'ĝ' => 'g','Ğ' => 'G', 'ğ' => 'g','Ġ' => 'G', 'ġ' => 'g','Ģ' => 'G', 'ģ' => 'g','Ĥ' => 'H', 'ĥ' => 'h','Ħ' => 'H', 'ħ' => 'h','Ĩ' => 'I', 'ĩ' => 'i','Ī' => 'I', 'ī' => 'i','Ĭ' => 'I', 'ĭ' => 'i','Į' => 'I', 'į' => 'i','İ' => 'I', 'ı' => 'i','Ĳ' => 'IJ','ĳ' => 'ij','Ĵ' => 'J', 'ĵ' => 'j','Ķ' => 'K', 'ķ' => 'k','ĸ' => 'k', 'Ĺ' => 'L','ĺ' => 'l', 'Ļ' => 'L','ļ' => 'l', 'Ľ' => 'L','ľ' => 'l', 'Ŀ' => 'L','ŀ' => 'l', 'Ł' => 'L','ł' => 'l', 'Ń' => 'N','ń' => 'n', 'Ņ' => 'N','ņ' => 'n', 'Ň' => 'N','ň' => 'n', 'ŉ' => 'n','Ŋ' => 'N', 'ŋ' => 'n','Ō' => 'O', 'ō' => 'o','Ŏ' => 'O', 'ŏ' => 'o','Ő' => 'O', 'ő' => 'o','Œ' => 'OE','œ' => 'oe','Ŕ' => 'R','ŕ' => 'r','Ŗ' => 'R','ŗ' => 'r','Ř' => 'R','ř' => 'r','Ś' => 'S','ś' => 's','Ŝ' => 'S','ŝ' => 's','Ş' => 'S','ş' => 's','Š' => 'S', 'š' => 's','Ţ' => 'T', 'ţ' => 't','Ť' => 'T', 'ť' => 't','Ŧ' => 'T', 'ŧ' => 't','Ũ' => 'U', 'ũ' => 'u','Ū' => 'U', 'ū' => 'u','Ŭ' => 'U', 'ŭ' => 'u','Ů' => 'U', 'ů' => 'u','Ű' => 'U', 'ű' => 'u','Ų' => 'U', 'ų' => 'u','Ŵ' => 'W', 'ŵ' => 'w','Ŷ' => 'Y', 'ŷ' => 'y','Ÿ' => 'Y', 'Ź' => 'Z','ź' => 'z', 'Ż' => 'Z','ż' => 'z', 'Ž' => 'Z','ž' => 'z', 'ſ' => 's',
				// Decompositions for Latin Extended-B
				'Ș' => 'S', 'ș' => 's',
				'Ț' => 'T', 'ț' => 't',
				// Euro Sign
				'€' => 'E',
				// GBP (Pound) Sign
				'£' => '',
				// Vowels with diacritic (Vietnamese)
				// unmarked
				'Ơ' => 'O', 'ơ' => 'o','Ư' => 'U', 'ư' => 'u',
				// grave accent
				'Ầ' => 'A', 'ầ' => 'a','Ằ' => 'A', 'ằ' => 'a','Ề' => 'E', 'ề' => 'e','Ồ' => 'O', 'ồ' => 'o','Ờ' => 'O', 'ờ' => 'o','Ừ' => 'U', 'ừ' => 'u','Ỳ' => 'Y', 'ỳ' => 'y',
				// hook
				'Ả' => 'A', 'ả' => 'a','Ẩ' => 'A', 'ẩ' => 'a','Ẳ' => 'A', 'ẳ' => 'a','Ẻ' => 'E', 'ẻ' => 'e','Ể' => 'E', 'ể' => 'e','Ỉ' => 'I', 'ỉ' => 'i','Ỏ' => 'O', 'ỏ' => 'o','Ổ' => 'O', 'ổ' => 'o','Ở' => 'O', 'ở' => 'o','Ủ' => 'U', 'ủ' => 'u','Ử' => 'U', 'ử' => 'u','Ỷ' => 'Y', 'ỷ' => 'y',
				// tilde
				'Ẫ' => 'A', 'ẫ' => 'a','Ẵ' => 'A', 'ẵ' => 'a','Ẽ' => 'E', 'ẽ' => 'e','Ễ' => 'E', 'ễ' => 'e','Ỗ' => 'O', 'ỗ' => 'o','Ỡ' => 'O', 'ỡ' => 'o','Ữ' => 'U', 'ữ' => 'u','Ỹ' => 'Y', 'ỹ' => 'y',
				// acute accent
				'Ấ' => 'A', 'ấ' => 'a','Ắ' => 'A', 'ắ' => 'a','Ế' => 'E', 'ế' => 'e','Ố' => 'O', 'ố' => 'o','Ớ' => 'O', 'ớ' => 'o','Ứ' => 'U', 'ứ' => 'u',
				// dot below
				'Ạ' => 'A', 'ạ' => 'a','Ậ' => 'A', 'ậ' => 'a','Ặ' => 'A', 'ặ' => 'a','Ẹ' => 'E', 'ẹ' => 'e','Ệ' => 'E', 'ệ' => 'e','Ị' => 'I', 'ị' => 'i','Ọ' => 'O', 'ọ' => 'o','Ộ' => 'O', 'ộ' => 'o','Ợ' => 'O', 'ợ' => 'o','Ụ' => 'U', 'ụ' => 'u','Ự' => 'U', 'ự' => 'u','Ỵ' => 'Y', 'ỵ' => 'y',
				// Vowels with diacritic (Chinese, Hanyu Pinyin)
				'ɑ' => 'a',
				// macron
				'Ǖ' => 'U', 'ǖ' => 'u',
				// acute accent
				'Ǘ' => 'U', 'ǘ' => 'u',
				// caron
				'Ǎ' => 'A', 'ǎ' => 'a','Ǐ' => 'I', 'ǐ' => 'i','Ǒ' => 'O', 'ǒ' => 'o','Ǔ' => 'U', 'ǔ' => 'u','Ǚ' => 'U', 'ǚ' => 'u',
				// grave accent
				'Ǜ' => 'U', 'ǜ' => 'u',
			);
			$locale = Mage::app()->getLocale()->getLocaleCode();
			if ( 'de_DE' == $locale || 'de_DE_formal' == $locale || 'de_CH' == $locale || 'de_CH_informal' == $locale ) {
				$chars[ 'Ä' ] = 'Ae';
				$chars[ 'ä' ] = 'ae';
				$chars[ 'Ö' ] = 'Oe';
				$chars[ 'ö' ] = 'oe';
				$chars[ 'Ü' ] = 'Ue';
				$chars[ 'ü' ] = 'ue';
				$chars[ 'ß' ] = 'ss';
			} elseif ( 'da_DK' === $locale ) {
				$chars[ 'Æ' ] = 'Ae';
				$chars[ 'æ' ] = 'ae';
				$chars[ 'Ø' ] = 'Oe';
				$chars[ 'ø' ] = 'oe';
				$chars[ 'Å' ] = 'Aa';
				$chars[ 'å' ] = 'aa';
			} elseif ( 'ca' === $locale ) {
				$chars[ 'l·l' ] = 'll';
			} elseif ( 'sr_RS' === $locale || 'bs_BA' === $locale ) {
				$chars[ 'Đ' ] = 'DJ';
				$chars[ 'đ' ] = 'dj';
			}
			$string = strtr($string, $chars);
		} else {
			$chars = array();
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
				."\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
				."\xc3\xc4\xc5\xc7\xc8\xc9\xca"
				."\xcb\xcc\xcd\xce\xcf\xd1\xd2"
				."\xd3\xd4\xd5\xd6\xd8\xd9\xda"
				."\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
				."\xe4\xe5\xe7\xe8\xe9\xea\xeb"
				."\xec\xed\xee\xef\xf1\xf2\xf3"
				."\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
				."\xfc\xfd\xff";
			$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";
			$string = strtr($string, $chars['in'], $chars['out']);
			$double_chars = array();
			$double_chars['in'] = array("\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe");
			$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
			$string = str_replace($double_chars['in'], $double_chars['out'], $string);
		}
		return $string;
	}
	/**
	 * Checks to see if a string is utf8 encoded.
	 *
	 * NOTE: This function checks for 5-Byte sequences, UTF8
	 *       has Bytes Sequences with a maximum length of 4.
	 *
	 * @author bmorel at ssi dot fr (modified)
	 * @since 1.2.1
	 *
	 * @param string $str The string to be checked
	 * @return bool True if $str fits a UTF-8 model, false otherwise.
	 */
	public function seems_utf8($str) {
		$this->mbstring_binary_safe_encoding();
		$length = strlen($str);
		$this->reset_mbstring_encoding();
		for ($i=0; $i < $length; $i++) {
			$c = ord($str[$i]);
			if ($c < 0x80) $n = 0; // 0bbbbbbb
			elseif (($c & 0xE0) == 0xC0) $n=1; // 110bbbbb
			elseif (($c & 0xF0) == 0xE0) $n=2; // 1110bbbb
			elseif (($c & 0xF8) == 0xF0) $n=3; // 11110bbb
			elseif (($c & 0xFC) == 0xF8) $n=4; // 111110bb
			elseif (($c & 0xFE) == 0xFC) $n=5; // 1111110b
			else return false; // Does not match any model
			for ($j=0; $j<$n; $j++) { // n bytes matching 10bbbbbb follow ?
				if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
					return false;
			}
		}
		return true;
	}
	/**
	 * Set the mbstring internal encoding to a binary safe encoding when func_overload
	 * is enabled.
	 *
	 * When mbstring.func_overload is in use for multi-byte encodings, the results from
	 * strlen() and similar functions respect the utf8 characters, causing binary data
	 * to return incorrect lengths.
	 *
	 * This function overrides the mbstring encoding to a binary-safe encoding, and
	 * resets it to the users expected encoding afterwards through the
	 * `reset_mbstring_encoding` function.
	 *
	 * It is safe to recursively call this function, however each
	 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
	 * of `reset_mbstring_encoding()` calls.
	 *
	 * @since 3.7.0
	 *
	 * @see reset_mbstring_encoding()
	 *
	 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
	 *                    Default false.
	 */
	public function mbstring_binary_safe_encoding($reset = false) {
		static $encodings  = array();
		static $overloaded = null;
		if ( is_null( $overloaded ) ) {
			$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 ); // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
		}
		if ( false === $overloaded ) {
			return;
		}
		if ( ! $reset ) {
			$encoding = mb_internal_encoding();
			array_push( $encodings, $encoding );
			mb_internal_encoding( 'ISO-8859-1' );
		}
		if ( $reset && $encodings ) {
			$encoding = array_pop( $encodings );
			mb_internal_encoding( $encoding );
		}
	}
	/**
	 * Reset the mbstring internal encoding to a users previously set encoding.
	 *
	 * @see mbstring_binary_safe_encoding()
	 *
	 * @since 3.7.0
	 */
	public function reset_mbstring_encoding() {
		$this->mbstring_binary_safe_encoding( true );
	}
	
}
