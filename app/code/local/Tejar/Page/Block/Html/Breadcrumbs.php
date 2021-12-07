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
 * to license@magento.com so we can send you a copy immediately
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Tejar
 * @package     Tejar_Page
 * @copyright  Copyright (c) 2006-2015 X.commerce, Inc. (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Html page block
 *
 * @category   Tejar
 * @package    Tejar_Page
 * @author     Zeeshan <zeeshan.zeeshan123@gmail.com>
 */
class Tejar_Page_Block_Html_Breadcrumbs extends Mage_Page_Block_Html_Breadcrumbs 
{
	
//==================================== ZEE CODE ========================================//
	
	protected function _toHtml() {             
		
		
		    if(Mage::registry('current_product')) {
				$cat_id = "";
		$pathMaxArray =array();
		$pathCountArray =array();
		
		if($this->_crumbs){
			foreach($this->_crumbs as $arrKey=>$arrValue){
				if($arrKey !="home" && $arrKey !="product"){
					unset($this->_crumbs[$arrKey]);
				}			
			}
			
				$product_id = Mage::registry('current_product')->getId();
				$obj = Mage::getModel('catalog/product');
				$_product = $obj->load($product_id); // Enter your Product Id in $product_id
			   
				if($product_id) {
					$categoryIds = $_product->getCategoryIds();
				 //$cat_id = $categoryIds[0];
				}
				//if products are found within the link..
				if(count( $categoryIds)>0){
					
					$category = array();
					$pathIdsCountarray = array();
					for($j=0;$j<=count($categoryIds);$j++){
						//--- Check if key exists in categoryIds Array
						if(array_key_exists($j, $categoryIds)){
							$category[$j] = Mage::getModel('catalog/category')->load($categoryIds[$j]);
						}	
						//--- Check if product contains multiple categories or not, if yes then break the flow and include only one path
							if($j>0){
								
								$_path = $category[$j-1]->getPath();
								$ids = explode('/', $_path);
								//get path Ids...
								$pathIdsCountarray[] = $ids;
							}
						 }
						 //--- To get Category ids for the path with maximum count (longest path), sort pathIdsCountarray in descending order
						rsort($pathIdsCountarray);
						foreach($pathIdsCountarray[0] as $pathCountArr){
							array_push($pathMaxArray, $pathCountArr);
							//array_push($pathCountArray, count($ids));
						 }
								
					//Iterate through categories and products..
					for($i=0;$i<=count($categoryIds);$i++){
						//--- Check if key exists in categoryIds Array
						if(array_key_exists($i, $categoryIds)){
							$category[$i] = Mage::getModel('catalog/category')->load($categoryIds[$i]);
							$_path = $category[$i]->getPath();
							$ids = explode('/', $_path);
							arsort($ids);
							$currCount =  max($ids);
						}	
						//--- if currcount is low or equal to prevCount, it means it has restarted breadcrumbs, break it
							if($currCount==max($pathMaxArray) ){
								$categoryIdIndex = 0;
								for($j=2;$j<=count($ids);$j++){
									//--- Check if key exists in ids Array
									if(array_key_exists($j, $ids)){
										$category2[$j] = Mage::getModel('catalog/category')->load($ids[$j]);
										if($category2[$j]->getIsActive()){
											$cat_name[$j] = $category2[$j]->getName();
											//echo $ids[$j]." - ".$cat_name[$j]."---";
											$cat_url[$j]  =  $this->getBaseUrl().$category2[$j]->getUrlPath();
											$this->_crumbs['category'.$ids[$j]] = array('label'=>$cat_name[$j], 'title'=>'', 'link'=>$cat_url[$j],'first'=>'','last'=>'','readonly'=>'');
										}
										$categoryIdIndex++;
									}
								}
								//break;
							}
					}
					  //set product (last) link to true..
						$this->_crumbs['product']['last'] = true;
				}else{
					   //if no Categories were found show only home and category...
						if(is_array($this->_crumbs)) {
							
							reset($this->_crumbs);
							$this->_crumbs[key($this->_crumbs)]['first'] = true;
							end($this->_crumbs);
							$this->_crumbs[key($this->_crumbs)]['last'] = true;
						}
					} 
				//ksort($this->_crumbs);
				$home = $this->_crumbs['home'];
				unset($this->_crumbs['home']);
				unset($this->_crumbs['category']);
				array_unshift($this->_crumbs,$home);
				$this->assign('crumbs', $this->_crumbs);
				
			}
			
	   }
	   return parent::_toHtml();
	}
	//===================== END ZEE CODE =======================//
}