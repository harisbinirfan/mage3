<?php

$installer = $this;

$installer->startSetup();

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

//---- Add Column in Quote and Order Tables to hold 'Store Credit' Amount,..
$installer->getConnection()
->addColumn($installer->getTable('sales/shipment'),'sent_rating_email', array(
    'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'length' => 1,
    //'nullable' => false,
    'default' => 0, // column name to insert new column after
    'comment'   => 'Rating Review email'
    ));
	
$installer->getConnection()
	->addColumn($installer->getTable('sales/invoice'),'sent_delay_email', array(
    'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'length' => 1,
    //'nullable' => false,
    'default' => 0, // column name to insert new column after
    'comment'   => 'Order Delayed Email'
    ));
	
$installer->endSetup();