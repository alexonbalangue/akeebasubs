<?php
/**
 * @package		akeebasubs
 * @copyright           Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentPagseguro extends JPlugin
{
	private $ppName = 'pagseguro';
	private $ppKey = 'PLG_AKPAYMENT_PAGSEGURO_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		require_once dirname(__FILE__).'/pagseguro/library/PagSeguroLibrary.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_pagseguro', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_pagseguro', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_pagseguro', JPATH_ADMINISTRATOR, null, true);
                
                // The defined PagSeguro log file must exists, otherwise a default one is used
                $logfile = PagSeguroConfig::getLogFileLocation();
		if (! file_exists($logfile)) {
                    if ($f = @fopen($logfile, "a")) {
                            fclose($f);
                    }
                }
	}

	public function onAKPaymentGetIdentity()
	{
		$title = $this->params->get('title','');
		if(empty($title)) $title = JText::_($this->ppKey);
		$ret = array(
			'name'		=> $this->ppName,
			'title'		=> $title
		);
		return (object)$ret;
	}
	
	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * "paymentForm" and a visible submit button.
	 * 
	 * @param string $paymentmethod
	 * @param JUser $user
	 * @param AkeebasubsTableLevel $level
	 * @param AkeebasubsTableSubscription $subscription
	 * @return string
	 */
	public function onAKPaymentNew($paymentmethod, $user, $level, $subscription)
	{
		if($paymentmethod != $this->ppName) return false;
		
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		$data = (object)array(
			'merchant'		=> $this->getMerchantID(),
			'token'                 => $this->getToken(),
			'postback'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=pagseguro',
			'success'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','BRL')),
			'name'                  => trim($_REQUEST['name']),
                        'email'                 => trim($_REQUEST['email']),
		);
                        
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
                // Create PagSeguro payment request
		$paymentRequest = new PagSeguroPaymentRequest();
                $paymentRequest->setCurrency($data->currency);
                
                // Add item
		$paymentRequest->addItem(
			$level->title,
			$level->title . ' - [ ' . $user->username . ' ]',
			$subscription->gross_amount);
                
		// Add customer information
		$paymentRequest->setSenderName($data->name);
		$paymentRequest->setSenderEmail($data->email);
                $address = new PagSeguroAddress();  
                $address->setPostalCode($kuser->zip);
                $address->setStreet($kuser->address1);
                $address->setComplement($kuser->address2);  
                $address->setCity($kuser->city);  
                $address->setState($kuser->state); 
                $address->setCountry($kuser->country);
                $paymentRequest->setShipping($address);
                
                // Add redirect Url
		$paymentRequest->setRedirectUrl($data->success);
                
                // Call the PagSeguro web service and register this request for payment
		try {			
			$credentials = new PagSeguroAccountCredentials($data->merchant, $data->token);
			$paymentUrl = $paymentRequest->register($credentials);
                        $data->url=$paymentUrl;
		} catch (PagSeguroServiceException $e) {
                        return JError::raiseError(500, 'Cannot proceed with the payment. You have an error in your PagSeguro setup: '.$e->getMessage());
		}
		
		@ob_start();
		include dirname(__FILE__).'/pagseguro/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
                // TODO: Validity checks
                // 
		// Load the relevant subscription row
		if($isValid) {
                        // TODO Get the ID
			$id = -1;
			$subscription = null;
			if($id > 0) {
				$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->setId($id)
					->getItem();
				if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id) ) {
					$subscription = null;
					$isValid = false;
				}
			} else {
				$isValid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("custom" field) is invalid';
		}
                
                // Get notification code & type
                $type = $data['notificationType'];
                $code = $data['notificationCode'];
                
                if($type === 'transaction' && !empty($code))
                {
			$credentials = new PagSeguroAccountCredentials($this->getMerchantID(), $this->getToken());
                        $transaction = PagSeguroNotificationService::checkTransaction(  
                                $credentials,  
                                $code
                        );
                        
                        // TODO Check that receiver is correct
                        
                        // Check that mc_gross is correct
                        $isPartialRefund = false;
                        if($isValid && !is_null($subscription)) {
                                $ms_gross = $transaction->getGrossAmount();
                                $gross = floatval($subscription->gross_amount);
                                if(getGrossAmount > 0) {
                                        // A positive value means "payment". The prices MUST match!
                                        // Important: NEVER, EVER compare two floating point values for equality.
                                        $isValid = ($gross - $ms_gross) < 0.01;
                                } else {
                                        $isPartialRefund = false;
                                        $temp_ms_gross = -1 * $ms_gross;
                                        $isPartialRefund = ($gross - $temp_ms_gross) > 0.01; 
                                }
                                if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
                        }
                        
                        // TODO Check that id has not been previously processed
                        
                        // TODO Check that currency is correct

                        // Log the IPN data
                        $this->logIPN($data, $isValid);

                        // Fraud attempt? Do nothing more!
                        if(!$isValid) return false;
                        
                        // Check the payment_status
                        $status = $transaction->getStatus();
                        switch($status->getTypeFromValue())
                        {
                                case 'AVAILABLE':
                                        $newStatus = 'C';
                                        break;
                                    
                                case 'WAITING_PAYMENT':
                                case 'IN_ANALYSIS':
                                case 'PAID':
                                case 'IN_DISPUTE':
                                        $newStatus = 'P';
                                        break;

                                case 'REFUNDED':
                                case 'CANCELLED':
                                default:
                                        // Partial refunds can only by issued by the merchant. In that case,
                                        // we don't want the subscription to be cancelled. We have to let the
                                        // merchant adjust its parameters if needed.
                                        if($isPartialRefund) {
                                                $newStatus = 'C';
                                        } else {
                                                $newStatus = 'X';
                                        }
                                        break;
                        }
                        
                        // Update subscription status (this also automatically calls the plugins)
                        $updates = array(
                                'akeebasubs_subscription_id'    => $id,
                                // TODO get the key
                                'processor_key'                 => -1,
                                'state'				=> $newStatus,
                                'enabled'			=> 0
                        );
                        jimport('joomla.utilities.date');
                        if($newStatus == 'C') {
                                // Fix the starting date if the payment was accepted after the subscription's start date. This
                                // works around the case where someone pays by e-Check on January 1st and the check is cleared
                                // on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
                                // the user would have paid us and we'd never given him a subscription!
                                $jNow = new JDate();
                                $jStart = new JDate($subscription->publish_up);
                                $jEnd = new JDate($subscription->publish_down);
                                $now = $jNow->toUnix();
                                $start = $jStart->toUnix();
                                $end = $jEnd->toUnix();

                                if($start < $now) {
                                        $duration = $end - $start;
                                        $start = $now;
                                        $end = $start + $duration;
                                        $jStart = new JDate($start);
                                        $jEnd = new JDate($end);
                                }

                                $updates['publish_up'] = $jStart->toMySQL();
                                $updates['publish_down'] = $jEnd->toMySQL();
                                $updates['enabled'] = 1;

                        }
                        $subscription->save($updates);
                        		
                        // Run the onAKAfterPaymentCallback events
                        jimport('joomla.plugin.helper');
                        JPluginHelper::importPlugin('akeebasubs');
                        $app = JFactory::getApplication();
                        $jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array(
                                $subscription
                        ));

                        return true;
                }
                return false;
	}
	
	/**
	 * Gets the PagSeguro Token
	 */
	private function getToken()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return $this->params->get('sandbox_token','');
		} else {
			return $this->params->get('token','');
		}
	}
	
	/**
	 * Gets the PagSeguro Merchant ID (usually the email address)
	 */
	private function getMerchantID()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return $this->params->get('sandbox_merchant','');
		} else {
			return $this->params->get('merchant','');
		}
	}
	
	/**
	 * Reformats the XML response for logging
	 */
	private function reformatData($data)
	{
		$data = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $data);
		$data = str_replace(array('<', '>'), array('[', ']'), $data);

		return $data;
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
		$logFile = $logpath.'/akpayment_pagseguro_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_pagseguro_ipn-1.php';
				if(JFile::exists($altLog)) {
					JFile::delete($altLog);
				}
				JFile::copy($logFile, $altLog);
				JFile::delete($logFile);
				$dummy = "<?php die(); ?>\n";
				JFile::write($logFile, $dummy);
			}
		}
		$logData = JFile::read($logFile);
		if($logData === false) $logData = '';
		$logData .= "\n" . str_repeat('-', 80);
		$logData .= $isValid ? 'VALID GOOGLE CHECKOUT IPN' : 'INVALID GOOGLE CHECKOUT IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}