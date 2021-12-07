<?php
/**
 * Intenso Premium Theme
 * 
 * @category    Itactica
 * @package     Itactica_Intenso
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */

class Tejar_Observpricing_Block_Adminhtml_System_Config_Form_Field_Array_Currency extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

	public function __construct()
    {
        $this->addColumn('currency', array(
            'label' => Mage::helper('adminhtml')->__('Currency'),
            'style' => 'width:80px',
        ));
		$allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies();	
		
		foreach($allowedCurrencies as $currency){
			$this->addColumn($currency, array(
				'label' => $currency,
				'style' => 'width:80px',
			));
		}
		
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Source');
		parent::__construct();
        $this->setTemplate('itactica/intenso/system/config/form/field/array_dropdown.phtml');
    }
	
	
	  /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     * @return string
     */
    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column     = $this->_columns[$columnName];
        $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

        if ($column['renderer']) {
            return $column['renderer']->setInputName($inputName)->setColumnName($columnName)->setColumn($column)
                ->toHtml();
        }
		
		if($columnName == 'currency'){
			$OptionCurrencies = Mage::app()->getLocale()->getOptionCurrencies();
			$value = '#{'.$columnName.'}';
            $rendered = '<select ' . (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '  name="' . $inputName . '">';
				$rendered .= '<option value=""></option>';
			foreach ($OptionCurrencies as  $Currency) {
				$seletedValue = "";
                $rendered .= '<option ' . $seletedValue . ' value="' . $Currency['value'] . '">' . $Currency['label']  . '</option>';
            }
           return $rendered .= '</select>';
		} else {

			return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '/>';
		}
    }

}


