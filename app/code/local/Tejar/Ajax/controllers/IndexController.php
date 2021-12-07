<?php
/**
 * Tejar
 *
 * @category    Tejar
 * @package     Tejar_Catalog
 * @author      Zeeshan <zeeshan.zeehsan123@gmail.com>
 */

class Tejar_Ajax_IndexController extends Mage_Core_Controller_Front_Action{
	
	public function topMenuAction()
    {
			$this->loadLayout();
			$toplink = $this->getLayout()->getBlock('mega.menu')->toHtml();
			$this->renderLayout();
			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($toplink));
		
    }
	
	public function featuredSliderAction()
    {
			$this->loadLayout();
			$blockName = $this->getRequest()->getParam('id');
			$block = $this->getLayout()->createBlock('itactica_featuredcategories/view')->setIdentifier($blockName)->setTemplate('itactica_featuredcategories/view_custom.phtml')->toHtml(); 
			$this->renderLayout();
			$this->getResponse()->setHeader('Content-type', 'application/json');
			$this->getResponse()->setBody(Mage::helper('core')->jsonEncode($block));
		
    }
	
}
