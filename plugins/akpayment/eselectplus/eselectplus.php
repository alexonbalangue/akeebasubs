<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentESelectPlus extends JPlugin
{
	private $ppName = 'eselectplus';
	private $ppKey = 'PLG_AKPAYMENT_ESELECTPLUS_TITLE';
	
	private $requestParams = array(
		'ca'	=> array (
			'url'					=> 'url',
			'merchant_id'			=> 'ps_store_id',
			'merchant_key'			=> 'hpp_key',
			'price_total'			=> 'charge_total',
			'item_id'				=> 'id1',
			'item_desc'				=> 'description1',
			'item_quantity'			=> 'quantity1',
			'item_price_unit'		=> 'price1',
			'price_taxes'			=> 'gst',
			'subscription_id'		=> 'rvarSubscriptionID',
			'customer_id'			=> 'cust_id',
			'customer_email'		=> 'email',
			'language'				=> 'lang',
			'order_id'				=> 'order_id',
			'bill_first_name'		=> 'bill_first_name',
			'bill_last_name'		=> 'bill_last_name',
			'bill_address'			=> 'bill_address_one',
			'bill_city'				=> 'bill_city',
			'bill_postal_code'		=> 'bill_postal_code',
			'bill_country'			=> 'bill_country',
			'bill_company'			=> 'bill_company_name',
			'bill_state'			=> 'bill_state_or_province',
			'ver_key'				=> 'transactionKey'
		),
		'us'	=> array (
			'url'					=> 'url',
			'merchant_id'			=> 'hpp_id',
			'merchant_key'			=> 'hpp_key',
			'price_total'			=> 'amount',
			'item_id'				=> 'li_id1',
			'item_desc'				=> 'li_description1',
			'item_quantity'			=> 'li_quantity1',
			'item_price_unit'		=> 'li_price1',
			'price_taxes'			=> 'li_taxes',
			'subscription_id'		=> 'rvarSubscriptionID',
			'customer_id'			=> 'cust_id',
			'customer_email'		=> 'client_email',
			'language'				=> 'lang',
			'order_id'				=> 'order_no',
			'bill_first_name'		=> 'od_bill_firstname',
			'bill_last_name'		=> 'od_bill_lastname',
			'bill_address'			=> 'od_bill_address',
			'bill_city'				=> 'od_bill_city',
			'bill_postal_code'		=> 'od_bill_zipcode',
			'bill_country'			=> 'od_bill_country',
			'bill_company'			=> 'od_bill_company',
			'bill_state'			=> 'od_bill_state',
			'ver_key'				=> 'verify_key'
		)
	);
	
	private $responseParams = array(
		'ca'	=> array (
			'subscription_id'		=> 'rvarSubscriptionID',
			'processor_key'			=> 'bank_transaction_id',
			'order_id'				=> 'response_order_id',
			'charge_total'			=> 'charge_total',
			'response_code'			=> 'response_code',
			'ver_key'				=> 'transactionKey',
			'transaction_type'		=> 'trans_name',
			'txn_num'				=> 'txn_num'
		),
		'us'	=> array (
			'subscription_id'		=> 'rvarSubscriptionID',
			'processor_key'			=> 'ref_num',
			'order_id'				=> 'order_no',
			'charge_total'			=> 'amount',
			'response_code'			=> 'response_code',
			'ver_key'				=> 'verify_key',
			'transaction_type'		=> 'txn_type',
			'txn_num'				=> 'txn_num'
		)
	);
	
	private $verificationParams = array(
		'ca'	=> array (
			'ver_key'		=> 'transactionKey',
			'order_id'		=> 'order_id',
			'response_code'	=> 'response_code',
			'charge_total'	=> 'amount',
			'txn_num'		=> 'txn_num',
			'status'		=> 'status'
		),
		'us'	=> array (
			'ver_key'		=> 'verify_key',
			'order_id'		=> 'order_no',
			'response_code'	=> 'response_code',
			'charge_total'	=> 'amount',
			'txn_num'		=> 'txn_num',
			'status'		=> 'message'
		)
	);

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
		$jlang->load('plg_akpayment_eselectplus', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_eselectplus', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_eselectplus', JPATH_ADMINISTRATOR, null, true);
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
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/pp_eSp_small.gif';
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
		
		$nameParts = explode(' ', trim($user->name), 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		

		$v = $this->params->get('version', 'ca');
		$p = $this->requestParams[$v];
		
		$data = (object)array(
			$p['url']					=> $this->getPaymentURL(),
			$p['merchant_id']			=> trim($this->params->get('store_id','')),
			$p['merchant_key']			=> trim($this->params->get('key','')),
			$p['price_total']			=> sprintf('%.2f',$subscription->gross_amount),
			// Item details
			$p['item_id']				=> $level->akeebasubs_level_id,
			$p['item_desc']				=> $level->title . ' - [ ' . $user->username . ' ]',
			$p['item_quantity']			=> 1,
			$p['item_price_unit']		=> sprintf('%.2f',$subscription->net_amount),
			// Transaction details
			$p['subscription_id']		=> $subscription->akeebasubs_subscription_id,
			$p['customer_id']			=> $user->username,
			$p['customer_email']		=> $user->email,
			$p['language']				=> $this->params->get('language','en-ca'),
			$p['price_taxes']			=> sprintf('%.2f',$subscription->tax_amount),
			// To have a unique order_id it consists of the level's title and the subscription's ID
			// in order to avoid that the order_id might be already used in the merchant's account.
			// The Id only is used for the parameter rvarSubscriptionID above.
			$p['order_id']				=> str_replace(' ', '', $level->title) . $subscription->akeebasubs_subscription_id,
			// Billing
			$p['bill_first_name']		=> $firstName,
			$p['bill_last_name']		=> $lastName,
			$p['bill_address']			=> trim($kuser->address1),
			$p['bill_city']				=> trim($kuser->city),
			$p['bill_postal_code']		=> trim($kuser->zip),
			$p['bill_country']			=> trim($kuser->country)
		);
		
		if($kuser->isbusiness) {
			$data->$p['bill_company'] = trim($kuser->businessname);
		}
		if(! empty($kuser->state)) {
			$data->$p['bill_state'] = trim($kuser->state);
		}

		@ob_start();
		include dirname(__FILE__).'/eselectplus/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		jimport('joomla.utilities.date');

		$v = $this->params->get('version', 'ca');
		$p = $this->responseParams[$v];
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$id = $data[$p['subscription_id']];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The ' . $data[$p['subscription_id']] . ' is invalid. ' . $data['message'];
		}
        
		// Check that bank_transaction_id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data[$p['processor_key']]) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same " . $data[$p['processor_key']] . " twice";
			}
		}

		// Check that charge_total is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data[$p['charge_total']]);
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

		// Check the response_code
		$response_code = (int) $data[$p['response_code']];
		if($response_code < 50) {
			// Transaction approved
			$transType = $data[$p['transaction_type']];
			if((!empty($transType)) && ($transType == 'preauth' || $transType == 'cavv_preauth')) {
				$newStatus = 'P';
			} else {
				$newStatus = 'C';
			}
		} else {
			// Transaction declined
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data[$p['processor_key']],
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
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$version = $this->params->get('version', 'ca');
		$sandbox = $this->params->get('sandbox', 0);
		if($version == 'ca') {
			if($sandbox) {
				return 'https://esqa.moneris.com/HPPDP/index.php';
			} else {
				return 'https://www3.moneris.com/HPPDP/index.php';
			}
		} else {
			if($sandbox) {
				return 'https://esplusqa.moneris.com/DPHPP/index.php';
			} else {
				return 'https://esplus.moneris.com/DPHPP/index.php';
			}
		}
	}

	private function getVerificationHost()
	{
		$version = $this->params->get('version', 'ca');
		$sandbox = $this->params->get('sandbox', 0);
		if($version == 'ca') {
			if($sandbox) {
				return 'ssl://esqa.moneris.com';
			} else {
				return 'ssl://www3.moneris.com';
			}
		} else {
			if($sandbox) {
				return 'ssl://esplusqa.moneris.com';
			} else {
				return 'ssl://esplus.moneris.com';
			}
		}
	}

	private function getVerificationFile()
	{
		$version = $this->params->get('version', 'ca');
		if($version == 'ca') {
			return '/HPPDP/verifyTxn.php';
		} else {
			return '/DPHPP/index.php';
		}
	}
	
	private function isValidIPN($data)
	{
		$v = $this->params->get('version', 'ca');
		$reqParams = $this->requestParams[$v]; // request
		$cbParams = $this->responseParams[$v]; // callback
		$verParams = $this->verificationParams[$v]; // verification
		
		// A empty transactionKey is possible if the Transaction Verification
		// is not enabled in the account settings
		if(! empty($data[$cbParams['ver_key']])) {
			
			// Build the verification request
			$req = $reqParams['merchant_id'] . '=' . urlencode(trim($this->params->get('store_id',''))) .
					'&' . $reqParams['merchant_key'] . '=' . urlencode(trim($this->params->get('key',''))) . 
					'&' . $reqParams['ver_key'] . '=' . urlencode($data[$cbParams['ver_key']]);
			$header = '';
			$header .= "POST " . $this->getVerificationFile() . " HTTP/1.0\r\n";
			$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
 
			// Send the request and get the result
			$fp = fsockopen($this->getVerificationHost(), 443, $errno, $errstr, 30);
		
			if (!$fp) {
				// HTTP ERROR
				return false;
			} else {
				fputs ($fp, $header . $req);
				while (!feof($fp)) {
					$res .= fgets ($fp, 1024);
				}
				fclose ($fp);
			}
			
			// Verify the result:
			// order_id, response_code, txn_num and charge_total
			// should be the same like in the original callback
			if(($this->getResponseValue($res, $verParams['order_id']) != $data[$cbParams['order_id']])
					|| ($this->getResponseValue($res, $verParams['response_code']) != $data[$cbParams['response_code']])
					|| (!empty($data[$cbParams['txn_num']]) && $this->getResponseValue($res, $verParams['txn_num']) != $data[$cbParams['txn_num']])
					|| ($this->getResponseValue($res, $verParams['charge_total']) != $data[$cbParams['charge_total']])) {
				return false;
			}
			// Check response code too for the Canadian version only
			if($v == 'ca' && $this->getResponseValue($res, $verParams['ver_key']) != $data[$cbParams['ver_key']]) {
				return false;
			}
			// The status is expected to start with 'Valid'
			if(! preg_match('/^Valid/', $this->getResponseValue($res, $verParams['status']))) {
				return false;
			}
		}
		return true;
	}
	
	private function getResponseValue($res, $param)
	{
		preg_match('/<input[^>]+' . $param . '[^>]+"([^>]+)"/', $res, $matches);
		return $matches[1];
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
		$logFile = $logpath.'/akpayment_eselectplus_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_eselectplus_ipn-1.php';
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
		$logData .= $isValid ? 'VALID ESELECTPLUS IPN' : 'INVALID ESELECTPLUS IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}