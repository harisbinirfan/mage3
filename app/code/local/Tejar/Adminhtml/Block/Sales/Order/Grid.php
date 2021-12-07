<?php
/**
 * Adminhtml sales orders grid
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Adminhtml_Block_Sales_Order_Grid extends Mage_Adminhtml_Block_Sales_Order_Grid
{
	protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(false);

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/cancel')) {
            $this->getMassactionBlock()->addItem('cancel_order', array(
                 'label'=> Mage::helper('sales')->__('Cancel'),
                 'url'  => $this->getUrl('*/sales_order/massCancel'),
            ));
        }

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/hold')) {
            $this->getMassactionBlock()->addItem('hold_order', array(
                 'label'=> Mage::helper('sales')->__('Hold'),
                 'url'  => $this->getUrl('*/sales_order/massHold'),
            ));
        }

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/unhold')) {
            $this->getMassactionBlock()->addItem('unhold_order', array(
                 'label'=> Mage::helper('sales')->__('Unhold'),
                 'url'  => $this->getUrl('*/sales_order/massUnhold'),
            ));
        }

        $this->getMassactionBlock()->addItem('pdfinvoices_order', array(
             'label'=> Mage::helper('sales')->__('Print Invoices'),
             'url'  => $this->getUrl('*/sales_order/pdfinvoices'),
        ));

        $this->getMassactionBlock()->addItem('pdfshipments_order', array(
             'label'=> Mage::helper('sales')->__('Print Packingslips'),
             'url'  => $this->getUrl('*/sales_order/pdfshipments'),
        ));

        $this->getMassactionBlock()->addItem('pdfcreditmemos_order', array(
             'label'=> Mage::helper('sales')->__('Print Credit Memos'),
             'url'  => $this->getUrl('*/sales_order/pdfcreditmemos'),
        ));

        $this->getMassactionBlock()->addItem('pdfdocs_order', array(
             'label'=> Mage::helper('sales')->__('Print All'),
             'url'  => $this->getUrl('*/sales_order/pdfdocs'),
        ));

        $this->getMassactionBlock()->addItem('print_shipping_label', array(
             'label'=> Mage::helper('sales')->__('Print Shipping Labels'),
             'url'  => $this->getUrl('*/sales_order_shipment/massPrintShippingLabel'),
        ));

		//--- Zee Code ---------//
		$this->getMassactionBlock()->addItem('bulk_shipping_label', array(
             'label'=> Mage::helper('sales')->__('Bulk Shipping Labels'),
             'url'  => $this->getUrl('*/sales_order_shipment/printBulkShippingLabel'),
        ));

        if($this->_isAllowedAction('mass_delete')){
		    //--- 3SD Code ---------//
            $this->getMassactionBlock()->addItem('bulk_order_summery', array(
                'label'=> Mage::helper('sales')->__('Bulk Order Summary'),
                'url'  => $this->getUrl('*/sales_order_invoice/printBulkOrderSummary'),
            ));
        }

        return $this;
    }

		protected function _prepareCollection()
		    {
		        $collection = Mage::getResourceModel($this->_getCollectionClass());
				$collection->getSelect()
				->joinLeft(array('order_address' => $collection->getTable('sales/order_address')),'main_table.entity_id = order_address.parent_id AND order_address.address_type="shipping"', 'order_address.city as city')
				->joinLeft(array('billing_address' => $collection->getTable('sales/order_address')),'main_table.entity_id = billing_address.parent_id AND billing_address.address_type="billing"', 'billing_address.city as city')
				->joinLeft(array('payment'=>$collection->getTable('sales/order_payment')), 'payment.parent_id=main_table.entity_id',array('payment_method'=>'method'))
				->joinLeft(array('shipping'=>$collection->getTable('sales/order')), 'shipping.entity_id=main_table.entity_id',array('shipping_method'=>'shipping_method'))
                ->joinLeft(array('invoice'=>$collection->getTable('sales/invoice_grid')), 'invoice.order_id=main_table.entity_id AND invoice.created_at = (SELECT MIN(innerInvoice.created_at) FROM `sales_flat_invoice_grid` as innerInvoice WHERE innerInvoice.order_id = main_table.entity_id)',array('invoice_date'=>'invoice.created_at'));
		        $this->setCollection($collection);
		        return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();
		    }
				protected function _prepareColumns()
    {
        $this->addColumn('real_order_id', array(
            'header'=> Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type'  => 'text',
            'index' => 'increment_id',
						'filter_index' => 'main_table.increment_id',
        ));
        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'    => Mage::helper('sales')->__('Purchased From (Store)'),
                'index'     => 'store_id',
								'filter_index' => 'main_table.store_id',
                'type'      => 'store',
                'store_view'=> true,
                'display_deleted' => true,
            ));
        }
        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
						'filter_index' => 'main_table.created_at'
        ));
        $this->addColumn('invoice_date', array(
            'header' => Mage::helper('sales')->__('Invoice Date'),
            'index' => 'invoice_date',
            'type' => 'datetime',
            'width' => '100px',
        ));
        $this->addColumn('billing_name', array(
            'header' => Mage::helper('sales')->__('Bill to Name'),
            'index' => 'billing_name',
            'filter_index' => 'main_table.billing_name'
        ));
        $this->addColumn('shipping_name', array(
            'header' => Mage::helper('sales')->__('Ship to Name'),
            'index' => 'shipping_name',
            'filter_index' => 'main_table.shipping_name'
        ));
				// 3SD CODE ADD CITY OPTION ORDER GIRD
		$this->addColumn('billing_city', array(
            'header' => Mage::helper('sales')->__('Bill to City'),
            'index' => 'city',
			'filter_index' => 'billing_address.city',
        ));
			// 3SD CODE ADD PAYMENT METHOD OPTION ORDER GIRD
		$paymentMethods = array();
		$allActivePaymentMethods = Mage::getModel('payment/config')->getAllMethods();
		foreach($allActivePaymentMethods as $key => $paymentMethod){
			if($paymentMethod->getTitle()){
				$paymentMethods[$paymentMethod->getCode()] = $paymentMethod->getTitle();
			} else {
				$paymentMethods[$paymentMethod->getCode()] = $paymentMethod->getCode();
			}
		}
		 $this->addColumn('payment_method', array(
            'header' => Mage::helper('sales')->__('Payment Method'),
            'index' => 'payment_method',
						'filter_index' => 'payment.method',
						'type'  => 'options',
            'options' => $paymentMethods,
        ));
		// 3SD CODE ADD SHIPPING METHOD OPTION ORDER GIRD
		$shippingMethods = array();
		$allActiveShippingMethods = Mage::getModel('shipping/config')->getAllCarriers();
		foreach($allActiveShippingMethods as $key => $shippingMethod){
			$shippingTitle = Mage::getStoreConfig('carriers/'.$key.'/title');
			$shippingMethods[$shippingMethod->getId()."_".$shippingMethod->getId()] = $shippingTitle;
		}
		// 3SD CODE ADD CITY OPTION ORDER GIRD
		$this->addColumn('city', array(
            'header' => Mage::helper('sales')->__('Ship to City'),
            'index' => 'city',
						'filter_index' => 'order_address.city',
        ));
		$this->addColumn('shipping_method', array(
            'header' => Mage::helper('sales')->__('Shipping Method'),
            'index' => 'shipping_method',
						'filter_index' => 'shipping.shipping_method',
						'type'  => 'options',
            'options' => $shippingMethods,
        ));
		$this->addColumn('base_grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Base)'),
            'index' => 'base_grand_total',
            'type'  => 'currency',
            'currency' => 'base_currency_code',
						'filter_index' => 'main_table.base_grand_total'
        ));
        $this->addColumn('grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type'  => 'currency',
            'currency' => 'order_currency_code',
						'filter_index' => 'main_table.grand_total'
        ));
        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type'  => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
						'filter_index' => 'main_table.status'
        ));
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action',
                array(
                    'header'    => Mage::helper('sales')->__('Action'),
                    'width'     => '50px',
                    'type'      => 'action',
                    'getter'     => 'getId',
                    'actions'   => array(
                        array(
                            'caption' => Mage::helper('sales')->__('View'),
                            'url'     => array('base'=>'*/sales_order/view'),
                            'field'   => 'order_id',
                            'data-column' => 'action',
                        )
                    ),
                    'filter'    => false,
                    'sortable'  => false,
                    'index'     => 'stores',
                    'is_system' => true,
            ));
        }
        $this->addRssList('rss/order/new', Mage::helper('sales')->__('New Order RSS'));
        $this->addExportType('*/*/exportCsv', Mage::helper('sales')->__('CSV'));
        $this->addExportType('*/*/exportExcel', Mage::helper('sales')->__('Excel XML'));
        return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns();
    }

    protected function _isAllowedAction($action)
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/products/' . $action);
    }
}
