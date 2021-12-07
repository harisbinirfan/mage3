<?php
/**
 * Adminhtml customer grid block
 *
 * @category   Tejar
 * @package    Tejar_Adminhtml
 * @author     Zeeshan <zeeshan.zeeshan123@gmail.com>
 */
class Tejar_Adminhtml_Block_Catalog_Product_Grid extends Mage_Adminhtml_Block_Catalog_Product_Grid
{

	protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = Mage::getModel('catalog/product')->getCollection()
			->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
			->addAttributeToSelect('manufacturer')
			->addAttributeToSelect('model')
			->addAttributeToSelect('sourcing');

			if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
			            Mage::getModel('cataloginventory/stock_item')->addCatalogInventoryToProductCollection($collection);
			        }

        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $collection->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
        }

				$collection->joinField('types',
					'catalog/product_relation',
					// 'IF(at_types.parent_id IS NULL, e.type_id, "associate")',
					'IF(count(at_types.parent_id) > 1, "multiple_associate" , IF(at_types.parent_id IS NULL, e.type_id, "associate"))',
					'child_id=entity_id',
					null,
				'left');

				$customQuery = "(SELECT entity_id, group_concat(Distinct category_id SEPARATOR ',') as category_ids FROM ( ";
				$customQuery .= "SELECT `e`.entity_id, `at_category_id`.`category_id` ";
				$customQuery .= "FROM `{$collection->getTable('catalog/product')}` AS `e` ";
				$customQuery .= "LEFT JOIN `{$collection->getTable('catalog/category_product')}` AS `at_category_id` ";
				$customQuery .= "ON (at_category_id.`product_id`=e.entity_id) ";
				$customQuery .= ")  sub_query ";
				$customQuery .= "GROUP BY entity_id)";

				$collection->joinField('category_ids',
				new Zend_Db_Expr($customQuery),
					'category_ids',
					'entity_id=entity_id',
					null,
				'left');

        if ($store->getId()) {
            $adminStore = Mage_Core_Model_App::ADMIN_STORE_ID;
            $collection->addStoreFilter($store);
            $collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $adminStore
            );
            $collection->joinAttribute(
                'custom_name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );

						$collection->joinAttribute(
									'sourcing',
									'catalog_product/sourcing',
									'entity_id',
									null,
									'left',
									$store->getId()
							);

		    		$collection->joinAttribute(
									'manufacturer',
									'catalog_product/manufacturer',
									'entity_id',
									null,
									'inner',
									$store->getId()
							);

							$collection->joinAttribute(
				                'model',
				                'catalog_product/model',
				                'entity_id',
				                null,
				                'left',
				                $store->getId()
				            );

            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'price',
                'catalog_product/price',
                'entity_id',
                null,
                'left',
                $store->getId()
            );
						$collection->joinAttribute(
								'special_price',
								'catalog_product/special_price',
								'entity_id',
								null,
								'left',
								$store->getId()
						);
        }
        else {
            $collection->addAttributeToSelect('price');
						$collection->addAttributeToSelect('special_price');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');

        }

				$collection->getSelect()->group('e.entity_id');
        $this->setCollection($collection);

        Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
        $this->getCollection()->addWebsiteNamesToResult();
        return $this;
    }

	protected function _filterHasUrlAttributesCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}

		if($value){
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$attribute = $this->getCollection()->getAttribute($value);
			$attributeId = $attribute->getAttributeId();
			$attributeTable = $attribute->getBackendTable();
			if(!$storeId = $this->getRequest()->getParam('store')){
					$storeId = 0;
			}
			$select = $readAdapter->select()->from(array('e' => $attributeTable), array('entity_id' => 'e.entity_id'))
			->where("e.attribute_id = {$attributeId} AND e.store_id = {$storeId} and e.value IS NOT NULL");
			$productIds = "";
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		}

	}

	/*
	* Override this funciton to make last Column 'Action' sortable with 'updated_at' value in Admin Product Grid...
	*@name   _prepareColumns
	*@parms   none
	*/
    protected function _prepareColumns()
    {
        $this->addColumn('entity_id',
            array(
                'header'=> Mage::helper('catalog')->__('ID'),
                'width' => '50px',
                'type'  => 'number',
                'index' => 'entity_id',
        ));
        $this->addColumn('name',
            array(
                'header'=> Mage::helper('catalog')->__('Name'),
                'index' => 'name',
        ));

        $store = $this->_getStore();
        if ($store->getId()) {
            $this->addColumn('custom_name',
                array(
                    'header'=> Mage::helper('catalog')->__('Name in %s', $store->getName()),
                    'index' => 'custom_name',
            ));
        }

        // $this->addColumn('type',
        //     array(
        //         'header'=> Mage::helper('catalog')->__('Type'),
        //         'width' => '60px',
        //         'index' => 'type_id',
        //         'type'  => 'options',
        //         'options' => Mage::getSingleton('catalog/product_type')->getOptionArray(),
        // ));

				$types = Mage::getSingleton('catalog/product_type')->getOptionArray();
		$types["associate"] = "Associated Product";
		$types["multiple_associate"] = "Multiple Associated";
        $this->addColumn('type',
            array(
                'header'=> Mage::helper('catalog')->__('Type'),
                'width' => '60px',
                'index' => 'types',
				'filter_index' => 'type_id',
                'type'  => 'options',
                'options' => $types,
				'filter_condition_callback' => array($this, '_filterHasUrlConditionCallback')
        ));

				$customFilterArray = array();
		$customFilterArray["no_last_category"] = "Last Category Not Selected";
		$customFilterArray["multiple_select_last_child"] = "Multiple Last Category Selected";
		$customFilterArray["no_brand_seleted"] = "Brand Category Not Selected";
		$customFilterArray["multiple_select_brand"] = "Mutiple Brand Category Selected";
		$customFilterArray["parent_category_seleted"] = "Parent Category Selected";
		$customFilterArray["attribute_set_and_category_match"] = "Attributes Set and Category Mismatch";
		$customFilterArray["attribute_set_product_match"] = "Associated and Configurable Attribute Set Mismatch";
		$customFilterArray["empty_configurable"] = "Empty Configurable";
		$customFilterArray["configurable_and_associated_stock_mismatch"] = "Associated and Configurable Stock Mismatch";
		$customFilterArray["associate_out_of_stock"] = "Associated Disabled or Not Visible on Website";
		$customFilterArray["disabled_mismatch"] = "Associated and Configurable Status Mismatch Both";
		$customFilterArray["product_name_mistmatch_brand"] = "Incorrect Name Both";
		$customFilterArray["empty_description"] = "Irrelevant Description Both";
		$customFilterArray["empty_short_description"] = "Irrelevant Short Description Both";
		$customFilterArray["disallowed_model"] = "Incorrect Model Both";
		$customFilterArray["duplicate_model"] = "Duplicate Model Both";
		$customFilterArray["sourcing_unchecked_list_price_is_checked"] = "Sourcing Unchecked and Other Attributes Checked Both";
		$customFilterArray["sku_model_match"] = "SKU and Model Same Both";
		$customFilterArray["check_unchecked_fields"] = "Unchecked Attributes Store";
		$customFilterArray["check_status"] = "Unchecked Status Store";
		$this->addColumn('custom_filter',
            array(
                'header'=> Mage::helper('catalog')->__('Filter'),
                'width' => '70px',
                'type'  => 'options',
                'options' => $customFilterArray,
				'filter_condition_callback' => array($this, '_filterHasUrlCustomFilterCallback')
        ));

        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
            ->load()
            ->toOptionHash();

        $sets2 = array();
        		$attributeOptions = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'manufacturer');
        		$attributeOptions = $attributeOptions->getSource()->getAllOptions(false);
        		foreach ($attributeOptions as $key=>$attributeOption) {
        			$sets2[$attributeOption['value']] = $attributeOption['label'];
        		}


        $sets3 = array();
        		$attributeOptions = Mage::getSingleton('eav/config')->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'sourcing');
        		$attributeOptions = $attributeOptions->getSource()->getAllOptions(false);
        		foreach ($attributeOptions as $key=>$attributeOption) {
        			$sets3[$attributeOption['value']] = $attributeOption['label'];
        		}

        $this->addColumn('set_name',
            array(
                'header'=> Mage::helper('catalog')->__('Attribute Sets'),
                'width' => '100px',
                'index' => 'attribute_set_id',
                'type'  => 'options',
                'options' => $sets,
		));

		$attributeCollection = array();
		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$query = $readConnection->select()
			->from('eav_attribute')
			->where('entity_type_id = ?', 4);
			// ->where('backend_type = ?', "int");
		// $query->reset(Zend_Db_Select::COLUMNS);
		// $query->columns(array('attribute_id','attribute_code'));
		$results = $readConnection->fetchAll($query);
		foreach($results as $row){
			if($row['frontend_label']){
				$attributeCollection[$row['attribute_code']] = $row['frontend_label'];
			} else {
				$attributeCollection[$row['attribute_code']] = $row['attribute_code'];
			}
		}
		$this->addColumn('attributes',
			array(
				'header'=> Mage::helper('catalog')->__('Attributes'),
				'width' => '70px',
				'type'  => 'options',
				'options' => $attributeCollection,
				'filter_condition_callback' => array($this, '_filterHasUrlAttributesCallback')
		
			)
		);
		
		$_categories = Mage::helper('tejar_adminhtml')->getCategoryTree();
		$this->addColumn('category_ids',
            array(
                'header'=> Mage::helper('catalog')->__('Categories'),
                'width' => '200px',
				'filter_index' => 'category_id',
                'index' => 'category_ids',
                'type'  => 'options',
				'renderer' => 'tejar_adminhtml/catalog_product_edit_tab_category_grid_column_renderer_used',
                'options' => $_categories,
				'filter_condition_callback' => array($this, '_filterHasCategoryConditionCallback')
        ));

        $this->addColumn('manufacturer',
                array(
                    'header'=> Mage::helper('catalog')->__('Manufacturer'),
                    'width' => '100px',
                    'index' => 'manufacturer',
                    'type'  => 'options',
                    'options' => $sets2,
            ));

    		$this->addColumn('sourcing',
                array(
                    'header'=> Mage::helper('catalog')->__('Sourcing'),
                    'width' => '100px',
                    'index' => 'sourcing',
                    'type'  => 'options',
                    'options' => $sets3,
            ));

        $this->addColumn('sku',
            array(
                'header'=> Mage::helper('catalog')->__('SKU'),
                'width' => '80px',
                'index' => 'sku',
        ));

				$this->addColumn('model',
		            array(
		                'header'=> Mage::helper('catalog')->__('Model'),
		                'index' => 'model',
		        ));

        $store = $this->_getStore();
        $this->addColumn('price',
            array(
                'header'=> Mage::helper('catalog')->__('Price'),
                'type'  => 'price',
                'currency_code' => $store->getBaseCurrency()->getCode(),
                'index' => 'price',
        ));

				$this->addColumn('special_price',
						array(
								'header'=> Mage::helper('catalog')->__('Special Price'),
								'type'  => 'price',
								'currency_code' => $store->getBaseCurrency()->getCode(),
								'index' => 'special_price',
				));

        if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
            $this->addColumn('qty',
                array(
                    'header'=> Mage::helper('catalog')->__('Qty'),
                    'width' => '100px',
                    'type'  => 'number',
                    'index' => 'qty',
            ));
        }

        $this->addColumn('visibility',
            array(
                'header'=> Mage::helper('catalog')->__('Visibility'),
                'width' => '70px',
                'index' => 'visibility',
                'type'  => 'options',
                'options' => Mage::getModel('catalog/product_visibility')->getOptionArray(),
        ));

				$this->addColumn('is_saleable', array(
		            'header'    => Mage::helper('catalog')->__('Inventory'),
		            'renderer'  => 'adminhtml/catalog_product_edit_tab_super_config_grid_renderer_inventory',
		            'filter'    => 'adminhtml/catalog_product_edit_tab_super_config_grid_filter_inventory',
		            'index'     => 'is_saleable'
		        ));

        $this->addColumn('status',
            array(
                'header'=> Mage::helper('catalog')->__('Status'),
                'width' => '70px',
                'index' => 'status',
                'type'  => 'options',
                'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
        ));

        // if (!Mage::app()->isSingleStoreMode()) {
        //     $this->addColumn('websites',
        //         array(
        //             'header'=> Mage::helper('catalog')->__('Websites'),
        //             'width' => '100px',
        //             'sortable'  => false,
        //             'index'     => 'websites',
        //             'type'      => 'options',
        //             'options'   => Mage::getModel('core/website')->getCollection()->toOptionHash(),
        //     ));
        // }

				if (!Mage::app()->isSingleStoreMode()) {
			$websites = Mage::getModel('core/website')->getCollection()->toOptionHash();
			foreach($websites as $key => $website){
				$websites[$key+$key] = "Not in " . $website;
			}
            $this->addColumn('websites',
                array(
                    'header'=> Mage::helper('catalog')->__('Websites'),
                    'width' => '100px',
                    'sortable'  => false,
                    'index'     => 'websites',
                    'type'      => 'options',
                    'options'   => $websites,
					'filter_condition_callback' => array($this, '_filterHasUrlWebsitesCallback')
            ));
        }

				$this->addColumn('updated_at',

						array(
								'type'      => 'datetime',
								'filter_time' => true,
								'gmtoffset' => true,
								'header'=> Mage::helper('catalog')->__('Updated at'),
								'width' => '100px',
								'index' => 'updated_at',
						)
					);

				$this->addColumn('created_at',
					array(
						'type'      => 'datetime',
						'filter_time' => true,
						'gmtoffset' => true,
						'header'=> Mage::helper('catalog')->__('Created at'),
						'width' => '100px',
						'index' => 'created_at',
					)
				);


					$this->addColumn('action',
									array(
											'header'    => Mage::helper('catalog')->__('Action'),
											'width'     => '50px',
											'type'      => 'action',
											'getter'     => 'getId',
											'actions'   => array(
													array(
															'caption' => Mage::helper('catalog')->__('Edit'),
															'url'     => array(
																	'base'=>'*/*/edit',
																	'params'=>array('store'=>$this->getRequest()->getParam('store'))
															),
															'field'   => 'id'
													)
											),
											'filter'    => false,
											'sortable'  => false,
											'index'     => 'stores',
							));

        if (Mage::helper('catalog')->isModuleEnabled('Mage_Rss')) {
            $this->addRssList('rss/catalog/notifystock', Mage::helper('catalog')->__('Notify Low Stock RSS'));
        }

        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
	}
	
	protected function _filterHasCategoryConditionCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		if($value){
			$productIds = array();
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$select = $readAdapter->select()->from(array('e' => 'catalog_category_product'), array('product_id'))->where("(e.category_id = ".$value.")");
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['product_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		}
		return $this;
	}

		/**
     * Retrieve Core addLikeEscape
     *
     * @return Mage_Core_Model_Resource_Helper_Mysql4
	*/
    public function getExprHelper()
    {
        return Mage::getResourceHelper('core');
    }

		protected function _filterHasUrlCustomFilterCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		$collection = Mage::getResourceModel('catalog/category_collection');
		$collectionType = $collection->getAttribute('collection_type')->getAttributeId();
		if($value === "no_last_category") {
				$readAdapter = $this->getCollection()->getConnection('core_read');
				$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'))
				->joinInner(array('at_category_product' => 'catalog_category_product'),"(`e`.`entity_id` = `at_category_product`.`product_id` )", array())
				->joinInner(array('at_catalog_category' => 'catalog_category_entity'),"(`at_catalog_category`.`entity_id` = `at_category_product`.`category_id` ) AND (at_catalog_category.children_count = 0)", array())
				->joinLeft(array('at_collection_type' => 'catalog_category_entity_int'),"(`at_collection_type`.`entity_id` = `at_catalog_category`.`entity_id` ) AND ( `at_collection_type`.`attribute_id` = '".$collectionType."' ) AND ( `at_collection_type`.`store_id` = 0)", array())
				->where("(at_collection_type.value IS NULL)");
				$productIds = array();
				foreach ($readAdapter->fetchAll($select) as $row) {
					$productIds[] = $row['entity_id'];
				}
				if(!empty($productIds)){
					$this->getCollection()->getSelect()->where("(e.entity_id NOT IN(". join(',',$productIds) ."))");
				} else {
					$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
				}
		}	else if($value === "parent_category_seleted") {
				$readAdapter = $this->getCollection()->getConnection('core_read');
				$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'))
				->joinInner(array('at_category_product' => 'catalog_category_product'),"(`e`.`entity_id` = `at_category_product`.`product_id` )", array())
				->joinInner(array('at_catalog_category' => 'catalog_category_entity'),"(`at_catalog_category`.`entity_id` = `at_category_product`.`category_id` ) AND (at_catalog_category.children_count != 0)", array())
				->joinLeft(array('at_collection_type' => 'catalog_category_entity_int'),"(`at_collection_type`.`entity_id` = `at_catalog_category`.`entity_id` ) AND ( `at_collection_type`.`attribute_id` = '".$collectionType."' ) AND ( `at_collection_type`.`store_id` = 0)", array())
				->where("(at_collection_type.value IS NULL)");
				$productIds = array();
				foreach ($readAdapter->fetchAll($select) as $row) {
					$productIds[] = $row['entity_id'];
				}
				if(!empty($productIds)){
					$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
				} else {
					$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
				}
		} else if($value === "no_brand_seleted"){
				$readAdapter = $this->getCollection()->getConnection('core_read');
				$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'))
				->joinInner(array('at_category_product' => 'catalog_category_product'),"(`e`.`entity_id` = `at_category_product`.`product_id` )", array())
				->joinInner(array('at_catalog_category' => 'catalog_category_entity'),"(`at_catalog_category`.`entity_id` = `at_category_product`.`category_id` )", array())
				->joinInner(array('at_collection_type' => 'catalog_category_entity_int'),"(`at_collection_type`.`entity_id` = `at_catalog_category`.`entity_id`) AND ( `at_collection_type`.`attribute_id` = '".$collectionType."' ) AND ( `at_collection_type`.`store_id` = 0) AND ( `at_collection_type`.`value` = 1)", array());
				$productIds = array();
				foreach ($readAdapter->fetchAll($select) as $row) {
					$productIds[] = $row['entity_id'];
				}
				if(!empty($productIds)){
					$this->getCollection()->getSelect()->where("(e.entity_id NOT IN(". join(',',$productIds) ."))");
				} else {
					$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
				}
		} else if($value === "multiple_select_last_child"){
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'))
			->joinInner(array('at_category_product' => 'catalog_category_product'),"(`e`.`entity_id` = `at_category_product`.`product_id` )", array())
			->joinInner(array('at_catalog_category' => 'catalog_category_entity'),"(`at_catalog_category`.`entity_id` = `at_category_product`.`category_id` )  AND (at_catalog_category.children_count = 0)", array())
			->joinLeft(array('at_collection_type' => 'catalog_category_entity_int'),"(`at_collection_type`.`entity_id` = `at_catalog_category`.`entity_id`) AND ( `at_collection_type`.`attribute_id` = '".$collectionType."' ) AND ( `at_collection_type`.`store_id` = 0)", array())
			->where("(at_collection_type.value IS NULL)")
			->group('e.entity_id')
			->having('count(length(at_catalog_category.entity_id)) > 1');
			$productIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		} else if($value === "multiple_select_brand"){
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'))
			->joinInner(array('at_category_product' => 'catalog_category_product'),"(`e`.`entity_id` = `at_category_product`.`product_id` )", array())
			->joinInner(array('at_catalog_category' => 'catalog_category_entity'),"(`at_catalog_category`.`entity_id` = `at_category_product`.`category_id` )", array())
			->joinLeft(array('at_collection_type' => 'catalog_category_entity_int'),"(`at_collection_type`.`entity_id` = `at_catalog_category`.`entity_id`) AND ( `at_collection_type`.`attribute_id` = '".$collectionType."' ) AND ( `at_collection_type`.`store_id` = 0)", array())
			->where("(at_collection_type.value = 1) and (at_catalog_category.level = 2)")
			->group('e.entity_id')
			->having('count(length(at_catalog_category.entity_id)) > 1');
			$productIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		}	else if($value === "empty_short_description") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$attribute = $this->getCollection()->getAttribute('short_description');
			$attributeId = $attribute->getAttributeId();
			$attributeTable = $attribute->getBackendTable();
			if(!$storeId = $this->getRequest()->getParam('store')){
					$storeId = 0;
			}
			$select = $readAdapter->select()->from(array('e' => $attributeTable), array('entity_id' => 'e.entity_id'))
			->where("e.attribute_id = {$attributeId} AND e.store_id = {$storeId} AND ((LENGTH(e.value) < 15) OR (
			(e.value LIKE '<div%') OR (e.value LIKE '%div>') OR
			(e.value LIKE '<br%') OR (e.value LIKE '%br>') OR 
			(e.value LIKE '<h%') OR (e.value LIKE '%h_>') OR
			((e.value NOT LIKE '<ul>%') AND (e.value NOT LIKE '<p>%')) OR 
			((e.value NOT LIKE '%</ul>') AND (e.value NOT LIKE '%</p>')) OR
			(e.value LIKE '%<h2></h2>%') OR 
			(e.value LIKE '%<h3></h3>%') OR 
			(e.value LIKE '%<h4></h4>%') OR 
			(e.value LIKE '%<h5></h5>%') OR 
			(e.value LIKE '%<p></p>%') OR 
			(e.value LIKE '%<ul></ul>%') OR 
			(e.value LIKE '%<li></li>%') OR 
			(e.value LIKE '%<ul><li></li></ul>%') OR 
			(e.value LIKE '%<div></div>%') OR
			(e.value LIKE '%<h2> </h2>%') OR 
			(e.value LIKE '%<h3> </h3>%') OR 
			(e.value LIKE '%<h4> </h4>%') OR 
			(e.value LIKE '%<h5> </h5>%') OR 
			(e.value LIKE '%<p> </p>%') OR 
			(e.value LIKE '%<ul> </ul>%') OR 
			(e.value LIKE '%<li> </li>%') OR 
			(e.value LIKE '%<ul> <li></li></ul>%') OR 
			(e.value LIKE '%<ul><li> </li></ul>%') OR 
			(e.value LIKE '%<ul><li></li> </ul>%') OR 
			(e.value LIKE '%<ul> <li> </li></ul>%') OR 
			(e.value LIKE '%<ul><li> </li> </ul>%') OR 
			(e.value LIKE '%<ul> <li></li> </ul>%') OR 
			(e.value LIKE '%<ul> <li> </li> </ul>%') OR
			(e.value LIKE '%<div> </div>%')  
			)) AND e.value IS NOT NULL");
			$productIds = "";
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}	
		}	else if($value === "empty_description") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$attribute = $this->getCollection()->getAttribute('description');
			$attributeId = $attribute->getAttributeId();
			$attributeTable = $attribute->getBackendTable();
			if(!$storeId = $this->getRequest()->getParam('store')){
					$storeId = 0;
			}
			$select = $readAdapter->select()->from(array('e' => $attributeTable), array('entity_id' => 'e.entity_id'))
			->where("e.attribute_id = {$attributeId} AND e.store_id = {$storeId} AND ((LENGTH(e.value) < 15) OR (
			(e.value LIKE '<div%') OR (e.value LIKE '%div>') OR
			(e.value LIKE '<br%') OR (e.value LIKE '%br>') OR 
			((e.value NOT LIKE '<h_>%') AND (e.value NOT LIKE '<p>%') AND (e.value NOT LIKE '<ul>%')) OR 
			((e.value NOT LIKE '%</h_>') AND (e.value NOT LIKE '%</p>') AND (e.value NOT LIKE '%</ul>')) OR
			(e.value LIKE '%<h2></h2>%') OR 
			(e.value LIKE '%<h3></h3>%') OR 
			(e.value LIKE '%<h4></h4>%') OR 
			(e.value LIKE '%<h5></h5>%') OR 
			(e.value LIKE '%<p></p>%') OR 
			(e.value LIKE '%<ul></ul>%') OR 
			(e.value LIKE '%<li></li>%') OR 
			(e.value LIKE '%<ul><li></li></ul>%') OR 
			(e.value LIKE '%<div></div>%') OR
			(e.value LIKE '%<h2> </h2>%') OR 
			(e.value LIKE '%<h3> </h3>%') OR 
			(e.value LIKE '%<h4> </h4>%') OR 
			(e.value LIKE '%<h5> </h5>%') OR 
			(e.value LIKE '%<p> </p>%') OR 
			(e.value LIKE '%<ul> </ul>%') OR 
			(e.value LIKE '%<li> </li>%') OR 
			(e.value LIKE '%<ul> <li></li></ul>%') OR 
			(e.value LIKE '%<ul><li> </li></ul>%') OR 
			(e.value LIKE '%<ul><li></li> </ul>%') OR 
			(e.value LIKE '%<ul> <li> </li></ul>%') OR 
			(e.value LIKE '%<ul><li> </li> </ul>%') OR 
			(e.value LIKE '%<ul> <li></li> </ul>%') OR 
			(e.value LIKE '%<ul> <li> </li> </ul>%') OR 
			(e.value LIKE '%<div> </div>%')  
			)) AND e.value IS NOT NULL");
			$productIds = "";
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			$attribute = $this->getCollection()->getAttribute('in_the_box');
			$attributeId = $attribute->getAttributeId();
			$attributeTable = $attribute->getBackendTable();
			$select = $readAdapter->select()->from(array('e' => $attributeTable), array('entity_id' => 'e.entity_id'))
			->where("e.attribute_id = {$attributeId} AND e.store_id = {$storeId} AND ((LENGTH(e.value) < 15) OR (
			(e.value LIKE '<div%') OR (e.value LIKE '%div>') OR
			(e.value LIKE '<h%') OR (e.value LIKE '%h_>') OR
			(e.value LIKE '<br%') OR (e.value LIKE '%br>') OR 
			((e.value NOT LIKE '<ul>%') AND (e.value NOT LIKE '<p>%')) OR 
			((e.value NOT LIKE '%</ul>') AND (e.value NOT LIKE '%</p>'))
			)) AND e.value IS NOT NULL");
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}	
		} else if($value === "sourcing_unchecked_list_price_is_checked") {
			
			$readAdapter = $this->getCollection()->getConnection('core_read');
			
			$attribute = $this->getCollection()->getAttribute('sourcing');
			$attributeId = $attribute->getAttributeId();
			$attributeTable = $attribute->getBackendTable();
			$attributeCode = $attribute->getAttributeCode();
			
			$productIds = array();
			$inThisAttributes = array('list_price','model','warranty','special_from_date','special_to_date');
			if(!$storeId = $this->getRequest()->getParam('store')){
					$storeId = 0;
			}
			
			foreach($inThisAttributes as $attrCode){
				$listPriceAttribute = $this->getCollection()->getAttribute($attrCode);
				$listPriceAttributeId = $listPriceAttribute->getAttributeId();
				$listPriceAttributeTable = $listPriceAttribute->getBackendTable();
				$listPriceAttributeCode = $listPriceAttribute->getAttributeCode();
				
				$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'=>'e.entity_id'))
					->joinLeft(array("at_{$attributeCode}" => $attributeTable),"(`at_{$attributeCode}`.`entity_id` = `e`.`entity_id`) AND (`at_{$attributeCode}`.`attribute_id` = {$attributeId}) AND `at_{$attributeCode}`.`store_id` = {$storeId}",array())
					->joinLeft(array("at_{$listPriceAttributeCode}" => $listPriceAttributeTable),"(`at_{$listPriceAttributeCode}`.`entity_id` = `e`.`entity_id`) AND (`at_{$listPriceAttributeCode}`.`attribute_id` = {$listPriceAttributeId}) AND `at_{$listPriceAttributeCode}`.`store_id` = {$storeId}",array())
					->where("e.entity_id IS NOT NULL AND at_{$attributeCode}.value_id > 0 and at_{$listPriceAttributeCode}.value_id IS NULL");

			
				
				foreach ($readAdapter->fetchAll($select) as $row) {
					if($row['entity_id'] && !empty($row['entity_id'])){
						if (!in_array($row['entity_id'], $productIds)) {
							$productIds[] = $row['entity_id'];
						}
					}
				}
			}
			
			if(!empty($productIds)){
				$productIds = join(',',$productIds);
				// $productIds = preg_replace('/\,{2,}/', ',', $productIds);
				// $productIds = preg_replace('/\,$/', '', $productIds);
				// $productIds = preg_replace('/^\,/', '', $productIds);
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". $productIds ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}	
		}	else if($value === "sku_model_match") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$attribute = $this->getCollection()->getAttribute('model');
			$attributeId = $attribute->getAttributeId();
			$attributeTable = $attribute->getBackendTable();
			if(!$storeId = $this->getRequest()->getParam('store')){
					$storeId = 0;
			}
			
			
			$select = $readAdapter->select()->from(array('e' => "catalog_product_entity"), array('entity_id' => 'e.entity_id'))
			->joinInner(array('at_defaul_model' => $attributeTable),"(`at_defaul_model`.`attribute_id` = {$attributeId}) AND (`at_defaul_model`.`entity_id` = `e`.`entity_id`)  AND (`at_defaul_model`.`store_id` = 0)", array())
			->joinLeft(array('at_model' => $attributeTable),"(`at_model`.`attribute_id` = {$attributeId}) AND (`at_model`.`entity_id` = `e`.`entity_id`) AND (`at_model`.`store_id` = {$storeId})", array())
			->where("(IF(at_model.value_id > 0, at_model.value, at_defaul_model.value) = e.sku)");
			
			
			
			$productIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}	
		}	else if($value === "duplicate_model") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$attribute = $this->getCollection()->getAttribute('model');
			$attributeId = $attribute->getAttributeId();
			$attributeTable = $attribute->getBackendTable();
			if(!$storeId = $this->getRequest()->getParam('store')){
					$storeId = 0;
			}
			$select = $readAdapter->select()->from(array('e' => $attributeTable), array('entity_id' => 'group_concat(e.entity_id)'))
			->where("e.attribute_id = {$attributeId} AND e.store_id = {$storeId}")
			->group("e.value")
			->having("count(e.value) > 1");
			$productIds = "";
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		}	else if($value === "disallowed_model") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$attribute = $this->getCollection()->getAttribute('model');
			$manufacturerAttribute = $this->getCollection()->getAttribute('manufacturer');
			$attributeId = $attribute->getAttributeId();
			$attributeManufacturerId = $manufacturerAttribute->getAttributeId();
			$attributeTable = $attribute->getBackendTable();
			if(!$storeId = $this->getRequest()->getParam('store')){
					$storeId = 0;
			}
			 $select = $readAdapter->select()->from(array('at_manufacturer' => $manufacturerAttribute->getBackendTable()), array('entity_id' => 'at_manufacturer.entity_id'))
				->joinInner(array('at_model' => $attributeTable),"(`at_model`.`attribute_id` = {$attributeId}) AND (`at_model`.`entity_id` = `at_manufacturer`.`entity_id`)", array())
				->joinInner(array('at_option' => 'eav_attribute_option'),"(`at_option`.`attribute_id` = `at_manufacturer`.`attribute_id` ) AND ( `at_option`.`option_id` = `at_manufacturer`.`value`)", array())
				->joinInner(array('at_option_default_value' => 'eav_attribute_option_value'),"(`at_option_default_value`.`option_id` = `at_option`.`option_id` ) AND ( `at_option`.`attribute_id` = {$attributeManufacturerId}) AND `at_option_default_value`.`store_id` = 0", array())
				->joinLeft(array('at_option_value' => 'eav_attribute_option_value'),"(`at_option_value`.`option_id` = `at_option`.`option_id` ) AND (`at_option`.`attribute_id` = {$attributeManufacturerId}) AND (`at_option_value`.`store_id` = {$storeId})", array())
				->where("at_manufacturer.attribute_id = {$attributeManufacturerId}")
				->where("(at_model.value  LIKE CONCAT(IF(at_option_value.value_id > 0, at_option_value.value, at_option_default_value.value),' %')) OR (at_model.value  LIKE CONCAT('% ',IF(at_option_value.value_id > 0, at_option_value.value, at_option_default_value.value),' %')) OR (at_model.value  LIKE CONCAT('% ',IF(at_option_value.value_id > 0, at_option_value.value, at_option_default_value.value)))")
				->group("at_manufacturer.entity_id"); 
			$productIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		} else if($value === "attribute_set_and_category_match"){
				if(!$storeId = $this->getRequest()->getParam('store')){
					$storeId = 0;
				}
				$readAdapter = $this->getCollection()->getConnection('core_read');
				$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'))
				->joinInner(array('at_attribute_set' => 'eav_attribute_set'),"(`e`.`attribute_set_id` = `at_attribute_set`.`attribute_set_id` )", array())
				->joinInner(array('at_category_product' => 'catalog_category_product'),"(`e`.`entity_id` = `at_category_product`.`product_id` )", array())
				->joinInner(array('at_catalog_category' => 'catalog_category_entity'),"(`at_catalog_category`.`entity_id` = `at_category_product`.`category_id`) AND (at_catalog_category.children_count = 0)", array())
				->joinLeft(array('at_collection_type' => 'catalog_category_entity_int'),"(`at_collection_type`.`entity_id` = `at_catalog_category`.`entity_id` ) AND ( `at_collection_type`.`attribute_id` = '".$collectionType."' ) AND ( `at_collection_type`.`store_id` = 0)", array())
				->joinInner(array('at_name_default' => 'catalog_category_entity_varchar'),"( `at_name_default`.`entity_id` = `at_catalog_category`.`entity_id` ) AND ( `at_name_default`.`attribute_id` = 41 ) AND `at_name_default`.`store_id` = 0", array())
				->joinLeft(array('at_name' => 'catalog_category_entity_varchar'),"( `at_name`.`entity_id` = `at_catalog_category`.`entity_id` ) AND (`at_name`.`attribute_id` = 41) AND (`at_name`.`store_id` = ".$storeId.")", array())
				->where("(at_collection_type.value IS NULL) AND (IF(at_name.value_id > 0, at_name.value, at_name_default.value) != at_attribute_set.attribute_set_name)");
				$productIds = array();
				foreach ($readAdapter->fetchAll($select) as $row) {
					$productIds[] = $row['entity_id'];
				}
				if(!empty($productIds)){
					$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
				} else {
					$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
				}
		} else if($value === "associate_out_of_stock"){
			if(!$storeId = $this->getRequest()->getParam('store')){
				$storeId = 1;
			}
			$this->getCollection()->getSelect()
			->joinLeft(array('stock_status' => $this->getCollection()->getTable('cataloginventory/stock_status')),'e.entity_id = stock_status.product_id AND stock_status.website_id="'.$storeId.'"')
			->where("(IF((IF(cisi.use_config_manage_stock = 1, 0, cisi.manage_stock) = 1), cisi.is_in_stock, 1) = 1) and (stock_status.stock_status = 0)");
		} else if($value === "attribute_set_product_match"){
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'))
			->joinLeft(array('at_relation' => 'catalog_product_relation'),"(`e`.`entity_id` = `at_relation`.`child_id` )", array('parent_id'))
			->joinLeft(array('at_dulipcate_product' => 'catalog_product_entity'),"( `at_relation`.`parent_id` = `at_dulipcate_product`.`entity_id` )", array())
			->where("(e.type_id = 'simple' AND at_relation.parent_id IS NOT NULL) AND e.attribute_set_id != at_dulipcate_product.attribute_set_id");
			$productIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				if(isset($row['parent_id']) && !in_array($row['parent_id'],$productIds)){
					$productIds[] = $row['parent_id'];
				}
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		} else if($value === "configurable_and_associated_stock_mismatch"){
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'=>'group_concat(e.entity_id)'))
			->joinInner(array('at_relation' => 'catalog_product_relation'),"(`e`.`entity_id` = `at_relation`.`child_id` )", array('parent_id'))
			->joinLeft(array('cisi' => 'cataloginventory_stock_item'),"( `e`.`entity_id` = `cisi`.`product_id` )", array())
			->joinLeft(array('dup_cisi' => 'cataloginventory_stock_item'),"( `at_relation`.`parent_id` = `dup_cisi`.`product_id` )", array())
			->where('(e.type_id = "simple")')
			->group('at_relation.parent_id')
			->having('(((sum((IF((IF(cisi.use_config_manage_stock = 1, 0, cisi.manage_stock) = 1), cisi.is_in_stock, 1))) < 1)) AND (SUM((IF((IF(dup_cisi.use_config_manage_stock = 1, 0, dup_cisi.manage_stock) = 1), dup_cisi.is_in_stock, 1))) = count(*))) OR  (((sum((IF((IF(cisi.use_config_manage_stock = 1, 0, cisi.manage_stock) = 1), cisi.is_in_stock, 1))) >= 1)) AND (SUM((IF((IF(dup_cisi.use_config_manage_stock = 1, 0, dup_cisi.manage_stock) = 1), dup_cisi.is_in_stock, 1))) < 1))');
			$productIds = array();
			$childIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				if(isset($row['parent_id']) && !in_array($row['parent_id'],$productIds)){
					$productIds[] = $row['parent_id'];
				}
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}
		} else if($value === "empty_configurable"){
			$this->getCollection()->getSelect()
			->joinLeft(array('relation' => $this->getCollection()->getTable('catalog/product_relation')),'e.entity_id = relation.parent_id')
			->where('e.type_id = "configurable" and relation.parent_id is null');

		} else if($value === "disabled_mismatch") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$attribute = $this->getCollection()->getAttribute('status');
			$attributeCode = $attribute->getAttributeCode();
			$attributeId = $attribute->getAttributeId();
			$table = $attribute->getBackendTable();
			$storeId = $this->_getStore()->getId();
			$select = $readAdapter->select()->from(array('e' => 'catalog_product_entity'), array('entity_id'=>'group_concat(e.entity_id)'))
			->joinInner(array('at_relation' => 'catalog_product_relation'),"(`e`.`entity_id` = `at_relation`.`child_id` )", array('parent_id'))
			->joinInner(array("at_{$attributeCode}_default" => $table),"(`at_{$attributeCode}_default`.`entity_id` = `e`.`entity_id`) AND (`at_{$attributeCode}_default`.`attribute_id` = {$attributeId}) AND `at_{$attributeCode}_default`.`store_id` = 0",array())
			->joinLeft(array("at_{$attributeCode}" => $table),"(`at_{$attributeCode}`.`entity_id` = `e`.`entity_id`) AND (`at_{$attributeCode}`.`attribute_id` = {$attributeId}) AND `at_{$attributeCode}`.`store_id` = {$storeId}",array())
			->joinInner(array("at_parent_{$attributeCode}_default" => $table),"(`at_parent_{$attributeCode}_default`.`entity_id` = `at_relation`.`parent_id`) AND (`at_parent_{$attributeCode}_default`.`attribute_id` = {$attributeId}) AND `at_parent_{$attributeCode}_default`.`store_id` = 0",array())
			->joinLeft(array("at_parent_{$attributeCode}" => $table),"(`at_parent_{$attributeCode}`.`entity_id` = `at_relation`.`parent_id`) AND (`at_parent_{$attributeCode}`.`attribute_id` = {$attributeId}) AND `at_parent_{$attributeCode}`.`store_id` = {$storeId}",array())
			->where("(e.type_id = 'simple') AND (IF(at_{$attributeCode}.value_id > 0, at_{$attributeCode}.value, at_{$attributeCode}_default.value)) != (IF(at_parent_{$attributeCode}.value_id > 0, at_parent_{$attributeCode}.value, at_parent_{$attributeCode}_default.value))")			
			->group('at_relation.parent_id');
			$productIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				if(isset($row['parent_id']) && !in_array($row['parent_id'],$productIds)){
					$productIds[] = $row['parent_id'];
				}
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}

		}	else if($value === "product_name_mistmatch_brand") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$nameAttribute = $this->getCollection()->getAttribute('name');
			$manufacturerAttribute = $this->getCollection()->getAttribute('manufacturer');
			$nameAttributeId = $nameAttribute->getAttributeId();
			$attributeManufacturerId = $manufacturerAttribute->getAttributeId();
			$nameAttributeTable = $nameAttribute->getBackendTable();
			$manufacturerAttributeTable = $manufacturerAttribute->getBackendTable();
			$mainTable = $this->getCollection()->getTable('catalog/product');
			if(!$storeId = $this->getRequest()->getParam('store')){
					$storeId = 0;
			}
			$select = $readAdapter->select()->from(array('e' => $mainTable), array('entity_id' => 'e.entity_id'))
				->joinInner(array('at_name_default' => $nameAttributeTable),"(`at_name_default`.`entity_id` = `e`.`entity_id` ) AND (`at_name_default`.`attribute_id` = {$nameAttributeId}) AND `at_name_default`.`store_id` = 0", array())
				->joinLeft(array('at_name' => $nameAttributeTable),"(`at_name`.`entity_id` = `e`.`entity_id` ) AND (`at_name`.`attribute_id` = {$nameAttributeId}) AND (`at_name`.`store_id` = {$storeId})", array())
				->joinInner(array('at_manufacturer' => $manufacturerAttributeTable),"(`at_manufacturer`.`attribute_id` = {$attributeManufacturerId}) AND (`e`.`entity_id` = `at_manufacturer`.`entity_id`)", array())
				->joinInner(array('at_option' => 'eav_attribute_option'),"(`at_option`.`attribute_id` = `at_manufacturer`.`attribute_id` ) AND ( `at_option`.`option_id` = `at_manufacturer`.`value`)", array())
				->joinInner(array('at_option_default_value' => 'eav_attribute_option_value'),"(`at_option_default_value`.`option_id` = `at_option`.`option_id` ) AND ( `at_option`.`attribute_id` = {$attributeManufacturerId}) AND `at_option_default_value`.`store_id` = 0", array())
				->joinLeft(array('at_option_value' => 'eav_attribute_option_value'),"(`at_option_value`.`option_id` = `at_option`.`option_id` ) AND (`at_option`.`attribute_id` = {$attributeManufacturerId}) AND (`at_option_value`.`store_id` = {$storeId})", array())
				->where('IF(at_name.value_id > 0, at_name.value, at_name_default.value) NOT LIKE concat(IF(at_option_value.value_id > 0, at_option_value.value, at_option_default_value.value)," %")');
			$productIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			}

		} else if($value === "check_status") {

			$readAdapter = $this->getCollection()->getConnection('core_read');
			$attribute = $this->getCollection()->getAttribute('status');
			$attributeCode = $attribute->getAttributeCode();
			$attributeId = $attribute->getAttributeId();
			$table = $attribute->getBackendTable();
			$storeId = $this->_getStore()->getId();

			$select = $readAdapter->select()->from(array('e' => $table), array('entity_id'=>'e.entity_id'))
			->where('e.store_id = ?',0)
			->where('e.attribute_id = ?', $attributeId)
			->where('e.value = ?',1)
			->where("e.entity_id IN(SELECT `s`.`entity_id` FROM `{$table}` AS `s` WHERE (s.store_id = {$storeId}) AND (s.value = 1) AND s.attribute_id = {$attributeId})");

			$productIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['entity_id'];
			}
			if(!empty($productIds)){
				$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
			} else {
				$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
			} 

		} else if($value === "check_unchecked_fields") {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$excludeAttribute = array("status","model","warranty","sourcing","price","special_price","special_from_date","special_to_date","list_price","url_path","image_label","small_image_label","thumbnail_label");
			$attributes = Mage::getSingleton('eav/config')
			->getEntityType(Mage_Catalog_Model_Product::ENTITY)
					->getAttributeCollection()
					->addSetInfo();
			$attributes->getSelect()->where('attribute_code NOT IN(?)',$excludeAttribute);
			$storeAttributes    = array();
			$selectAttributes 	= array();
			$websiteAttributes  = array();
			$store = $this->_getStore();
			$storeId = $store->getStoreId();
			if($storeId){
				$model = Mage::getModel('catalog/product');
				$entityIdField      = $model->getResource()->getEntityIdField();
				foreach($attributes as $attribute){
					$selectAttributes[$attribute->getBackend()->getTable()][] = array(
						'attribute_id'  => $attribute->getAttributeId(),
						'value_id'      => $attribute->getBackend()->getEntityValueId($product)
					);
					if ($attribute->isScopeStore()) {
						$websiteAttributes[$attribute->getBackend()->getTable()][] = (int)$attribute->getAttributeId();
					} elseif ($attribute->isScopeWebsite()) {
						$websiteAttributes[$attribute->getBackend()->getTable()][] = (int)$attribute->getAttributeId();
					}				
				}
				if(!empty($selectAttributes)){
					$productIds = array();
					foreach($selectAttributes as $tableName => $dAttr){ 
						if (!empty($websiteAttributes) && array_key_exists($tableName,$websiteAttributes)) {
							$storeIds = array($storeId);
							if (!empty($storeIds) && !empty($websiteAttributes[$tableName])) {
								$select = $readAdapter->select()
								->from($tableName, $entityIdField)
								->where('attribute_id IN(?)',$websiteAttributes[$tableName])
								->where('store_id IN(?)',$storeIds);
								foreach ($readAdapter->fetchAll($select) as $row) {
									$productIds[] = $row['entity_id'];
								}
							}
						}
					}
				}
				$includeAttribute = array("model","warranty","sourcing","special_from_date","special_to_date");
				$attrs = Mage::getModel('eav/config')->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection()->addSetInfo();
				$attrs->getSelect()->where('attribute_code IN(?)',$includeAttribute);
				$attrCollection = array();
				foreach($attrs as $attr){
					if ($attr->isScopeStore() || $attr->isScopeWebsite()) {
						$attrCollection[(int)$attr->getAttributeId()] = array(
							"table_name" => $attr->getBackend()->getTable(),
							"attribute_code" => $attr->getAttributeCode()
						);
					}
				}
				$secProductIds = array();
				if(!empty($attrCollection)){
					$storeIds = array($storeId);
					foreach($attrCollection as $attrId => $attrArray){
						if(!empty($attrArray)){
							$select = $readAdapter->select()->from($attrArray['table_name'], $entityIdField)->where('attribute_id = ?',$attrId)->where('store_id IN(?)',$storeIds); 
							$secProductIds[$attrArray['attribute_code']] = array();
							foreach ($readAdapter->fetchAll($select) as $row) {
								$secProductIds[$attrArray['attribute_code']][] = $row['entity_id'];
							}
						}
					}
					if(!empty($secProductIds)){
						foreach($secProductIds as $groups => $entityIds){
							if($groups == "sourcing") continue;
							foreach($entityIds as $key => $entityId){
								if(!in_array($entityId,$secProductIds["sourcing"])){
									$productIds[] = $entityId;
								}
							}
						}
					}
				}
				if(!empty($productIds)){
					$this->getCollection()->getSelect()->where("(e.entity_id IN(". join(',',$productIds) ."))");
				} else {
					$this->getCollection()->getSelect()->where("(e.entity_id IN(0))");
				}	
			}
				
		}
		return $this;
	}

		protected function _filterHasUrlConditionCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		if ($value === "associate") {
			$this->getCollection()->getSelect()->where(
				 "(e.type_id = 'simple') and (at_types.parent_id IS NOT NULL)");
			 } else if($value === "simple") {
	 			$this->getCollection()->getSelect()->where(
					  "(e.type_id = 'simple') and (at_types.parent_id IS NULL)");
			} else if($value === "multiple_associate"){
					$this->getCollection()->getSelect()->where("(e.type_id = 'simple') and (at_types.parent_id IS NOT NULL)")->having("count(at_types.parent_id) > 1");
	 		} else {
			$this->getCollection()->getSelect()->where(
				 "(e.type_id = '".$value."')");
		}
		return $this;
	}

	protected function _filterHasUrlWebsitesCallback($collection, $column)
	{
		if (!$value = $column->getFilter()->getValue()) {
			return $this;
		}
		$websites = Mage::getModel('core/website')->getCollection()->toOptionHash();
		if (array_key_exists($value, $websites)) {
			 $this->getCollection()->getSelect()->where(
				 "(at_websites.website_id = ".$value.")");
		} else {
			$readAdapter = $this->getCollection()->getConnection('core_read');
			$select = $readAdapter->select()->from(array('website' => 'catalog_product_website'), array('product_id'))->where('website.website_id =?',($value / 2));
			$productIds = array();
			foreach ($readAdapter->fetchAll($select) as $row) {
				$productIds[] = $row['product_id'];
			}
			 $this->getCollection()->getSelect()->where("(e.entity_id NOT IN(". join(',',$productIds) ."))");
		}
		return $this;
	}

	protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('product');

		if($this->_isAllowedAction('mass_delete')){
			$this->getMassactionBlock()->addItem('delete', array(
				 'label'=> Mage::helper('catalog')->__('Delete'),
				 'url'  => $this->getUrl('*/*/massDelete'),
				 'confirm' => Mage::helper('catalog')->__('Are you sure?')
			));
		}

        $statuses = Mage::getSingleton('catalog/product_status')->getOptionArray();

        array_unshift($statuses, array('label'=>'', 'value'=>''));
        $this->getMassactionBlock()->addItem('status', array(
             'label'=> Mage::helper('catalog')->__('Change status'),
             'url'  => $this->getUrl('*/*/massStatus', array('_current'=>true)),
             'additional' => array(
                    'visibility' => array(
                         'name' => 'status',
                         'type' => 'select',
                         'class' => 'required-entry',
                         'label' => Mage::helper('catalog')->__('Status'),
                         'values' => $statuses
                     )
             )
        ));

        if (Mage::getSingleton('admin/session')->isAllowed('catalog/update_attributes')){
            $this->getMassactionBlock()->addItem('attributes', array(
                'label' => Mage::helper('catalog')->__('Update Attributes'),
                'url'   => $this->getUrl('*/catalog_product_action_attribute/edit', array('_current'=>true))
            ));
        }

        Mage::dispatchEvent('adminhtml_catalog_product_grid_prepare_massaction', array('block' => $this));
        return $this;
    }

	protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/products/' . $action);
    }
}
