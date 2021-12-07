<?php
/**
 * Tejar
 *
 * @category    Tejar
 * @package     Tejar_Catalog
 * @author      Zeeshan <zeeshan.zeehsan123@gmail.com>
 */

class Tejar_Catalog_CategoryController extends Mage_Core_Controller_Front_Action{
	
	/* For mobile listings fix for product names with different screen widths..
	 *@name ajaxScreenWidthAction
	 *@params Array through Ajax request
	 *@return JsonEncoded Array
	*/
	public function ajaxScreenWidthAction(){
		
		//--- Initialize Array to return ..
		$_productNameArrayToReturn = array();
		
		//--- Get Parameters sent from AJAX call
		$_productNameArray = $this->getRequest()->getParam('productNames');
		$_screenWidth = $this->getRequest()->getParam('screenWidth');
		
		if (is_array($_productNameArray) || is_object($_productNameArray)){
			//--- Loop through all products name 
			foreach($_productNameArray as $k=>$v){
				$_productName = $v;
				if($_screenWidth <325 && strlen($_productName)>=35){
					if($this->wordLengthCheck($_productName)){
						$_productName = substr($_productName, 0,28)."...";
					}else{
						$_productName = substr($_productName, 0,32)."...";
					}
				}else if($_screenWidth > 325 && $_screenWidth <= 600 && strlen($_productName)>=50){
					$_productName = substr($_productName, 0,50)."...";
				}
				else if($_screenWidth > 600 && $_screenWidth < 1000 && strlen($_productName)>=40){
					$_productName = substr($_productName, 0,45)."...";
				}else if($_screenWidth >= 1000 && strlen($_productName)>=40){
					$_productName = substr($_productName, 0,45)."...";
				}
				$_productNameArrayToReturn[$k]=$_productName;
			}
			//--- Return JSON Encoded Array..
			echo json_encode($_productNameArrayToReturn);
		}
	}
	
	/* Check if given string contains a word having lenght > 10
	 *@name wordLengthCheck
	 *@params String
	 *@return Boolean
	*/
	public function wordLengthCheck($nameStr){
		
		$nameStr = explode(' ', $nameStr);
		foreach($nameStr as $str){
			if(strlen($str)>=11){
				return true;
				break;
			}
		}
		return false;
	}
}
