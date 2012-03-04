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
			'token'         => $this->getToken(),
			'postback'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=pagseguro',
			'success'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','BRL')),
			'name'			=> trim($_REQUEST['name']),
			'email'			=> trim($_REQUEST['email']),
		);
                        
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		// Create PagSeguro payment request
		$paymentRequest = new PagSeguroPaymentRequest();
		$paymentRequest->setCurrency($data->currency); // Currency defined in Akeeba Subscriptions
		$paymentRequest->setReference($subscription->akeebasubs_subscription_id); // Reference = subscription ID
                
		// Add item
		$paymentRequest->addItem(
			$level->akeebasubs_level_id,
			$level->title . ' - [ ' . $user->username . ' ]',
			1,
			sprintf('%02.2f',$subscription->gross_amount),
			0
		);
                
		// Add customer information
		$paymentRequest->setSenderName($data->name);
		$paymentRequest->setSenderEmail($data->email);
                
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
		
		// Initialise
		$isValid = true;
		
		// Get incoming data
		$type = array_key_exists('notificationType', $data) ? $data['notificationType'] : 'INVALID';
		$code = array_key_exists('notificationCode', $data) ? $data['notificationCode'] : '';
		
		// Is it a valid notifiaction type (only "transaction" is supposed to be sent)
		if($type != 'transaction') {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = 'Invalid notification type: '.$type;
		}
		
		// Is the notification code non-empty?
		if(empty($code)) {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = 'The notification code is empty';
		}
		
		// Get the transaction data
		if($isValid) {
			$credentials = new PagSeguroAccountCredentials($this->getMerchantID(), $this->getToken());
			$transaction = PagSeguroNotificationService::checkTransaction(  
				$credentials,  
				$code
			);
		}
		
		// Load the relevant subscription row and make sure it's valid
		if($isValid) {
			// Get the ID
			$id = $transaction->getReference();
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("reference" field) is invalid';
		}
		
		// Check that mc_gross is correct
		if($isValid) {
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
		}
		
		// Check that id has not been previously processed
		if($isValid) {
			$processorKey = $transaction->getCode();
			if($subscription->processor_key == $processorKey) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = 'This transaction is already processed';
			}
		}
                
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
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'    => $id,
				'processor_key'                 => $processorKey,
				'state'							=> $newStatus,
				'enabled'						=> 0
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
	
	/**
	 * Gets the PagSeguro Token
	 */
	private function getToken()
	{
		return $this->params->get('token','');
	}
	
	/**
	 * Gets the PagSeguro Merchant ID (usually the email address)
	 */
	private function getMerchantID()
	{
		return $this->params->get('merchant','');
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
		$logData .= $isValid ? 'VALID PAGSEGURO IPN' : 'INVALID PAGSEGURO IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}