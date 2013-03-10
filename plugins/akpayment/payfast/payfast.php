<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentPayFast extends plgAkpaymentAbstract
{
	private $validHosts = array(
        'www.payfast.co.za',
        'sandbox.payfast.co.za',
        'w1w.payfast.co.za',
        'w2w.payfast.co.za',
        );

	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'payfast',
			'ppKey'			=> 'PLG_AKPAYMENT_PAYFAST_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/payfast_logo.png'
		));
		
		parent::__construct($subject, $config);
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
		
		$nameParts = explode(' ', trim($user->name), 2);
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
			// Receiver Details
			'url'					=> $this->getPaymentURL(),
			'merchant_id'			=> $this->getMerchantID(),
			'merchant_key'			=> $this->getMerchantKey(),
			'return_url'			=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel_url'			=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'notify_url'			=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=payfast',
			// Payer Details
			'name_first'			=> $firstName,
			'name_last'				=> $lastName,
			'email_address'			=> trim($user->email),
			// Transaction Details
			'amount'				=> sprintf('%.2f',$subscription->gross_amount),
			'item_name'				=> $level->title . ' - [' . $user->username . ']',
			'custom_str1'			=> $subscription->akeebasubs_subscription_id
		);
		
		// Signature
		$sigString = '';
		foreach($data as $key => $val ) {
			if($key == 'url') continue;
			$sigString .= $key . '=' . urlencode($val) . '&';
		}
		$sigString = substr($sigString, 0, -1);
		$data->signature = md5($sigString);

		@ob_start();
		include dirname(__FILE__).'/payfast/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
        
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['custom_str1'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The custom_str1 is invalid';
		}
        
		// Check that merchant_id is correct
		if($isValid && !is_null($subscription)) {
			if($this->getMerchantID() != $data['merchant_id']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "The received merchant_id does not match the one that was sent.";
			}
		}
        
		// Check that pf_payment_id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['pf_payment_id']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same pf_payment_id twice";
			}
		}
		
		// Check that amount_gross is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['amount_gross']);
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
			
		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;
		
		// Payment status
		if($data['payment_status'] == 'COMPLETE') {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['pf_payment_id'],
				'state'							=> $newStatus,
				'enabled'						=> 0
		);
		JLoader::import('joomla.utilities.date');
		if($newStatus == 'C') {
			$this->fixDates($subscription, $updates);
		}
		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		JLoader::import('joomla.plugin.helper');
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
			return 'https://sandbox.payfast.co.za/eng/process';
		} else {
			return 'https://www.payfast.co.za/eng/process';
		}
	}
	
	/**
	 * Gets the PayFast Merchant ID
	 */
	private function getMerchantID()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return '10000100';
		} else {
			return trim($this->params->get('merchant_id',''));
		}
	}
	/**
	 * Gets the PayFast Merchant Key
	 */
	private function getMerchantKey()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return '46f0cd694581a';
		} else {
			return trim($this->params->get('merchant_key',''));
		}
	}
	
	/**
	 * Gets the IPN callback URL
	 */
	private function getCallbackURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'ssl://sandbox.payfast.co.za';
		} else {
			return 'ssl://www.payfast.co.za';
		}
	}
    
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{			
		// 1. Check valid host
		$validIps = array();
		foreach($this->validHosts as $validHost){
			$ips = gethostbynamel($validHost);
			if($ips !== false) {
				$validIps = array_merge($validIps, $ips);	
			}
		}
		$validIps = array_unique($validIps);
		if(! in_array($_SERVER['REMOTE_ADDR'], $validIps)) {
			return false;
		}
	
		// 2. Check signature
		// Build returnString from 'm_payment_id' onwards and exclude 'signature'
		foreach($data as $key => $val ) {
				if($key == 'm_payment_id') $returnString = '';
				if(! isset($returnString)) continue;
				if($key == 'signature') continue;
				$returnString .= $key . '=' . urlencode($val) . '&';
		}
		$returnString = substr($returnString, 0, -1);
		
		if(md5($returnString) != $data['signature']) {
			return false;
		}
		
		// 3. Call PayFast server for validity check
		$header = "POST /eng/query/validate HTTP/1.0\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($returnString) . "\r\n\r\n";
		
		$fp = fsockopen($this->getCallbackURL(), 443, $errno, $errstr, 10);
		
		if (!$fp) {
			// HTTP ERROR
			return false;
		} else {
			fputs($fp, $header . $returnString);
			while(! feof($fp)) {
				$res = fgets($fp, 1024);
				if (strcmp($res, "VALID") == 0) {
					fclose($fp);
					return true;
				}
			}
		}
		
		fclose($fp);
		return false;
	}
}