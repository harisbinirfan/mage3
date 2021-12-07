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
class Tejar_Catalog_Model_Category_Image extends Mage_Catalog_Model_Product_Image
{
   

    /**
     * Set filenames for base file and new file
     *
     * @param string $file
     * @return Mage_Catalog_Model_Product_Image
     */
    public function setBaseFile($file)
    {
        $this->_isBaseFilePlaceholder = false;

        if (($file) && (0 !== strpos($file, '/', 0))) {
            $file = '/' . $file;
        }
        $baseDir = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'category';
		

        if ('/no_selection' == $file) {
            $file = null;
        }
        if ($file) {
            if ((!$this->_fileExists($baseDir . $file)) || !$this->_checkMemory($baseDir . $file)) {
                $file = null;
            }
        }
        if (!$file) {
			
			if($this->getDestinationSubdir() == "custom_thumbnail"){
				// check if placeholder defined in config
				$isConfigPlaceholder = Mage::getStoreConfig("catalog/placeholder/thumbnail_placeholder");
				
				
				$configPlaceholder   = '/placeholder/' . $isConfigPlaceholder;
				if ($isConfigPlaceholder && $this->_fileExists($baseDir . $configPlaceholder)) {
					$file = $configPlaceholder;
				}
				else {
					// replace file with skin or default skin placeholder
					$skinBaseDir     = Mage::getDesign()->getSkinBaseDir();
					$skinPlaceholder = "/images/catalog/category/placeholder/thumbnail.jpg";
					$file = $skinPlaceholder;
					if (file_exists($skinBaseDir . $file)) {
						$baseDir = $skinBaseDir;
					}
					else {
						$baseDir = Mage::getDesign()->getSkinBaseDir(array('_theme' => 'default'));
						if (!file_exists($baseDir . $file)) {
							$baseDir = Mage::getDesign()->getSkinBaseDir(array('_theme' => 'default', '_package' => 'base'));
						}
					}
				}
				$this->_isBaseFilePlaceholder = true;
				
			} else {
				// check if placeholder defined in config
				$isConfigPlaceholder = Mage::getStoreConfig("catalog/placeholder/{$this->getDestinationSubdir()}_placeholder");
				
				
				$configPlaceholder   = '/placeholder/' . $isConfigPlaceholder;
				if ($isConfigPlaceholder && $this->_fileExists($baseDir . $configPlaceholder)) {
					$file = $configPlaceholder;
				}
				else {
					// replace file with skin or default skin placeholder
					$skinBaseDir     = Mage::getDesign()->getSkinBaseDir();
					$skinPlaceholder = "/images/catalog/category/placeholder/{$this->getDestinationSubdir()}.jpg";
					$file = $skinPlaceholder;
					if (file_exists($skinBaseDir . $file)) {
						$baseDir = $skinBaseDir;
					}
					else {
						$baseDir = Mage::getDesign()->getSkinBaseDir(array('_theme' => 'default'));
						if (!file_exists($baseDir . $file)) {
							$baseDir = Mage::getDesign()->getSkinBaseDir(array('_theme' => 'default', '_package' => 'base'));
						}
					}
				}
				$this->_isBaseFilePlaceholder = true;
			}
            
        }

        $baseFile = $baseDir . $file;

        if ((!$file) || (!file_exists($baseFile))) {
            throw new Exception(Mage::helper('catalog')->__('Image file was not found.'));
        }

        $this->_baseFile = $baseFile;

        // build new filename (most important params)
        $path = array(
			Mage::getBaseDir('media') . DS . 'catalog' . DS . 'category',
            'cache',
            // Mage::app()->getStore()->getId(),
            $path[] = $this->getDestinationSubdir()
        );
        if((!empty($this->_width)) || (!empty($this->_height)))
            $path[] = "{$this->_width}x{$this->_height}";

        // add misk params as a hash
        $miscParams = array(
                ($this->_keepAspectRatio  ? '' : 'non') . 'proportional',
                ($this->_keepFrame        ? '' : 'no')  . 'frame',
                ($this->_keepTransparency ? '' : 'no')  . 'transparency',
                ($this->_constrainOnly ? 'do' : 'not')  . 'constrainonly',
                $this->_rgbToString($this->_backgroundColor),
                'angle' . $this->_angle,
                'quality' . $this->_quality
        );

        // if has watermark add watermark params to hash
        if ($this->getWatermarkFile()) {
            $miscParams[] = $this->getWatermarkFile();
            $miscParams[] = $this->getWatermarkImageOpacity();
            $miscParams[] = $this->getWatermarkPosition();
            $miscParams[] = $this->getWatermarkWidth();
            $miscParams[] = $this->getWatermarkHeigth();
        }

        $path[] = md5(implode('_', $miscParams));

        // append prepared filename
        $this->_newFile = implode('/', $path) . $file; // the $file contains heading slash

        return $this;
    }
  
  
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

   
    public function clearCache()
    {
        $directory = Mage::getBaseDir('media') . DS.'catalog'.DS.'category'.DS.'cache'.DS;
        $io = new Varien_Io_File();
        $io->rmdir($directory, true);

        Mage::helper('core/file_storage_database')->deleteFolder($directory);
    }
	
	
	/**
     * First check this file on FS
     * If it doesn't exist - try to download it from DB
     *
     * @param string $filename
     * @return bool
     */
    protected function _fileExists($filename) {
        if (file_exists($filename)) {
            return true;
        } else {
            return Mage::helper('core/file_storage_database')->saveFileToFilesystem($filename);
        }
    }

}
