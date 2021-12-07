<?php
/**
 * Intenso Premium Theme
 * 
 * @category    Itactica
 * @package     Itactica_Billboard
 * @copyright   Copyright (c) 2014-2015 Itactica (http://www.itactica.com)
 * @license     http://getintenso.com/license
 */
//require_once Mage::getModuleDir('controllers', 'Itactica_Billboard') . DS . 'UnitController.php';
require_once "app/code/local/Itactica/Billboard/controllers/Adminhtml/Billboard/UnitController.php";
class Tejar_Billboard_Adminhtml_Billboard_UnitController extends Itactica_Billboard_Adminhtml_Billboard_UnitController
{
    
    /**
     * save billboard - action
     * @access public
     * @return void
     */
    public function saveAction() {
		
        if ($data = $this->getRequest()->getPost('unit')) {
            try {
                // array to string conversion for images_to_show
                if (isset($data['images_to_show'])) {
                    if (is_array($data['images_to_show'])) {
                        $data['images_to_show'] = implode(",", $data['images_to_show']);
                    } else {
                        $data['images_to_show'] = '';
                    }
                }
                $unit = $this->_initUnit();
                $unit->addData($data);
                $filenameLargeFirst = $this->_uploadAndGetName('image_large_first', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
                $filenameLargeSecond = $this->_uploadAndGetName('image_large_second', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
                $filenameLargeThird = $this->_uploadAndGetName('image_large_third', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
                $filenameLargeFourth = $this->_uploadAndGetName('image_large_fourth', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
				$filenameLargeFifth = $this->_uploadAndGetName('image_large_fifth', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
                $filenameMediumFirst = $this->_uploadAndGetName('image_medium_first', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
                $filenameMediumSecond = $this->_uploadAndGetName('image_medium_second', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
                $filenameMediumThird = $this->_uploadAndGetName('image_medium_third', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
                $filenameMediumFourth = $this->_uploadAndGetName('image_medium_fourth', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
				$filenameMediumFifth = $this->_uploadAndGetName('image_medium_fifth', Mage::helper('itactica_billboard/image')->getImageBaseDir(), $data);
                $unit->setData('image_large_first', $filenameLargeFirst);
                $unit->setData('image_large_second', $filenameLargeSecond);
                $unit->setData('image_large_third', $filenameLargeThird);
                $unit->setData('image_large_fourth', $filenameLargeFourth);
				$unit->setData('image_large_fifth', $filenameLargeFifth);
                $unit->setData('image_medium_first', $filenameMediumFirst);
                $unit->setData('image_medium_second', $filenameMediumSecond);
                $unit->setData('image_medium_third', $filenameMediumThird);
                $unit->setData('image_medium_fourth', $filenameMediumFourth);
				$unit->setData('image_medium_fifth', $filenameMediumFifth);
                $unit->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('itactica_billboard')->__('Billboard successfully saved.'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $unit->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            }
            catch (Mage_Core_Exception $e){
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setBillboardData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
            catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itactica_billboard')->__('There was a problem saving the billboard.'));
                Mage::getSingleton('adminhtml/session')->setBillboardData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('itactica_billboard')->__('Unable to find the billboard to save.'));
        $this->_redirect('*/*/');
    }
   
}
