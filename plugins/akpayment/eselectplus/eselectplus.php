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
		
		$grossAcount = sprintf('%.2f',$subscription->gross_amount);
		
		$data = (object)array(
			'url'					=> $this->getPaymentURL(),
			'ps_store_id'			=> trim($this->params->get('store_id','')),
			'hpp_key'				=> trim($this->params->get('key','')),
			'charge_total'			=> $grossAcount,
			// Item details
			'id1'					=> $level->akeebasubs_level_id,
			'description1'			=> $level->title . ' - [ ' . $user->username . ' ]',
			'quantity1'				=> 1,
			'price1'				=> $grossAcount,
			'subtotal1'				=> $grossAcount,
			// Transaction details
			'rvarSubscriptionID'	=> $subscription->akeebasubs_subscription_id,
			'cust_id'				=> $user->username,
			'lang'					=> $this->params->get('language','en-ca'),
			'gst'					=> $subscription->tax_amount,
			// To have a unique order_id it consists of the level's title and the subscription's ID
			// in order to avoid that the order_id might be already used in the merchant's account.
			// The Id only is used for the parameter rvarSubscriptionID above.
			'order_id'				=> str_replace(' ', '', $level->title) . $subscription->akeebasubs_subscription_id,
			// CVD always present
			'cvd_indicator'			=> 1,
			// Billing
			'bill_first_name'		=> $firstName,
			'bill_last_name'		=> $lastName,
			'bill_address_one'		=> trim($kuser->address1),
			'bill_city'				=> trim($kuser->city),
			'bill_postal_code'		=> trim($kuser->zip),
			'bill_country'			=> trim($kuser->country)
		);
		
		if(! empty($kuser->businessname)) {
			$data->bill_company_name = trim($kuser->businessname);
		}
		if(! empty($kuser->state)) {
			$data->bill_state_or_province = trim($kuser->state);
		}

		@ob_start();
		include dirname(__FILE__).'/eselectplus/form.php';
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
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['rvarSubscriptionID'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The orderId is invalid. ' . $data['message'];
		}
		
		// Check CVD response code
		if($data['cvd_response_code'] == 'N') {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = "The Card Validation Digits (CVD) don't match. " . $data['message'];
		}
        
		// Check that bank_transaction_id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['bank_transaction_id']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same bank_transaction_id twice";
			}
		}

		// Check that charge_total is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['charge_total']);
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
		$response_code = (int) $data['response_code'];
		if($response_code < 50) {
			// Transaction approved
			$transName = $data['trans_name'];
			if((!empty($transName)) && ($transName == 'preauth' || $transName == 'cavv_preauth')) {
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
				'processor_key'					=> $data['bank_transaction_id'],
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
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://esqa.moneris.com/HPPDP/index.php';
		} else {
			return 'https://www3.moneris.com/HPPDP/index.php';
		}
	}

	/**
	 * Gets the verification URL
	 */
	private function getVerificationHost()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'ssl://esqa.moneris.com';
		} else {
			return 'ssl://www3.moneris.com';
		}
	}
	
	private function isValidIPN($data)
	{
		// A empty transactionKey is possible if the Transaction Verification
		// is not enabled in the account settings
		if(! empty($data['transactionKey'])) {
			
			// Build the verification request
			$req = 'ps_store_id=' . urlencode(trim($this->params->get('store_id',''))) .
					'&hpp_key=' . urlencode(trim($this->params->get('key',''))) . 
					'&transactionKey=' . urlencode($data['transactionKey']);
			$header = '';
			$header .= "POST /HPPDP/verifyTxn.php HTTP/1.0\r\n";
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
			// order_id, transactionKey, amount and response_code
			// should be the same like in the original response
			if(($this->getResponseValue($res, 'order_id') != $data['response_order_id'])
					|| ($this->getResponseValue($res, 'transactionKey') != $data['transactionKey'])
					|| ($this->getResponseValue($res, 'response_code') != $data['response_code'])
					|| ($this->getResponseValue($res, 'amount') != $data['charge_total'])) {
				return false;
			}
			// The status is expected to start with 'Valid'
			if(! preg_match('/^Valid/', $this->getResponseValue($res, 'status'))) {
				return false;
			}
		}
		return true;
	}
	
	private function getResponseValue($res, $param)
	{	
		preg_match('/' . $param . '.*value="(.*)"/', $res, $matches);
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
	
	public function selectMonth()
	{
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 1; $i <= 12; $i++) {
			$options[] = JHTML::_('select.option', sprintf('%02u', $i), sprintf('%02u', $i));
		}
		
		return JHTML::_('select.genericlist', $options, 'expMonth', '', 'value', 'text', '', 'expMonth');
	}
	
	public function selectYear()
	{
		$year = gmdate('Y');
		
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 0; $i <= 10; $i++) {
			$options[] = JHTML::_('select.option', sprintf('%02u', ($i+$year) % 100), sprintf('%04u', $i+$year));
		}
		
		return JHTML::_('select.genericlist', $options, 'expYear', '', 'value', 'text', '', 'expYear');
	}
}

class Logging {
    // define default log file
    private $log_file = '/tmp/logfile.log';
    // define default newline character
    private $nl = "\n";
    // define file pointer
    private $fp = null;
    // set log file (path and name)
    public function lfile($path) {
        $this->log_file = $path;
    }
    // write message to the log file
    public function lwrite($message) {
        // if file pointer doesn't exist, then open log file
        if (!$this->fp) {
            $this->lopen();
        }
        // define script name
        $script_name = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
        // define current time
        $time = date('H:i:s');
        // write current time, script name and message to the log file
        fwrite($this->fp, "$time ($script_name) $message". $this->nl);
    }
    // close log file (it's always a good idea to close a file when you're done with it)
    public function lclose() {
        fclose($this->fp);
    }
    // open log file
    private function lopen() {
        // define log file path and name
        $lfile = $this->log_file;
        // set newline character to "\r\n" if script is used on Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->nl = "\r\n";
        }
        // define the current date (it will be appended to the log file name)
        $today = date('Y-m-d');
        // open log file for writing only; place the file pointer at the end of the file
        // if the file does not exist, attempt to create it
        $this->fp = fopen($lfile . '_' . $today, 'a') or exit("Can't open $lfile!");
    }
}