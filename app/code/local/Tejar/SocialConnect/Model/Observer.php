<?php
/**
 * SocialConnect Observer 
 *
 * @category   Tejar
 * @package    Tejar_SocialConnect
 * @class      Tejar_SocialConnect_Model_Observer
 * @author     Zeeshan <zeeshan.zeeshan123@gmail.com>
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Tejar_SocialConnect_Model_Observer extends Varien_Event_Observer
{
	/*
	*
	*
	*/
	public function CustomerLogout(Varien_Event_Observer $observer){
		//echo "ddd";die;
		if(Mage::getSingleton('core/session')->getGoogleCsrf()){
			Mage::getSingleton('core/session')->unsGoogleCsrf();
		}
		
		if(Mage::getSingleton('core/session')->getFacebookCsrf()){
			Mage::getSingleton('core/session')->unsFacebookCsrf();
		}
	}
}