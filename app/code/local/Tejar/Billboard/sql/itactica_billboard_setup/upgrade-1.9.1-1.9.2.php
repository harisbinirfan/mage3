<?php
/**
 * Intenso Premium Theme
 * 
 * @category    Itactica
 * @package     Itactica_FeaturedProducts
 * @copyright   Copyright (c) 2014 Itactica (http://www.itactica.com)
 * @license     http://getintenso.com/license
 */

$this->startSetup();


$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'alt_fifth',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'Alt tag of fifth image'
    )
);

$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'link_fifth',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'Link of fifth image'
    )
);

$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'image_large_fifth',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'Fifth large image filename'
    )
);

$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'image_medium_fifth',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'Fifth medium image filename'
    )
);

$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'image_large_five',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'Image Uplaod Large'
    )
);

$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'description_first',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'First Description Billboard'
    )
);

$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'description_second',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'Second Description Billboard'
    )
);

$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'description_third',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'Third Description Billboard'
    )
);

$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'description_fourth',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'Fourth Description Billboard'
    )
);

$this->getConnection()->addColumn(
    $this->getTable('itactica_billboard/billboard'),
    'description_fifth',
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_TEXT, 255,
        'nullable'  => true,
        'comment'   => 'Fifth Description Billboard'
    )
);

$this->endSetup();
