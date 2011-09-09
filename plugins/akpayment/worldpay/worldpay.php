<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentWorldpay extends JPlugin
{
	private $ppName = 'worldpay';
	private $ppKey = 'PLG_AKPAYMENT_WORLDPAY_TITLE';

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		
		// Load the language files
		$jlang =& JFactory::getLanguage();
		$jlang->load('plg_akpayment_worldpay', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_worldpay', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_worldpay', JPATH_ADMINISTRATOR, null, true);
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
	 * @param KDatabaseRow $level
	 * @param KDatabaseRow $subscription
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
		
		$slug = KFactory::get('com://admin/akeebasubs.model.levels')
				->id($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		$data = (object)array(
			'url'			=> $this->getPaymentURL(),
			'test'			=> ($this->params->get('sandbox',0) ? '0' : '100'),
			'instid'		=> $this->params->get('instid',''),
			'postback'		=> $rootURL.str_replace('&amp;','&',JRoute::_('/index.php?option=com_akeebasubs&view=callback&paymentmethod=worldpay')),
			'success'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=order')),
			'cancel'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=cancel')),
			'currency'		=> strtoupper(KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->currency),
			'firstname'		=> $firstName,
			'lastname'		=> $lastName
		);
		
		$kuser = KFactory::get('com://admin/akeebasubs.model.users')
			->user_id($user->id)
			->getItem();

		@ob_start();
		include dirname(__FILE__).DS.'worldpay'.DS.'form.php';
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
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Callback Passsword in request does not match the preset value';
		
		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('cartId', $data) ? (int)$data['cartId'] : -1;
			$subscription = null;
			if($id > 0) {
				$subscription = KFactory::get('com://admin/akeebasubs.model.subscriptions')
					->id($id)
					->getItem();
				if( ($subscription->id <= 0) || ($subscription->id != $id) ) {
					$subscription = null;
					$isValid = false;
				}
			} else {
				$isValid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("cartId" field) is invalid';
		}
		
		// Check that intallation ID is what the site owner has configured
		if($isValid) {
			$incomingID = $data['instId'];
			$valid_id = $this->params->get('instid','');
			$isValid = ($incomingID == $valid_id);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Installation ID does not match incoming InstID';
		}
		
		// Check that transId has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['transId']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same transId twice";
			}
		}
		
		// Check that transId is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['transId']);
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
		
		// Check that currency is correct
		if($isValid && !is_null($subscription)) {
			$mc_currency = strtoupper($data['currency']);
			$currency = strtoupper(KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->currency);
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
		switch($data['transStatus'])
		{
			case 'Y':
				if($data['authMode'] == 'A') {
					$newStatus = 'C';
				} else {
					$newStatus = 'P';
				}
				break;
			
			case 'C':
			default:
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'id'				=> $id,
			'processor_key'		=> $data['transId'],
			'state'				=> $newStatus,
			'enabled'			=> 0
		);
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
		$subscription->setData($updates)->save();
		
		return true;
	}
	
	/**
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://secure-test.wp3.rbsworldpay.com/wcc/purchase';
		} else {
			return 'https://secure.wp3.rbsworldpay.com/wcc/purchase';
		}
	}
	
	private function isValidIPN($data)
	{
		// Check the password
		$callbackpw = $this->params->get('callbackpw','');
		if(empty($callbackpw)) return true;
		
		$check = $data['callbackPW'];
		return $check == $callbackpw;
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
		$logFile = $logpath.'/akpayment_worldpay_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.DS.'akpayment_worldpay_ipn-1.php';
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
		$logData .= $isValid ? 'VALID WORLDPAY IPN' : 'INVALID WORLDPAY IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}