<?php
/**
 * Sales Order Shipment PDF model
 *
 * @category   Tejar
 * @package    Tejar_Sales
 * @author     Zeeshan
 */
class Tejar_Sales_Model_Order_Pdf_Invoice extends Mage_Sales_Model_Order_Pdf_Invoice
{

  /**
     * Insert order to pdf page
     *
     * @param Zend_Pdf_Page $page
     * @param Mage_Sales_Model_Order $obj
     * @param bool $putOrderId
     */
    protected function insertOrder(&$page, $obj, $putOrderId = true)
    {
        if ($obj instanceof Mage_Sales_Model_Order) {
            $shipment = null;
            $order = $obj;
        } elseif ($obj instanceof Mage_Sales_Model_Order_Shipment) {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }
        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.45));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.45));
        $page->drawRectangle(25, $top, 570, $top - 45);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $this->setDocHeaderCoordinates(array(25, $top, 570, $top - 45));
        $this->_setFontRegular($page, 10);
        if ($putOrderId) {
            $page->drawText(
                Mage::helper('sales')->__('Order # ') . $order->getRealOrderId(), 35, ($top -= 15), 'UTF-8'
            );
        }
        $page->drawText(
            Mage::helper('sales')->__('Order Date: ') . Mage::helper('core')->formatDate(
                $order->getCreatedAtStoreDate(), 'medium', false
            ),
            35,
            ($top -= 15),
            'UTF-8'
        );
        $top -= 10;
        $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $top, 275, ($top - 25));
        $page->drawRectangle(275, $top, 570, ($top - 25));
        /* Calculate blocks info */
        /* Billing Address */
        $billingAddress = $this->_formatAddress($order->getBillingAddress()->format('pdf'));
        /* Payment */
        $paymentInfo = Mage::helper('payment')->getInfoBlock($order->getPayment())
            ->setIsSecureMode(true)
            ->toPdf();
        $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key=>$value){
            if (strip_tags(trim($value)) == '') {
                unset($payment[$key]);
            }
        }
        reset($payment);
        /* Shipping Address and Method */
        if (!$order->getIsVirtual()) {
            /* Shipping Address */
            $shippingAddress = $this->_formatAddress($order->getShippingAddress()->format('pdf'));
            $shippingMethod  = $order->getShippingDescription();
        }
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 12);
        $page->drawText(Mage::helper('sales')->__('Sold to:'), 35, ($top - 15), 'UTF-8');
        if (!$order->getIsVirtual()) {
            $page->drawText(Mage::helper('sales')->__('Ship to:'), 285, ($top - 15), 'UTF-8');
        } else {
            $page->drawText(Mage::helper('sales')->__('Payment Method:'), 285, ($top - 15), 'UTF-8');
        }
        $addressesHeight = $this->_calcAddressHeight($billingAddress);
        if (isset($shippingAddress)) {
            $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));
        }
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(25, ($top - 25), 570, $top - 33 - $addressesHeight);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 10);
        $this->y = $top - 40;
        $addressesStartY = $this->y;
        foreach ($billingAddress as $value){
            if ($value !== '') {
                $text = array();
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $page->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }
        $addressesEndY = $this->y;
        if (!$order->getIsVirtual()) {
            $this->y = $addressesStartY;
            foreach ($shippingAddress as $value){
                if ($value!=='') {
                    $text = array();
                    foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
                        $this->y -= 15;
                    }
                }
            }
            $addressesEndY = min($addressesEndY, $this->y);
            $this->y = $addressesEndY;
            $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineWidth(0.5);
            $page->drawRectangle(25, $this->y, 275, $this->y-25);
            $page->drawRectangle(275, $this->y, 570, $this->y-25);
            $this->y -= 15;
            $this->_setFontBold($page, 12);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $page->drawText(Mage::helper('sales')->__('Payment Method'), 35, $this->y, 'UTF-8');
            $page->drawText(Mage::helper('sales')->__('Shipping Method:'), 285, $this->y , 'UTF-8');
            $this->y -=10;
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(1));
            $this->_setFontRegular($page, 10);
            $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
            $paymentLeft = 35;
            $yPayments   = $this->y - 15;
        }
        else {
            $yPayments   = $addressesStartY;
            $paymentLeft = 285;
        }
        foreach ($payment as $value){
            if (trim($value) != '') {
                //Printing "Payment Method" lines
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                foreach (Mage::helper('core/string')->str_split($value, 45, true, true) as $_value) {
                    $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
                    $yPayments -= 15;
                }
            }
        }
        if ($order->getIsVirtual()) {
            // replacement of Shipments-Payments rectangle block
            $yPayments = min($addressesEndY, $yPayments);
            $page->drawLine(25,  ($top - 25), 25,  $yPayments);
            $page->drawLine(570, ($top - 25), 570, $yPayments);
            $page->drawLine(25,  $yPayments,  570, $yPayments);
            $this->y = $yPayments - 15;
        } else {
            $topMargin    = 15;
            $methodStartY = $this->y;
            $this->y     -= 15;
            foreach (Mage::helper('core/string')->str_split($shippingMethod, 45, true, true) as $_value) {
                $page->drawText(strip_tags(trim($_value)), 285, $this->y, 'UTF-8');
                $this->y -= 15;
            }
            $yShipments = $this->y;
            $totalShippingChargesText = "(" . Mage::helper('sales')->__('Total Shipping Charges') . " "
                . $order->formatPriceTxt($order->getShippingAmount()) . ")";
            $page->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
            $yShipments -= $topMargin + 10;
            $tracks = array();
            if ($shipment) {
                $tracks = $shipment->getAllTracks();
            }
            if (count($tracks)) {
                $page->setFillColor(new Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->setLineWidth(0.5);
                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
                $page->drawLine(400, $yShipments, 400, $yShipments - 10);
                //$page->drawLine(510, $yShipments, 510, $yShipments - 10);
                $this->_setFontRegular($page, 9);
                $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
                //$page->drawText(Mage::helper('sales')->__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Title'), 290, $yShipments - 7, 'UTF-8');
                $page->drawText(Mage::helper('sales')->__('Number'), 410, $yShipments - 7, 'UTF-8');
                $yShipments -= 20;
                $this->_setFontRegular($page, 8);
                foreach ($tracks as $track) {
                    $CarrierCode = $track->getCarrierCode();
                    if ($CarrierCode != 'custom') {
                        $carrier = Mage::getSingleton('shipping/config')->getCarrierInstance($CarrierCode);
                        $carrierTitle = $carrier->getConfigData('title');
                    } else {
                        $carrierTitle = Mage::helper('sales')->__('Custom Value');
                    }
                    //$truncatedCarrierTitle = substr($carrierTitle, 0, 35) . (strlen($carrierTitle) > 35 ? '...' : '');
                    $maxTitleLen = 45;
                    $endOfTitle = strlen($track->getTitle()) > $maxTitleLen ? '...' : '';
                    $truncatedTitle = substr($track->getTitle(), 0, $maxTitleLen) . $endOfTitle;
                    //$page->drawText($truncatedCarrierTitle, 285, $yShipments , 'UTF-8');
                    $page->drawText($truncatedTitle, 292, $yShipments , 'UTF-8');
                    $page->drawText($track->getNumber(), 410, $yShipments , 'UTF-8');
                    $yShipments -= $topMargin - 5;
                }
            } else {
                $yShipments -= $topMargin - 5;
            }
            $currentY = min($yPayments, $yShipments);
            // replacement of Shipments-Payments rectangle block
            $page->drawLine(25,  $methodStartY, 25,  $currentY); //left
            $page->drawLine(25,  $currentY,     570, $currentY); //bottom
            $page->drawLine(570, $currentY,     570, $methodStartY); //right
            $this->y = $currentY;
            $this->y -= 15;
        }
    }

    /**
     * Return PDF document
     *
     * @param  array $invoices
     * @return Zend_Pdf
     */
    public function getPdf($invoices = array())
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');
        $pdf = new Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new Zend_Pdf_Style();
        $this->_setFontBold($style, 10);
		$x = 0;
        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
                Mage::app()->setCurrentStore($invoice->getStoreId());
            }
            $page  = $this->newPage();
            $order = $invoice->getOrder();
            /* Add image */
            $this->insertLogo($page, $invoice->getStore());
            /* Add address */
            $this->insertAddress($page, $invoice->getStore());
            /* Add head */
            $this->insertOrder(
                $page,
                $order,
                Mage::getStoreConfigFlag(self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID, $order->getStoreId())
            );
            $message = Mage::getStoreConfig('sales/identity/invoice_area', $order->getStoreId());
            /* Add document text and number */
            // $this->insertDocumentNumber(
                // $page,
                // Mage::helper('sales')->__('Invoice # ') . $invoice->getIncrementId()
            // );
            $image = Mage::getStoreConfig('sales/identity/logo', $invoice->getStoreId());
      			$image = Mage::getBaseDir('media') . '/sales/store/logo/' . $image;
      			$image       = Zend_Pdf_Image::imageWithPath($image);
      			$width       = $image->getPixelWidth();
      			$height      = $image->getPixelHeight();
      			$top         = 830; //top border of the page
      			$y1 = $top - $height;
      			$font = $this->_setFontRegular($page, 24);
      			$page->drawText(
      				Mage::helper('sales')->__('Invoice'),  $this->getAlignCenter(Mage::helper('sales')->__('Invoice'), 130, 300, $font, 10), ($y1 + 10), 'UTF-8'
      			);
            /* Add table */
            $this->_drawHeader($page);
            /* Add body */
            foreach ($invoice->getAllItems() as $item){
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);
            }
            /* Add totals */
            $this->insertTotals($page, $invoice);
            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
            if($message){
				$this->_setFontBold($page, 9);
				$page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
				$page->drawText('Terms', $x+35, $this->y-150, 'UTF-8');
				$this->y -= 15;
				$font = $this->_setFontRegular($page, 9);
				$i = 0;
				 foreach (Mage::helper('core/string')->str_split($message, 155, true, true) as $_value) {
					$page->drawText(trim($_value), $x+35, $this->y-150, 'UTF-8');	
					$this->y -= 15;
					$i++;
				}
			}
        }
        $this->_afterGetPdf();
        return $pdf;
    }



  /**
    * Draw header for item table
    *
    * @param Zend_Pdf_Page $page
    * @return void
    */
   protected function _drawCustomItems($item , Zend_Pdf_Page $page , $i , $invoice , $order)
   {

   $lines  = array();
   $shippingAmount = $invoice->getShippingAmount();
   if($shippingAmount != 0){
     $shippingAmount	= " " . Mage::helper('core')->formatPrice($shippingAmount, false);
   }
   else {
     $shippingAmount = "";
   }

   $trackNumbers = array();
   foreach($order->getShipmentsCollection() as $shipment){
       $getAllTracks = $shipment->getAllTracks();
       if(!empty($getAllTracks)){
           foreach($getAllTracks as $track){
               if($track->getData('track_number')){
                $trackNumbers[] = $track->getData('title') . " " . $track->getData('track_number');
               }
           }
       }
   }
   $_trackNumbers = "";
   if(!empty($trackNumbers)){
       $_trackNumbers = join(" | " , $trackNumbers);
   }


   $ProductWeight = $item->getWeight();
   $totalWeight = $item->getRowWeight();

      // draw Product name
   $lines[0] = array(array(
     'text' => Mage::helper('core/string')->str_split($item->getName(), 35, true, true),
     'feed' => 35,
   ));

   // draw SKU
   $lines[0][] = array(
     'text'  => Mage::helper('core/string')->str_split(Mage::helper('core')->formatPrice($item->getPrice() , false), 17),
     'feed'  => 290,
     'align' => 'right'
   );



   // draw QTY
   $qtyInvoiced = null;
   if($item->getQtyInvoiced()){
     $qtyInvoiced = $item->getQtyInvoiced() * 1;
   }

   $qtyShipped = null;
   if($item->getQtyShipped()>0){
     $qtyShipped = " " .  " Shipped " . ($item->getQtyShipped() * 1);
   }

   $qtyRefunded = null;
   if($item->getQtyRefunded()>0){
     $qtyRefunded = " " .  "Refunded " . ($item->getQtyRefunded() * 1);
   }


   $qtyTotal = $qtyInvoiced - (($item->getQtyRefunded() * 1) + ($item->getQtyShipped() * 1));
   if($qtyTotal < 0){
     $qtyTotal = 0;
   }

   $qtyRemain = "Remaining " . $qtyTotal;

   $lines[0][] = array(
     'text'  => Mage::helper('core/string')->str_split(" Invoiced " . ($qtyInvoiced) .  $qtyShipped . $qtyRefunded . $qtyRemain, 11),
     'feed'  => 435,
     'align' => 'right'
   );

       $lines[0][] = array(
           'text'  => $item->getWeight(),
           'feed'  => 360,
           'align' => 'right'
       );

       $lines[0][] = array(
           'text'  => $item->getRowWeight(),
           'feed'  => 495,
           'align' => 'right'
       );

       $lines[0][] = array(
           'text'  => Mage::helper('core')->formatPrice($item->getBaseRowTotal() , false),
           'feed'  => 565,
           'align' => 'right'
       );

       $lineBlock = array(
           'lines'  => $lines,
           'height' => 20
       );

   $lines1  = array();
   $lines2  = array();
   $lines3  = array();


    //columns headers
      // draw Product name
   $lines1[0] = array(array(
     'text' => $order->getRealOrderId(),
     'font'      => 'bold',
     'feed' => 35,
   ));

   // draw SKU
   $lines1[0][] = array(
     'text'  => $invoice->getCreatedAtStoreDate(),
     'font'      => 'bold',
     'feed'  => 200,
     'align' => 'right'
   );

   // draw QTY
   $lines1[0][] = array(
     'text'  => Mage::helper('core/string')->str_split($order->getShippingAddress()->getName(), 35, true, true),
     'font'      => 'bold',
     'feed'  => 220,
   );

       $lines1[0][] = array(
           'text'  => $order->getShippingAddress()->getCity(),
     'font'      => 'bold',
           'feed'  => 420,
           'align' => 'right'
       );

       $lines1[0][] = array(
           'text'  => $order->getShippingAddress()->getTelephone(),
     'font'      => 'bold',
           'feed'  => 540,
           'align' => 'right'
       );

       $lines2[0][] = array(
        'text'  => Mage::helper('core/string')->str_split($order->getShippingDescription() . $shippingAmount, 110, true, true),
		'font'      => 'bold',
		'feed'  => 35,

       );
	   
	$shipmentDate = array();
	$shipmentCollection = $order->getShipmentsCollection();
	foreach($shipmentCollection as $shipment){
	   $shipmentDate[] = $shipment->getCreatedAtStoreDate();
	}
	   

	$lines2[1][] = array(
        'text'  => Mage::helper('core/string')->str_split(MAX($shipmentDate) . " " . $_trackNumbers, 110, true, true),
		'font'      => 'bold',
		'feed'  => 35,
	);

    $lines3[0][] = array(
           'text'  => "--------------------------------------------------------------------------------------------------------------------------------------------------------",
     'font'      => 'bold',
           'feed'  => 35,

       );

       $lineBlock1 = array(
           'lines'  => $lines1,
     'height' => 20

       );

    $lineBlock2 = array(
           'lines'  => $lines2,
      'height' => 20
       );

    $lineBlock3 = array(
           'lines'  => $lines3,
      'height' => 20
       );



   if($i == 0){
     $this->drawLineBlocks($page, array($lineBlock3 , $lineBlock1 , $lineBlock2 , $lineBlock ), array('table' => true));
   }
   // elseif ($itemCount >= $i){
     // $this->drawLineBlocks($page, array($lineBlock), array('table' => true));
   // }
   else {
     $this->drawLineBlocks($page, array($lineBlock), array('table' => true));
   }
       $this->setPage($page);
   }





    public function getBulkOrderSummaryPdf($invoices = array())
      {

  		$this->_beforeGetPdf();
          $this->_initRenderer('invoice');
          $pdf = new Zend_Pdf();
          $this->_setPdf($pdf);
          $style = new Zend_Pdf_Style();
          $this->_setFontBold($style, 10);
  		$page  = $this->newPage();
  		$this->_setFontRegular($page, 10);
  		$lineBlockGrand = "";


  		$InvoiceQtyGrandTotal = 0;
  		$ShippedQtyGrandTotal = 0;
  		$RefundedQtyGrandTotal = 0;
  		$QtyGrandTotal = 0;


  		$InvoiceWeightGrandTotal = 0;
  		$ShippedWeightGrandTotal = 0;
  		$RefundedWeightGrandTotal = 0;
  		$WeightGrandTotal = 0;

  		$InvoiceGrandTotal = 0;
  		$ShippedGrandTotal = 0;
  		$RefundedGrandTotal = 0;
  		$Grandtotal = 0;
  		$shippingGrandtotal = 0;

          foreach ($invoices as $invoice) {

  			$lineBlock = "";
  			$weightProductTotal = 0;
  			$InvoicedQuantityTotal = 0;
  			$ShippedQuantityTotal = 0;
  			$RefundedQuantityTotal = 0;

  			$InvoiceWeightTotal = 0;
  			$ShippedWeightTotal = 0;
  			$RefundedWeightTotal = 0;


              if ($invoice->getStoreId()) {
                  Mage::app()->getLocale()->emulate($invoice->getStoreId());
                  Mage::app()->setCurrentStore($invoice->getStoreId());
              }

              $order = $invoice->getOrder();
  			$page->setFillColor(new Zend_Pdf_Color_Rgb(0, 0, 0));
  			$ShippedAmount = 0;

              $i = 0;
              foreach ($order->getAllItems() as $item){
                  // if ($item->getOrderItem()->getParentItem()) {
                      // continue;
                  // }


  				$InvoicedQuantityTotal += (int) $item->getQtyInvoiced() * 1;
  				$ShippedQuantityTotal += (int) $item->getQtyShipped() * 1;
  				$RefundedQuantityTotal += (int) $item->getQtyRefunded() * 1;


  				$weightProductTotal += $item->getRowWeight();


  				$InvoiceWeightTotal	+= ($item->getWeight() * ($item->getQtyInvoiced() * 1));
  				$ShippedWeightTotal	+= ($item->getWeight() * ($item->getQtyShipped() * 1));
  				$RefundedWeightTotal += ($item->getWeight() * ($item->getQtyRefunded() * 1));


  				$ShippedAmount += ($item->getPrice() * ($item->getQtyShipped() * 1));



  				$this->_drawCustomItems($item , $page , $i , $invoice , $order);
                  /* Draw item */
                  // $this->_drawItem($item, $page, $order);
                  $page = end($pdf->pages);


  				 $i++;
              }



  			$InvoiceQtyGrandTotal += $InvoicedQuantityTotal;
  			$ShippedQtyGrandTotal += $ShippedQuantityTotal;
  			$RefundedQtyGrandTotal += $RefundedQuantityTotal;

  			$InvoiceWeightGrandTotal += $InvoiceWeightTotal;
  			$ShippedWeightGrandTotal += $ShippedWeightTotal;
  			$RefundedWeightGrandTotal += $RefundedWeightTotal;




  			if ($invoice->getStoreId()) {
  				Mage::app()->getLocale()->revert();
  			}

  			$InvoiceGrandTotal += $order->getTotalInvoiced();
  			$ShippedGrandTotal += $ShippedAmount;
  			$RefundedGrandTotal += $order->getTotalRefunded();


  			// $result = $invoceTotal / $invoiceqty
  			// $result * shi

  			$page = end($pdf->pages);

          }


  				$lineBlockGrand['lines'][] = array(
  					array(
  					'text'  => "--------------------------------------------------------------------------------------------------------------------------------------------------------",
  					'font'      => 'bold',
  					'height' => 20,
  					'feed'  => 35,
  					)
  				);

  				$lineBlockGrand['lines'][] = array(
  					array('text'      => 'Invoiced Qty Total:','feed'      => 440,'align'     => 'right','font_size' => 11,'height' => 20,),
  					array('text'      => $InvoiceQtyGrandTotal,'feed'      => 565,'align'     => 'right','font_size' => 11,'height' => 20,),
  				);

  				if($ShippedQtyGrandTotal){
  					$lineBlockGrand['lines'][] = array(
  						array('text'      => 'Shipped Qty Total:','feed'      => 440,'align'     => 'right','font_size' => 11,'height' => 20,),
  						array('text'      => $ShippedQtyGrandTotal,'feed'      => 565,'align'     => 'right','font_size' => 11,'height' => 20,),
  					);
  				}

  				if($RefundedQtyGrandTotal){
  					$lineBlockGrand['lines'][] = array(
  						array('text'      => 'Refunded Qty Total:','feed'      => 440,'align'     => 'right','font_size' => 11,'height' => 20,),
  						array('text'      => $RefundedQtyGrandTotal,'feed'      => 565,'align'     => 'right','font_size' => 11,'height' => 20,),
  					);
  				}

  				$grandQtyTotal = $InvoiceQtyGrandTotal - ($ShippedQtyGrandTotal + $RefundedQtyGrandTotal);
  				if($grandQtyTotal < 0){$grandQtyTotal = 0;}
  				$lineBlockGrand['lines'][] = array(
  					array('text'      => 'Grand Qty Total:','feed'      => 440,'align'     => 'right','font_size' => 11,'height' => 20,),
  					array('text'      => $grandQtyTotal,'feed'      => 565,'align'     => 'right','font_size' => 11,'height' => 20,),
  				);


  				$lineBlockGrand['lines'][] = array(
  					array('text'      => 'Invoiced Weight Total:','feed'      => 440,'align'     => 'right','font_size' => 11,'height' => 20,),
  					array('text'      => $InvoiceWeightGrandTotal,'feed'      => 565,'align'     => 'right','font_size' => 11,'height' => 20,),
  				);

  				if($ShippedWeightGrandTotal){
  					$lineBlockGrand['lines'][] = array(
  						array('text'      => 'Shipped Weight Total:','feed'      => 440,'align'     => 'right','font_size' => 11,'height' => 20,),
  						array('text'      => $ShippedWeightGrandTotal,'feed'      => 565,'align'     => 'right','font_size' => 11,'height' => 20,),
  					);
  				}


  				if($RefundedWeightGrandTotal){
  					$lineBlockGrand['lines'][] = array(
  						array('text'      => 'Refunded Weight Total:','feed'      => 440,'align'     => 'right','font_size' => 11,'height' => 20,),
  						array('text'      => $RefundedWeightGrandTotal,'feed'      => 565,'align'     => 'right','font_size' => 11,'height' => 20,)
  					,);
  				}

  				$grandWeightTotal = $InvoiceWeightGrandTotal - ($ShippedWeightGrandTotal + $RefundedWeightGrandTotal);
  				if($grandWeightTotal < 0){$grandWeightTotal = 0;}
  				$lineBlockGrand['lines'][] = array(
  					array('text'      => 'Grand Weight Total:','feed'      => 440,'align'     => 'right','font_size' => 11,'height' => 20,),
  					array('text'      => $grandWeightTotal,'feed'      => 565,'align'     => 'right','font_size' => 11,'height' => 20,),
  				);


  				if($shippingGrandtotal){
  					$lineBlockGrand['lines'][] = array(
  							array('text'      => 'Shipping & Handling:','feed'      => 440,'align'     => 'right','font_size' => 12,'height' => 20,'font'      => 'bold'),
  							array('text'      => Mage::helper('core')->formatPrice($shippingGrandtotal, false),'feed'      => 565,'align'     => 'right','font_size' => 12,'height' => 20,'font'      => 'bold'),
  					);
  				}


  				$lineBlockGrand['lines'][] = array(
  					array('text'      => 'Invoice Total:','feed'      => 440,'align'     => 'right','font_size' => 12,'height' => 20),
  					array('text'      => Mage::helper('core')->formatPrice($InvoiceGrandTotal , false),'feed'      => 565,'align'     => 'right','font_size' => 12,'height' => 20),
  				);


  				// $ShippedGrandTotal =

  				if($ShippedGrandTotal){
  					$lineBlockGrand['lines'][] = array(
  						array('text'      => 'Shipped Total:','feed'      => 440,'align'     => 'right','font_size' => 12,'height' => 20),
  						array('text'      => Mage::helper('core')->formatPrice($ShippedGrandTotal , false),'feed'      => 565,'align'     => 'right','font_size' => 12,'height' => 20),
  					);
  				}

  				if($RefundedGrandTotal){
  					$lineBlockGrand['lines'][] = array(
  						array('text'      => 'Refunded Total:','feed'      => 440,'align'     => 'right','font_size' => 12,'height' => 20),
  						array('text'      => Mage::helper('core')->formatPrice($RefundedGrandTotal , false),'feed'      => 565,'align'     => 'right','font_size' => 12,'height' => 20),
  					);
  				}

  				$Grandtotal += $InvoiceGrandTotal - ($ShippedGrandTotal + $RefundedGrandTotal);
  				if($Grandtotal < 0){$Grandtotal = 0;}
  				$lineBlockGrand['lines'][] = array(
  					array('text'      => 'Grand Total:','feed'      => 440,'align'     => 'right','font_size' => 12,'height' => 20,'font'      => 'bold'),
  					array('text'      => Mage::helper('core')->formatPrice($Grandtotal , false),'feed'      => 565,'align'     => 'right','font_size' => 12,'height' => 20,'font'      => 'bold'),
  				);

  			$page = $this->drawLineBlocks($page, array($lineBlockGrand));

          $this->_afterGetPdf();
          return $pdf;

  	}

}
