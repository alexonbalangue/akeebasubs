<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentPostfinancech extends JPlugin
{
	private $ppName = 'postfinancech';
	private $ppKey = 'PLG_AKPAYMENT_POSTFINANCECH_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_postfinancech', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_postfinancech', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_postfinancech', JPATH_ADMINISTRATOR, null, true);
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
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		$successURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
		$failureURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id));

		$data = array(
			'PSPID'			=> $this->params->get('merchant',''),
			'ORDERID'		=> $subscription->akeebasubs_subscription_id,
			'AMOUNT'		=> (int)($subscription->gross_amount * 100),
			'CURRENCY'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'LANGUAGE'		=> $this->params->get('language',''),
			'CN'			=> $user->name,
			'EMAIL'			=> $user->email,
			'OWNERADDRESS'	=> $kuser->address1 . ($kuser->address2 ? (', '.$kuser->address2) : ''),
			'OWNERZIP'		=> $kuser->zip,
			'OWNERTOWN'		=> $kuser->city,
			'OWNERCTY'		=> $kuser->country,
			'COM'			=> $level->title . ' - [ ' . $user->username . ' ]',
			'TITLE'			=> $this->params->get('title',''),
			'BGCOLOR'		=> $this->params->get('bgcolor',''),
			
			'ACCEPTURL'		=> $successURL,
			'DECLINEURL'	=> $failureURL,
			
			'TXTCOLOR'		=> $this->params->get('bgcolor',''),
			'TBLBGCOLOR'	=> $this->params->get('tblbgcolor',''),
			'TBLTXTCOLOR'	=> $this->params->get('tbltxtcolor',''),
			'BUTTONBGCOLOR'	=> $this->params->get('buttonbgcolor',''),
			'BUTTONTXTCOLOR'=> $this->params->get('buttontxtcolor',''),
			'FONTTYPE'		=> $this->params->get('fonttype',''),
			'LOGO'			=> $this->params->get('logo',''),
			'HDTBLBGCOLOR'	=> $this->params->get('hdtblbgcolor',''),
			'HDTBLTXTCOLOR'	=> $this->params->get('hdtbltxtcolor',''),
			'HDFONTTYPE'	=> $this->params->get('hdfonttype',''),
		);
		
		$sha1 = $this->getSHA1($data, 'in');
		if(!empty($sha1)) $data['SHASIGN'] = $sha1;
		
		@ob_start();
		$formPostURL = $this->getPaymentURL();
		include dirname(__FILE__).'/postfinancech/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		jimport('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'PostFinance reports transaction as invalid';
		
		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('ORDERID', $data) ? (int)$data['ORDERID'] : -1;
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("ORDERID" field) is invalid';
		}
		
		// Check that AMOUNT is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['AMOUNT']);
			$gross = $subscription->gross_amount;
			if($mc_gross > 0) {
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			} else {
				$isPartialRefund = false;
				$temp_mc_gross = -1 * $mc_gross;
				$isPartialRefund = ($gross - $temp_mc_gross) > 0.01;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}
		
		// Check that PAYID has not been previously processed
		if($isValid && !is_null($subscription) && !$isPartialRefund) {
			if($subscription->processor_key == $data['PAYID']) {
				if($subscription->state == 'C') {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same PAYID twice";
				}
			}
		}
		
		// Check that CURRENCY is correct
		if($isValid && !is_null($subscription)) {
			$mc_currency = strtoupper($data['CURRENCY']);
			$currency = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
			if($mc_currency != $currency) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invalid currency; expected $currency, got $mc_currency";
			}
		}
		
		// Log the IPN data
		$this->logIPN($data, $isValid);
		
		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;

		// Check the payment_status
		switch($data['ACCEPTANCE'])
		{
			case 9:
				$newStatus = 'C';
				break;
			
			case 5:
			case 51:
			case 91:
			case 52:
			case 92:
				$newStatus = 'P';
				break;
			
			default:
				$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id'				=> $id,
			'processor_key'		=> $data['PAYID'],
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
	
	/**
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://e-payment.postfinance.ch/ncol/test/orderstandard_utf8.asp';
		} else {
			return 'https://e-payment.postfinance.ch/ncol/prod/orderstandard_utf8.asp';
		}
	}
	
	/**
	 * Gets the PostFinance SHA password
	 */
	private function getPassword($type = 'in')
	{
		if(!in_array($type,array('in','out'))) $type = 'in';
		
		$key = 'sha1'.$type;
		
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) $key = 'sandbox_'.$key;
		return $this->params->get($key,'');
	}	

	/**
	 * Calculates the SHA1 signature of a variables array
	 * 
	 * @param array $array The (hash) data array
	 * @param string $type in or out (in = sending data to PostFinance, out = receiving data)
	 * 
	 * @return string The SHA1 signature of the data array
	 */
	private function getSHA1(Array $array, $type = 'in')
	{
		// Initialise
		$sha1 = '';
		
		// Check the type
		if(!in_array($type,array('in','out'))) $type = 'in';
		
		// Filter out the variables which do not take part in the SHA1
		// calculation and normalise everything else
		$temp = array();
		foreach($array as $k => $v) {
			$k = strtoupper($k);
			if($k == 'SHASIGN') continue;
			if(empty($v)) continue;
			$temp[$k] = $v;
		}
		if(empty($temp)) return $sha1;
		
		// Alpha sort on the keys
		ksort($temp);
		
		// Get the password and calculate the SHA1 or SHA256 hash
		$password = $this->getPassword($type);
		$stringToSign = '';
		if(!empty($password)) {
			foreach ($temp as $key => $value) {
				if ($value)	$stringToSign .= $key.'='.$value.$password;
			}	
			if(function_exists('sha1')) {
				$stringToSign = iconv ( "UTF-8" , "ASCII//TRANSLIT" , $stringToSign );
				$sha1 = strtoupper(sha1($stringToSign));
			}
		}
		
		return $sha1;
	}
	
	/**
	 * Validates the incoming data against PayPal's IPN to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN($data)
	{
		$isValid = true;
		
		$sha1 = $this->getSHA1($data, 'out');
		
		if(!empty($sha1)) {
			$isValid = $data['SHASIGN'] == $sha1;
		}
		
		return $isValid;
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
		$logFile = $logpath.'/akpayment_postfinancech_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_postfinancech_ipn-1.php';
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
		$logData .= $isValid ? 'VALID POSTFINANCE.CH IPN' : 'INVALID POSTFINANCE.CH IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}