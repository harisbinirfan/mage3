<?php
/**
 * Product search result block
 *
 * @category   Mage
 * @package    Mage_CatalogSearch
 * @module     CatalogSearch
	
	Created by  ZEESHAN
 */
class Tejar_CatalogSearch_Model_Observer
{
    /**
	 * @name   catalogBlockProductListCollection
	 * @desc   
     * @param  Varien_Event_Observer $observer
     * @return Tejar_CatalogSearch_Model_Observer
     */
    public function catalogBlockProductListCollection(Varien_Event_Observer $observer)
    {
	
		//--- DISABLED CLAUSE ATM...
        $request = Mage::app()->getFrontController()->getRequest();
        $action = $request->getModuleName() . '_' . $request->getControllerName() . '_' . $request->getActionName();
		
		
        if($action === 'catalogsearch_result_index' || $action === 'catalogsearch_advanced_result') {
			//--- Get Current Product Collection from Observer Object
            $collection = $observer->getEvent()->getCollection();
			
				//echo "------------->", $collection->getSelect();die;
		//--- Initialize All Values....
			$myURLSearchquery    = addslashes(strip_tags(Mage::helper('catalogsearch')->getQueryText()));
			//$myURLSearchOrder    = $request->getParam('order');
			//$myURLSearchOrderDir = $request->getParam('dir');
			//echo $myURLSearchquery;die;
			$myURLSearchOrder  = "";
			$myQueryRegex = "";	
			$myQueryCases1       = "";
			$myQueryCases2       = "";
			$myQueryCases3       = "";
			$myQueryCases4       = "";
			$myQueryCases5 		 = "";
			$myQueryCases6 		 = "";
			$myQueryCases7 		 = "";
			$myQueryCases8 		 = "";
			$orderBy             = "";
			$searchParamsArray   = array();
			
		//--- Reset ORDER BY AND WHERE Clases from Current Colleciton..
			//$collection->getSelect()->reset(Zend_Db_Select::ORDER);
			//$collection->getSelect()->reset(Zend_Db_Select::WHERE);
			
		//-- Get product flat data table, store based (if flat catalog is disabled)
			if (!Mage::helper('catalog/product_flat')->isEnabled()) {
				$table = 'catalog_product_flat_'.Mage::app()->getStore()->getStoreId();
				
				$collection->getSelect()->join($table, 'e.entity_id ='.$table.'.entity_id');
				
            }
			
		//--- SET product visibility...
		//Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($collection);
		if(strpos($myURLSearchquery, ' ')){
				$myURLSearchqueryArray = explode(' ', $myURLSearchquery);
				$wordIndex = 0;
				$myVar = "";
				
			//--- Generate CASES for ORDER BY clause for multiple words 
				$myQueryCases1 .= $myURLSearchquery;
				$myQueryCases2 .= "% ".$myURLSearchquery;
				$myQueryCases3 .= $myURLSearchquery." %";
				$myQueryCases4 .= "% ".$myURLSearchquery." %";
				$myQueryCases8 .= "%".$myURLSearchquery."%";
				$myQueryRegex  .= "";
				
				
				foreach($myURLSearchqueryArray as $myQuery){
				//if(strlen($myQuery)>1){	
					if($myQuery[strlen($myQuery)-1]!='s'){
							$considerPlural = "s";
						}else{
							$considerPlural = "";
						}
						
					array_push($searchParamsArray, array('like' =>  $myQuery."%"));
					array_push($searchParamsArray, array('like' =>  "%"." ".$myQuery." "."%"));
					array_push($searchParamsArray, array('like' =>  "%_ ".$myQuery."%"));
					array_push($searchParamsArray, array('like' =>  "%_".$myQuery));
					array_push($searchParamsArray, array('like' =>  "%_".$myQuery." %"));
					array_push($searchParamsArray, array('like' =>  "%_".$myQuery.$considerPlural." %"));
					
					array_push($searchParamsArray, array('like' =>  substr($myQuery, 0, strlen($myQuery)-1)." _%"));
					
				//--- CONTINUE: Generate CASES for ORDER BY clause for search with multiple words..
				
					$wordIndex++;
					$myQueryRegex  .= "%".$myQuery."%";
				}
				
				array_push($searchParamsArray, array('like' =>  $myURLSearchquery[0]. $myURLSearchquery[1]."_%"));
				
			}else{
				//--- Consider Plural as well............
				if($myURLSearchquery[strlen($myURLSearchquery)-1]!='s'){
					$considerPlural = "s";
				}elseif($myURLSearchquery[strlen($myURLSearchquery)-1]=='s'){
					$considerPlural = "";
					array_push($searchParamsArray, array('like' =>  substr($myURLSearchquery, 0, strlen($myURLSearchquery)-1)." %"));
					array_push($searchParamsArray, array('like' =>  "% ".substr($myURLSearchquery, 0,strlen($myURLSearchquery)-1)." %"));
					array_push($searchParamsArray, array('like' =>  "% ".substr($myURLSearchquery, 0, strlen($myURLSearchquery)-1)));
					array_push($searchParamsArray, array('like' =>  "%".substr($myURLSearchquery, 0, strlen($myURLSearchquery)-1)."%"));
				}
				
				
				array_push($searchParamsArray, array('like' =>  $myURLSearchquery." %"));
				array_push($searchParamsArray, array('like' =>  "% ".$myURLSearchquery." %")); 
				array_push($searchParamsArray, array('like' =>  "% ".$myURLSearchquery));
				array_push($searchParamsArray, array('like' =>  $myURLSearchquery."_%"));
				array_push($searchParamsArray, array('like' =>  "%".$myURLSearchquery."%"));
				//array_push($searchParamsArray, array('like' =>  "% ".$myURLSearchquery.$considerPlural." %"));
				//array_push($searchParamsArray,array('like' =>  "%".$myURLSearchquery.$considerPlural."%"));
				//array_push($searchParamsArray,array('like' =>  "%".$myURLSearchquery.$considerPlural));
				//array_push($searchParamsArray, array('eq' => $myURLSearchquery));
			}
			
			if(($key = array_search("%".$myURLSearchquery."%", $searchParamsArray)) !== false) {
					unset($searchParamsArray[$key]);
				}
			
				//--- Again check if search query contains single or multiple words!
				if(!strpos($myURLSearchquery, ' ')){
					$orderBy  .= "CASE WHEN name LIKE '".$myURLSearchquery." %'  THEN 1 WHEN name LIKE '% ".$myURLSearchquery." %'  THEN 2 WHEN name LIKE '% ".$myURLSearchquery."' THEN 3 WHEN name LIKE '".$myURLSearchquery."_%' THEN 4 WHEN name LIKE '%".$myURLSearchquery."%' THEN 5 WHEN name LIKE '".substr($myURLSearchquery, 0, strlen($myURLSearchquery)-1)." %' THEN 6 WHEN name LIKE '% ".substr($myURLSearchquery, 0, strlen($myURLSearchquery)-1)." %' THEN 7 WHEN name LIKE '% ".substr($myURLSearchquery, 0, strlen($myURLSearchquery)-1)."' THEN 8 ELSE 9 END, name";
				}else{
					
					//---if search query contains multiple words, prepare Cases For ORDER BY Clause....
					$orderBy  .= "CASE ";
					if(isset($myQueryCases1)!=""){
						$orderBy  .= "WHEN name = '".$myQueryCases1."' THEN 1 ";
					}
					if(isset($myQueryCases2)!=""){
						$orderBy  .= "WHEN name LIKE '".$myQueryCases2."' THEN 2 ";
					}
					if(isset($myQueryCases3)!=""){
						$orderBy  .= "WHEN name LIKE '".$myQueryCases3."' THEN 3 ";
					}
					if(isset($myQueryCases4)!=""){
						$orderBy  .= "WHEN name LIKE '".$myQueryCases4."' THEN 4 ";
					}
					
					//--- Algorithm to make all possible combinitions of Multi worded query..
					$a = $myURLSearchqueryArray;
					$len  = count($a);
					$list = array();

					for($i = 1; $i< (1 << $len); $i++) {
						$c = '';
						for($j = 0; $j < $len; $j++)
							if($i & (1 << $j))
								$c .= $a[$j]." ";
						$list[] = $c;
					}
					
					//--- Sort the Combinition Array on the basis of length...
					usort($list, function($a, $b) {
						return strlen($b) - strlen($a);
					});

					//echo "<pre>"; print_r($list);
					$caseCount = 5;
					$orderBy5 = " WHEN ";
					$orderBy6 = " WHEN ";
					$orderBy7 = " WHEN ";
					$wordCount = 0;
					foreach($list as $l){
						//
						$orderBy1 = " WHEN";
						$orderBy2 = " WHEN";
						$orderBy3 = " WHEN";
						$orderBy4 = " WHEN";
						
						$wordCount++;	
						if($wordCount==1 ){
							//$orderBy5 .= ' WHEN ';
							//echo 'zee-->WHEN name like "'.$myURLSearchquery.'%" THEN '.$caseCount++;die; 
							$orderBy5 .= 'name like "'.$myURLSearchqueryArray[0].'%" AND ';
							$orderBy7 .= 'name like "% '.$myURLSearchqueryArray[0].' %" AND ';
						}
						
						$termArray = explode(' ',trim($l));
						foreach($termArray as $ta){
							$orderBy1  .=  ' name LIKE "% '.$ta.' %" AND ';
							//$orderBy2  .=  ' name LIKE "'.$ta.' %" AND ';
							//$orderBy3  .=  ' name LIKE " '.$ta.'%" AND ';
							
							//--- Single word ' WHOLE WORD ' CASE ... 
							if(count($termArray)==1){
								$orderBy .= ' WHEN name LIKE "% '.$ta.'"  OR name LIKE " '.$ta.'%" THEN '.$caseCount++;;
							}
							
							$orderBy4  .=  ' name LIKE "%'.$ta.'%"  AND ';
							
							if($wordCount==1 && $ta!=$myURLSearchqueryArray[0]){
								$orderBy5  .=  ' name like "% '.$ta.' %" AND ';
								$orderBy7  .=  ' name like "%'.$ta.'%" AND ';
							}
							if($wordCount==1 && $ta!=$myURLSearchqueryArray[count($myURLSearchqueryArray)-1]){
								$orderBy6 .=  ' name like "% '.$ta.' %" AND ';
							}
						}
						
						$orderBy1 = substr($orderBy1,0,strlen($orderBy1)-5);
						//$orderBy2 = substr($orderBy2,0,strlen($orderBy2)-5);
						//$orderBy3 = substr($orderBy3,0,strlen($orderBy3)-5);
						
						
						
						$orderBy .= $orderBy1.' THEN '.$caseCount++;
						//$orderBy .= $orderBy2.' THEN '.$caseCount++;
						//$orderBy .= $orderBy3.' THEN '.$caseCount++;
						//$orderBy .= $orderBy4.' THEN '.$caseCount++;
						
						if($wordCount==1){
							$orderBy5 = substr($orderBy5,0,strlen($orderBy5)-4);
							//echo 'zee-->WHEN name like "'.$myURLSearchquery.'%" THEN '.$caseCount++;die; 
							$orderBy5 .= " THEN ".$caseCount++;
							$orderBy .= $orderBy5;
							
							$orderBy6 .=  ' name like "% '.$myURLSearchqueryArray[count($myURLSearchqueryArray)-1].'" AND ';
							$orderBy6 = substr($orderBy6,0,strlen($orderBy6)-5);
							
							$orderBy6 .= " THEN ".$caseCount++;
							$orderBy  .= $orderBy6;
							
							$orderBy7 = substr($orderBy7,0,strlen($orderBy7)-4);
							//echo 'zee-->WHEN name like "'.$myURLSearchquery.'%" THEN '.$caseCount++;die; 
							$orderBy7 .= " THEN ".$caseCount++;
							$orderBy .= $orderBy7;
						}
							
						$orderBy4 = substr($orderBy4,0,strlen($orderBy4)-5);
						$orderBy .= $orderBy4.' THEN '.$caseCount++;
					}
					
					$orderBy  .= " ELSE ".$caseCount." END, name";
					
					//--- After getting Collection, Add Sorting Cases....
				}
		//--- DISABLED CLAUSE ATM...
		//$collection->addFieldToFilter('name',$searchParamsArray);
				if($myURLSearchOrder == "relevance" || !isset($myURLSearchOrder)){
						$collection->getSelect()->order(new Zend_Db_Expr($orderBy));
					}else{
						if($myURLSearchOrder == "price"){
							$tablePrefix = 'price_index';
						}elseif($myURLSearchOrder == "name"){
							$tablePrefix = $table;
						}elseif($myURLSearchOrder == "length"){
							$tablePrefix = $table;
						}elseif($myURLSearchOrder == "position"){
							$tablePrefix = 'catalog_category_product';
						} 
						
						//$myURLSearchOrderClause = $myURLSearchOrder == "position"?$myURLSearchOrder:$tablePrefix.'.'.$myURLSearchOrder;
						$myURLSearchOrderClause = $tablePrefix.'.'.$myURLSearchOrder;
						$myURLSearchOrderClause .= " ".$myURLSearchOrderDir;
						//$collection->getSelect()->order(new Zend_Db_Expr($myURLSearchOrderClause));
					}
			
			
        }
		
		//$collection->addFieldToFilter('name',$searchParamsArray);
		//echo $collection->getSelect(); 
        return $this;
    }
}