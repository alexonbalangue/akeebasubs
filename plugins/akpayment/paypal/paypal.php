<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentPaypal extends JPlugin
{
	private $ppName = 'paypal';
	private $ppKey = 'PLG_AKPAYMENT_PAYPAL_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_paypal', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_paypal', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_paypal', JPATH_ADMINISTRATOR, null, true);
	}

	public function onAKPaymentGetIdentity()
	{
		$title = $this->params->get('title','');
		if(empty($title)) $title = JText::_($this->ppKey);
		$ret = array(
			'name'		=> $this->ppName,
			'title'		=> $title
		);
		$ret['image'] = trim($this->params->get('ppimage',''));
		if(empty($ret['image'])) {
			$ret['image'] = 'https://www.paypal.com/en_US/i/bnr/horizontal_solution_PPeCheck.gif';
		}
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
		
		$nameParts = explode(' ', $user->name, 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		
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
			'url'			=> $this->getPaymentURL(),
			'merchant'		=> $this->getMerchantID(),
			//'postback'		=> rtrim(JURI::base(),'/').str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=callback&paymentmethod=paypal')),
			'postback'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=paypal',
			'success'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'firstname'		=> $firstName,
			'lastname'		=> $lastName,
			'cmd'			=> $level->recurring ? '_xclick-subscriptions' : '_xclick',
			'recurring'		=> $level->recurring ? true : false
		);
		
		if($level->recurring) {
			$ppDuration = $this->_toPPDuration($level->duration);
			$data->t3 = $ppDuration->unit;
			$data->p3 = $ppDuration->value;
		}
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		@ob_start();
		include dirname(__FILE__).'/paypal/form.php';
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
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'PayPal reports transaction as invalid';
		
		// Check txn_type; we only accept web_accept transactions with this plugin
		if($isValid) {
			$validTypes = array('web_accept','recurring_payment','subscr_payment');
			$isValid = in_array($data['txn_type'], $validTypes);
			if(!$isValid) {
				$data['akeebasubs_failure_reason'] = "Transaction type ".$data['txn_type']." can't be processed by this payment plugin.";
			} else {
				$recurring = ($data['txn_type'] != 'web_accept');
			}
		}
		
		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('custom', $data) ? (int)$data['custom'] : -1;
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
		
		// Check that receiver_email / receiver_id is what the site owner has configured
		if($isValid) {
			$receiver_email = $data['receiver_email'];
			$receiver_id = $data['receiver_id'];
			$valid_id = $this->getMerchantID();
			$isValid =
				($receiver_email == $valid_id)
				|| (strtolower($receiver_email) == strtolower($receiver_email))
				|| ($receiver_id == $valid_id)
				|| (strtolower($receiver_id) == strtolower($receiver_id))
			;
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Merchant ID does not match receiver_email or receiver_id';
		}
		
		// Check that mc_gross is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['mc_gross']);
			
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
		
		// Check that txn_id has not been previously processed
		if($isValid && !is_null($subscription) && !$isPartialRefund) {
			if($subscription->processor_key == $data['txn_id']) {
				if($subscription->state == 'C') {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same txn_id twice";
				}
			}
		}
		
		// Check that mc_currency is correct
		if($isValid && !is_null($subscription)) {
			$mc_currency = strtoupper($data['mc_currency']);
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
		switch($data['payment_status'])
		{
			case 'Canceled_Reversal':
			case 'Completed':
				$newStatus = 'C';
				break;
			
			case 'Created':
			case 'Pending':
			case 'Processed':
				$newStatus = 'P';
				break;
			
			case 'Denied':
			case 'Expired':
			case 'Failed':
			case 'Refunded':
			case 'Reversed':
			case 'Voided':
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
			'akeebasubs_subscription_id'				=> $id,
			'processor_key'		=> $data['txn_id'],
			'state'				=> $newStatus,
			'enabled'			=> 0
		);
		jimport('joomla.utilities.date');
		if($newStatus == 'C') {
			// Fix the starting date if the payment was accepted after the subscription's start date. This
			// works around the case where someone pays by e-Check on January 1st and the check is cleared
			// on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
			// the user would have paid us and we'd never given him a subscription!
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $subscription->publish_up)) {
				$subscription->publish_up = '2001-01-01';
			}
			if(!preg_match($regex, $subscription->publish_down)) {
				$subscription->publish_down = '2037-01-01';
			}
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
			
			$updates['publish_up'] = $jStart->toSql();
			$updates['publish_down'] = $jEnd->toSql();
			$updates['enabled'] = 1;

		}
		// In the case of a successful recurring payment, fetch the old subscription's data
		if($recurring && ($newStatus == 'C') && ($subscription->state == 'C')) {
			// Create a new record for the old subscription
			$oldData = $subscription->getData();
			$oldData['akeebasubs_subscription_id'] = 0;
			$oldData['publish_down'] = $jNow->toSql();
			$oldData['enabled'] = 0;
			$oldData['contact_flag'] = 3;
			$oldData['notes'] = "Automatically renewed subscription on ".$jNow->toSql();			
			
			// Calculate new start/end time for the subscription
			$allSubs = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
				->paystate('C')
				->level($subscription->akeebasubs_level_id)
				->user_id($subscription->user_id);
			$max_expire = 0;
			if(count($allSubs)) foreach($allSubs as $aSub) {
				$jExpire = new JDate($aSub->publish_down);
				$expire = $jExpire->toUnix();
				if($expire > $max_expire) $max_expire = $expire;
			}
			
			$duration = $end - $start;
			$start = max($now, $max_expire);
			$end = $start + $duration;
			$jStart = new JDate($start);
			$jEnd = new JDate($end);
			
			$updates['publish_up'] = $jStart->toSql();
			$updates['publish_down'] = $jEnd->toSql();
			
			// Save the record for the old subscription
			$table = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
				->getTable();
			$table->reset();
			$table->bind($oldData);
			$table->store();
		}
		// Save the changes
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
			return 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		} else {
			return 'https://www.paypal.com/cgi-bin/webscr';
		}
	}
	
	/**
	 * Gets the PayPal Merchant ID (usually the email address)
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
	 * Gets the IPN callback URL
	 */
	private function getCallbackURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		$ssl = $this->params->get('secureipn',0);
		$scheme = $ssl ? 'ssl://' : '';
		if($sandbox) {
			return $scheme.'www.sandbox.paypal.com';
		} else {
			return $scheme.'www.paypal.com';
		}
	}
	
	/**
	 * Validates the incoming data against PayPal's IPN to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN($data)
	{
		$url = $this->getCallbackURL();
		
		$req = 'cmd=_notify-validate';
		foreach($data as $key => $value) {
			$value = urlencode($value);
			$req .= "&$key=$value";
		}
		$header = '';
		$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
		
		$ssl = $this->params->get('secureipn',0);
		$port = $ssl ? 443 : 80;
		
		$fp = fsockopen ($url, $port, $errno, $errstr, 30);
		
		if (!$fp) {
			// HTTP ERROR
			return false;
		} else {
			fputs ($fp, $header . $req);
			while (!feof($fp)) {
				$res = fgets ($fp, 1024);
				if (strcmp ($res, "VERIFIED") == 0) {
					return true;
				} else if (strcmp ($res, "INVALID") == 0) {
					return false;
				}
			}
			fclose ($fp);
		}
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}
		$logFile = $logpath.'/akpayment_paypal_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_paypal_ipn-1.php';
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
		$logData .= $isValid ? 'VALID PAYPAL IPN' : 'INVALID PAYPAL IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
	
	private function _toPPDuration($days)
	{
		$ret = (object)array(
			'unit'		=> 'D',
			'value'		=> $days
		);

		// 0-90 => return days
		if($days < 90) return $ret;

		// Translate to weeks, months and years
		$weeks = (int)($days / 7);
		$months = (int)($days / 30);
		$years = (int)($days / 365);

		// Find which one is the closest match
		$deltaW = abs($days - $weeks*7);
		$deltaM = abs($days - $months*30);
		$deltaY = abs($days - $years*365);
		$minDelta = min($deltaW, $deltaM, $deltaY);

		// Counting weeks gives a better approximation
		if($minDelta == $deltaW) {
			$ret->unit = 'W';
			$ret->value = $weeks;

			// Make sure we have 1-52 weeks, otherwise go for a months or years
			if(($ret->value > 0) && ($ret->value <= 52)) {
				return $ret;
			} else {
				$minDelta = min($deltaM, $deltaY);
			}
		}

		// Counting months gives a better approximation
		if($minDelta == $deltaM) {
			$ret->unit = 'M';
			$ret->value = $months;

			// Make sure we have 1-24 month, otherwise go for years
			if(($ret->value > 0) && ($ret->value <= 24)) {
				return $ret;
			} else {
				$minDelta = min($deltaM, $deltaY);
			}
		}

		// If we're here, we're better off translating to years
		$ret->unit = 'Y';
		$ret->value = $years;

		if($ret->value < 0) {
			// Too short? Make it 1 (should never happen)
			$ret->value = 1;
		} elseif($ret->value > 5) {
			// One major pitfall. You can't have renewal periods over 5 years.
			$ret->value = 5;
		}

		return $ret;
	}
}