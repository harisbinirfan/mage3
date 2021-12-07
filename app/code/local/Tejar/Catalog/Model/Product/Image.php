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
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog product link model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Tejar_Catalog_Model_Product_Image extends Mage_Catalog_Model_Product_Image
{
	
	public function fileSizeConvert($bytes)
	{
		$bytes = floatval($bytes);
			$arBytes = array(
				0 => array(
					"UNIT" => "TB",
					"VALUE" => pow(1024, 4)
				),
				1 => array(
					"UNIT" => "GB",
					"VALUE" => pow(1024, 3)
				),
				2 => array(
					"UNIT" => "MB",
					"VALUE" => pow(1024, 2)
				),
				3 => array(
					"UNIT" => "KB",
					"VALUE" => 1024
				),
				4 => array(
					"UNIT" => "B",
					"VALUE" => 1
				),
			);

		foreach($arBytes as $arItem)
		{
			if($bytes >= $arItem["VALUE"])
			{
				$result = $bytes / $arItem["VALUE"];
				$result = str_replace(".", "," , strval(round($result, 2)))." ".$arItem["UNIT"];
				break;
			}
		}
		return $result;
	}
	
	public function getConfig($configPath)
    {
        return Mage::getConfig()->getNode($configPath, 'default');
    }
	
    /**
     * @return Mage_Catalog_Model_Product_Image
     */
    public function saveFile()
    {
        $result = "";
        $filename = $this->getNewFile();
		$info = pathinfo($filename);
		
		$imagickEnabled	= $this->getConfig(
			'catalog/image_optimizer/enabled'
		);
			
		$imagickOption	= $this->getConfig(
                'catalog/image_optimizer/imagick_options'
            );
			
		$imagickOptionPng	= $this->getConfig(
                'catalog/image_optimizer/imagick_options_png'
            );
			
		$imagickOptionGif	= $this->getConfig(
                'catalog/image_optimizer/imagick_options_gif'
            );
			
			
		$output = array();
		
        $this->getImageProcessor()->save($filename);
		
			
		if($imagickEnabled){
			
			$result .= $filename;
			$result .= "|";
			$filesize = filesize($filename);
			$result .= $this->fileSizeConvert($filesize);
			$result .= "|";
			switch (strtolower($info['extension'])) {
				case 'jpg':
				case 'jpeg':
					exec(sprintf($imagickOption, $filename , $filename), $output, $returnVar);	
					$type = 'jpg';
					break;
				case 'png':
					exec(sprintf($imagickOptionPng, $filename , $filename), $output, $returnVar);	
					$type = 'png';
					break;
				case 'gif':
					exec(sprintf($imagickOptionGif, $filename , $filename), $output, $returnVar);	
					$type = 'gif';
					break;	
			}	 
			
			// Mage::log($result, null, 'image_optimze.log');	
			
		}
		
        Mage::helper('core/file_storage_database')->saveFile($filename);
        return $this;
    }

  
}
