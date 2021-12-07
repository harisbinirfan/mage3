<?php
/**
 * @category  Apptrian
 * @package   Apptrian_ImageOptimizer
 * @author    Apptrian
 * @copyright Copyright (c) 2017 Apptrian (http://www.apptrian.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License
 */
class Tejar_Imageoptimizer_Helper_Data extends Apptrian_ImageOptimizer_Helper_Data
{
    
    /**
     * Optimizes single file.
     *
     * @param string $filePath
     * @return boolean
     */
    public function optimizeFile($filePath)
    {
        $info = pathinfo($filePath);
        
        $output = array();
		
		$type = "";
		
		$imagickEnabled	= $this->getConfig(
                'apptrian_imageoptimizer/utility/imagick'
            );
			
		$imagickOption	= $this->getConfig(
                'apptrian_imageoptimizer/utility/imagick_options'
            );
			
		$imagickOptionPng	= $this->getConfig(
                'apptrian_imageoptimizer/utility/imagick_options_png'
            );
			
		$imagickOptionGif	= $this->getConfig(
                'apptrian_imageoptimizer/utility/imagick_options_gif'
            );
			
		if($imagickEnabled == 1){
			
			$fileSize = number_format(filesize($filePath) / 1024, 2);
				
			switch (strtolower($info['extension'])) {
				case 'jpg':
				case 'jpeg':
					exec(sprintf($imagickOption, $filePath , $filePath) , $output, $returnVar);	
					$type = 'jpg';
					break;
				case 'png':
					exec(sprintf($imagickOptionPng, $filePath , $filePath) , $output, $returnVar);	
					$type = 'png';
					break;
				case 'gif':
					exec(sprintf($imagickOptionGif, $filePath , $filePath) , $output, $returnVar);	
					$type = 'gif';
					break;	
			}	 
				
			
			
			
			
			if($fileSize <= 10){
				exec(sprintf('convert %s -quality 85  %s', $filePath , $filePath) , $output, $returnVar);
			}
			
		} else {		
			switch (strtolower($info['extension'])) {
				case 'jpg':
				case 'jpeg':
					exec($this->getJpgUtil($filePath), $output, $returnVar);
					$type = 'jpg';
					break;
				case 'png':
					exec($this->getPngUtil($filePath), $output, $returnVar);
					$type = 'png';
					break;
				case 'gif':
					exec($this->getGifUtil($filePath), $output, $returnVar);
					$type = 'gif';
					break;
			}
		}
		
	    $utilPath = $this->getUtilPath() . DS . $this->getConfig(
				'apptrian_imageoptimizer/utility/' . $type
		) . $this->getUtilExt(); 
        
        if ($returnVar == 126) {
            $error = $this->getConfig(
                'apptrian_imageoptimizer/utility/' . $type
            ) . ' is not executable.';
            
            Mage::log($error, null, 'apptrian_imageoptimizer.log');
            
            return false;
        } else {
            if ($this->isLoggingEnabled()) {
                Mage::log($filePath, null, 'apptrian_imageoptimizer.log');
                Mage::log($output, null, 'apptrian_imageoptimizer.log');
            }
            
            $permissions = (string) $this->getConfig(
                'apptrian_imageoptimizer/utility/permissions'
            );
            
            if ($permissions) {
                chmod($filePath, octdec($permissions));
            }
            
            return true;
        }
    }

}
