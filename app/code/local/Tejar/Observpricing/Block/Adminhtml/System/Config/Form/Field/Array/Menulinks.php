<?php
/**
 * Intenso Premium Theme
 * 
 * @category    Itactica
 * @package     Itactica_Intenso
 * @copyright   Copyright (c) 2014-2017 Intenso (https://www.getintenso.com)
 * @license     http://getintenso.com/license
 */

class Tejar_Observpricing_Block_Adminhtml_System_Config_Form_Field_Array_Menulinks extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{

	public function __construct()
    {
        $this->addColumn('source', array(
            'label' => Mage::helper('adminhtml')->__('Sourcing'),
            'style' => 'width:120px',
        ));
		
		$this->addColumn('currency', array(
            'label' => Mage::helper('adminhtml')->__('Currency'),
            'style' => 'width:120px',
        ));
		
        $this->addColumn('value', array(
            'label' => Mage::helper('adminhtml')->__('Price Formula'),
            'style' => 'width:120px',
        )); 
		 
		$this->addColumn('special_value', array(
            'label' => Mage::helper('adminhtml')->__('Special Price Formula'),
            'style' => 'width:120px',
        )); 
		
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add Source');
		parent::__construct();
		
        $this->setTemplate('itactica/intenso/system/config/form/field/array_dropdown.phtml');
    }

    protected function _renderCellTemplate($columnName) {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }
        $column = $this->_columns[$columnName];
        $inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';
		
		$attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', 'sourcing');
		if ($attribute->usesSource()) {
			$options = $attribute->getSource()->getAllOptions(false);
			$defaultvalue = $attribute->getDefaultValue();
		}
        if ($columnName == 'value' || $columnName == 'special_value') {
			return '<textarea name="' . $inputName . '"' . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . '>#{' . $columnName . '}</textarea>';
        } elseif ($columnName == 'source') {
			$value = '#{'.$columnName.'}';
            $rendered = '<select ' . (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '  name="' . $inputName . '">';
				$rendered .= '<option value=""></option>';
			foreach ($options as  $name) {
				$seletedValue = "";
                $rendered .= '<option ' . $seletedValue . ' value="' . $name['value'] . '">' . $name['label']  . '</option>';
            }
            $rendered .= '</select>';
        } elseif ($columnName == 'currency') {
			$OptionCurrencies = Mage::app()->getLocale()->getOptionCurrencies();
			$value = '#{'.$columnName.'}';
            $rendered = '<select ' . (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '  name="' . $inputName . '">';
				$rendered .= '<option value=""></option>';
			foreach ($OptionCurrencies as  $Currency) {
				$seletedValue = "";
                $rendered .= '<option ' . $seletedValue . ' value="' . $Currency['value'] . '">' . $Currency['label']  . '</option>';
            }
            $rendered .= '</select>';
		}  else {
            return '<input type="text" name="' . $inputName . '" value="#{' . $columnName . '}" ' . ($column['size'] ? 'size="' . $column['size'] . '"' : '') . (isset($column['style']) ? ' style="'.$column['style'] . '"' : '')  . '/>';
        }

        return $rendered;
    }
}


