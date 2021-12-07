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
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product reports admin controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
require_once 'Mage/Adminhtml/controllers/Report/SalesController.php';
class Tejar_Adminhtml_Report_SalesController extends Mage_Adminhtml_Report_SalesController
{

	/**
     * Created products
     *
     */
    public function productAction()
    {
		
		

		$this->_title($this->__('Reports'))->_title($this->__('Products'))->_title($this->__('Created'));

		  $this->_initAction()
				->_setActiveMenu('report/salesroot/product')
				->_addBreadcrumb(Mage::helper('reports')->__('Created'), Mage::helper('reports')->__('Product Status'))
				->_addContent($this->getLayout()->createBlock('tejar_adminhtml/report_sales_product'))
				->renderLayout();
    }

    /**
     * Export products most viewed report to CSV format
     *
     */
    public function exportCreatedCsvAction()
    {
		$currentDate = Mage::getSingleton('core/date')->date('Y-m-d_H-i-s');
        $fileName   = 'products_status_' . $currentDate . '.csv';
        $grid       = $this->getLayout()->createBlock('tejar_adminhtml/report_sales_product_grid');
        $this->_initReportAction($grid);
        $this->_prepareDownloadResponse($fileName, $grid->getCsvFile());
    }
    /**
     * Export products most viewed report to XML format
     *
     */
    public function exportCreatedExcelAction()
    {
        $currentDate = Mage::getSingleton('core/date')->date('Y-m-d_H-i-s');
        $fileName   = 'products_status_' . $currentDate . '.xml';
        $grid       = $this->getLayout()->createBlock('tejar_adminhtml/report_sales_product_grid');
        $this->_initReportAction($grid);
        $this->_prepareDownloadResponse($fileName, $grid->getExcelFile($fileName));
    }

    /**
     * Check is allowed for report
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        $action = strtolower($this->getRequest()->getActionName());
        switch ($action) {
            case 'viewed':
                return Mage::getSingleton('admin/session')->isAllowed('report/products/viewed');
                break;
            case 'sold':
                return Mage::getSingleton('admin/session')->isAllowed('report/products/sold');
                break;
            case 'lowstock':
                return Mage::getSingleton('admin/session')->isAllowed('report/products/lowstock');
                break;
			case 'created':
                return Mage::getSingleton('admin/session')->isAllowed('report/products/created');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('report/products');
                break;
        }
    }

}
