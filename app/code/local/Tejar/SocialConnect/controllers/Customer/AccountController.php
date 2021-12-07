<?php
/**
 * Customer account controller
 *
 * @category   Mage - Overirding
 * @package    Tejar_SocialConnect
 * @author     Zeeshan
 */
require_once Mage::getModuleDir('controllers', 'Mage_Customer') . DS . 'AccountController.php';
class Tejar_SocialConnect_Customer_AccountController extends Mage_Customer_AccountController
{


 /**
     * Confirm customer account by id and confirmation key
     */
    public function confirmAction()
    {
        $session = $this->_getSession();
        if ($session->isLoggedIn()) {
            $this->_getSession()->logout()->regenerateSessionId();
        }
        try {
            $id      = $this->getRequest()->getParam('id', false);
            $key     = $this->getRequest()->getParam('key', false);
            $backUrl = $this->getRequest()->getParam('back_url', false);
			
            if (empty($id) || empty($key)) {
                throw new Exception($this->__('Bad request.'));
            }

            // load customer by id (try/catch in case if it throws exceptions)
            try {
                $customer = $this->_getModel('customer/customer')->load($id);
                if ((!$customer) || (!$customer->getId())) {
                    throw new Exception('Failed to load customer by id.');
                }
            }
            catch (Exception $e) {
                throw new Exception($this->__('Wrong customer account specified.'));
            }

            // check if it is inactive
            if ($customer->getConfirmation()) {
                if ($customer->getConfirmation() !== $key) {
                    throw new Exception($this->__('Wrong confirmation key.'));
                }

                // activate customer
                try {
                    $customer->setConfirmation(null);
                    $customer->save();
                }
                catch (Exception $e) {
                    throw new Exception($this->__('Failed to confirm customer account.'));
                }

                // log in and send greeting email, then die happy
                $session->setCustomerAsLoggedIn($customer);
                $successUrl = $this->_welcomeCustomer($customer, true);
                //$this->_redirectSuccess($backUrl ? $backUrl : $successUrl);
				//--- Redirect to Index Page
				$this->_redirectSuccess($this->_getUrl('*/*/index', array('_secure' => true)));
                return;
            }
			//echo "zeee----".$this->_getUrl('*/*/index', array('_secure' => true));die;
            // die happy
			// $session->setCustomerAsLoggedIn($customer);
            $this->_redirectSuccess($this->_getUrl('*/*/index', array('_secure' => true)));
            return;
        }
        catch (Exception $e) {
            // die unhappy
            $this->_getSession()->addError($e->getMessage());
            $this->_redirectError($this->_getUrl('*/*/index', array('_secure' => true)));
            return;
        }
    }
	
	 /**
     * Change customer password action
     */
    public function editPostAction()
    {
		
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/edit');
        }

        if ($this->getRequest()->isPost()) {
            /** @var $customer Mage_Customer_Model_Customer */
            $customer = $this->_getSession()->getCustomer();
			//echo "ssssssssssssss--->".$customer->getTejarSocialconnectGid();die;
			
            $customer->setOldEmail($customer->getEmail());
            /** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = $this->_getModel('customer/form');
            $customerForm->setFormCode('customer_account_edit')
                ->setEntity($customer);

            $customerData = $customerForm->extractData($this->getRequest());

            $errors = array();
            $customerErrors = $customerForm->validateData($customerData);
            if ($customerErrors !== true) {
                $errors = array_merge($customerErrors, $errors);
            } else {
                $customerForm->compactData($customerData);
                $errors = array();

             //--- if user is connected socially (only) and attempts to change info, ignore password validation..
			if($customer->getData('tejar_socialconnect_value')!=="1"){
				if (!$customer->validatePassword($this->getRequest()->getPost('current_password'))) {
                    $errors[] = $this->__('Invalid current password');
                }
			}
	//		           echo md5(''),"<br />";
	//echo "<pre>"; var_dump($customer->getData());die;
                // If email change was requested then set flag
                $isChangeEmail = ($customer->getOldEmail() != $customer->getEmail()) ? true : false;
                $customer->setIsChangeEmail($isChangeEmail);
				
			
                
				// If password change was requested then add it to common validation scheme
                $customer->setIsChangePassword($this->getRequest()->getParam('change_password'));
				
				//--- if user is connected socially (only) and changes email without passwords, throw error.
				if($isChangeEmail && $customer->getData('tejar_socialconnect_value')=="1"){
					 if (!$customer->getIsChangePassword()) {
						$errors[] = $this->__('To change email, new password is required.');
					 }
				}
				
                if ($customer->getIsChangePassword()) {
                    $newPass    = $this->getRequest()->getPost('password');
                    $confPass   = $this->getRequest()->getPost('confirmation');

                    if (strlen($newPass)) {
                        /**
                         * Set entered password and its confirmation - they
                         * will be validated later to match each other and be of right length
                         */
                        $customer->setPassword($newPass);
                        $customer->setPasswordConfirmation($confPass);
						
						//--- If user set new password and connected socially only, make new account and disassociate..
						if($customer->getData('tejar_socialconnect_value')=="1"){
							$customer->setData('tejar_socialconnect_value', '');
						}
						
						
                    } else {
                        $errors[] = $this->__('New password field cannot be empty.');
                    }
                }

                // Validate account and compose list of errors if any
                $customerErrors = $customer->validate();
                if (is_array($customerErrors)) {
                    $errors = array_merge($errors, $customerErrors);
                }
            }

            if (!empty($errors)) {
		
				
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost());
                foreach ($errors as $message) {
                    $this->_getSession()->addError($message);
                }
                $this->_redirect('*/*/edit');
                return $this;
            }
			
			
			
			

            try {
                $customer->cleanPasswordsValidationData();

                // Reset all password reset tokens if all data was sufficient and correct on email change
                if ($customer->getIsChangeEmail()) {
                    $customer->setRpToken(null);
                    $customer->setRpTokenCreatedAt(null);
					//--------------------------------------- ZEE CODE -------------------------------------------//
				//--- If user attempts to change the email address, disassociate any associated FB or Gmail Account..
	
					//--- Set customer GID and FID to null 
					if($customer->getTejarSocialconnectGid()!=""){
						
						$customer->setTejarSocialconnectGid('');
						$this->_getSession()->addNotice('Your Google Account has been unlinked!');
					}
					if($customer->getTejarSocialconnectFid()!=""){
						$customer->setTejarSocialconnectFid('');
						$this->_getSession()->addNotice('Your Facebook Account has been unlinked!');
					}
			
			//-------------------------------------- END ZEE CODE-------------------------------------------//
                }

                $customer->save();
                $this->_getSession()->setCustomer($customer)
                    ->addSuccess($this->__('The account information has been saved.'));

                if ($customer->getIsChangeEmail() || $customer->getIsChangePassword()) {
                    $customer->sendChangedPasswordOrEmail();
                }
				
                $this->_redirect('customer/account');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->setCustomerFormData($this->getRequest()->getPost())
                    ->addException($e, $this->__('Cannot save the customer.'));
            }
        }

		
		
				
        $this->_redirect('*/*/edit');
    }
	
	
	public function __editAction(){
		//--- if user is connected socially and attempts to change the pasword, return him back..
		if($this->getRequest()->getParam('changepass') && ($this->_getSession()->getCustomer()->getData('tejar_socialconnect_fid') || $this->_getSession()->getCustomer()->getData('tejar_socialconnect_gid') )){
			$this->_redirect('customer/account');
		}else{
			return parent::editAction();
		}
	}
}