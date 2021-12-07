<?php
/**
 * @category    Contacts
 * @package     Tejar_Contacts
 * @author      Shariq Shahab <shariqshahab2@gmail.com>
 * @license     https://opensource.org/licenses/osl-3.0.php OSL 3.0
 */
class Tejar_Contacts_Helper_Data extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ENABLED        = 'contacts/autoreply/enabled';
    const XML_PATH_EMAIL_SENDER   = 'contacts/autoreply/email_sender';
    const XML_PATH_EMAIL_TEMPLATE = 'contacts/autoreply/email_template';
    /**
     * Whether auto reply feature is enabled for contact form
     *
     * @return mixed
     */
    public function isEnabled()
    {
        return Mage::getStoreConfig(self::XML_PATH_ENABLED);
    }
    /**
     * Returns the auto reply email sender information
     *
     * @return mixed
     */
    public function getEmailSender()
    {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_SENDER);
    }
    /**
     * Returns the contact form auto reply email template
     *
     * @return mixed
     */
    public function getEmailTemplate()
    {
        return Mage::getStoreConfig(self::XML_PATH_EMAIL_TEMPLATE);
    }

    public function getGoogleCalendar($store){
		$result = "";
		$file = "/usr/share/nginx/tejar" . "/" . "events.json";
		$ioAdapter = new Varien_Io_File();
		if ($ioAdapter->fileExists($file)) {
			$ioAdapter->open(array('path' => $ioAdapter->dirname($file)));
			$ioAdapter->streamOpen($file, 'r');
            $buffer = $ioAdapter->streamRead(1000000);
            if(!empty($buffer)){
                $result = Mage::helper('core')->jsonDecode($buffer)[$store->getStoreId()];
            }
		}
		$ioAdapter->streamClose();
		return $result;
    }
    
}
