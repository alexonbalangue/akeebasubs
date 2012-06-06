<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentAlloPass extends JPlugin
{
	private $ppName = 'allopass';
	private $ppKey = 'PLG_AKPAYMENT_ALLOPASS_TITLE';
	private $apMapping = array();

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
		$jlang->load('plg_akpayment_allopass', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_allopass', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_allopass', JPATH_ADMINISTRATOR, null, true);
		
		// Load level to alloPass mapping from plugin parameters	
 		$rawApMapping = $this->params->get('apmapping','');
		$this->apMapping = $this->parseAPMatching($rawApMapping);
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
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/allopass-logo.png';
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
		
		// AlloPass Info for this level
		$alloPass = $this->apMapping[$level->akeebasubs_level_id];
		if(empty($alloPass)) {
			return JError::raiseError(500, 'Cannot proceed with the payment. No alloPass information are definied for this subscription.');
		}
		
		// Payment url
		$rawPricingInfo = file_get_contents('https://payment.allopass.com/api/onetime/pricing.apu?'
				.'site_id=' . $alloPass['site_id'] . '&product_id=' . $alloPass['product_id']);
		$url = $this->getPaymentURL($rawPricingInfo, $alloPass['pp_id']);
		if(empty($url)) {
			return JError::raiseError(500, 'Cannot proceed with the payment. No URL found for this pricepoint.');
		}
		$url .= '&merchant_transaction_id=' . $subscription->akeebasubs_subscription_id;
		
		// Payment button
		$button = 'https://payment.allopass.com/static/buy/button/en/162x56.png';
		
		// Language settings
		try {
			$lang = strtolower(substr(JFactory::getLanguage()->getTag(), 0, 2));
			$url .= '&lang=' . $lang;
			// Button in English (default), French or German
			if($lang == 'fr' || $lang == 'de') {
				$button = 'https://payment.allopass.com/static/buy/button/' . $lang . '/162x56.png';
			}
		} catch(Exception $e) {
			// Shouldn't happend. But setting the language is optional... so do nothing here.
		}
		
		$data = (object)array(
			'url'			=> $url,
			'button'		=> $button
		);

		@ob_start();
		include dirname(__FILE__).'/allopass/form.php';
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
			$id = $data['merchant_transaction_id'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The merchant_transaction_id is invalid';
		}
        
		// Check that transaction_id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['transaction_id']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same transaction_id twice";
			}
		}
        
		// Check that currency is correct and set corresponding amount
		if($isValid && !is_null($subscription)) {
			$mc_currency = strtoupper($data['currency']);
			$currency = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
			if($mc_currency != $currency) {
				$mc_currency = strtoupper($data['payout_currency']);
				if($mc_currency != $currency) {
					$mc_currency = strtoupper($data['reference_currency']);
					if($mc_currency != $currency) {
						$isValid = false;
						$data['akeebasubs_failure_reason'] = "Invalid currency";
					} else {
						$mc_gross = floatval($data['reference_amount']);		
					}
				} else {
					$mc_gross = floatval($data['payout_amount']);	
				}
			} else {
				$mc_gross = floatval($data['amount']);	
			}
		}
		
		// Check that acount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
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
		
		
		// Check the payment_status
		switch($data['status'])
		{
			case -1:
				// Payment accepted
				$newStatus = 'P';
				break;
			case 0:
				// The transaction has been initiated but has not yet completed
				$newStatus = 'C';
				break;
			default:
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['transaction_id'],
				'state'							=> $newStatus,
				'enabled'						=> 0
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
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		// Init
		$signature = $data['api_sig'];
		unset($data['api_sig']);
		ksort($data);
		$secretKey = $this->params->get('skey','');
		$string2compute = '';
		foreach($data as $name => $val) {
			$string2compute .= $name . $val;
		}

		// Check signature
		$apiHash = 'sha1';
		if(!empty($data['api_hash'])) {
			$apiHash = $data['api_hash'];
		}
		if($apiHash == 'sha1') {
			return sha1($string2compute . $secretKey) == $signature;
		} else if($apiHash == 'md5') {
			return md5($string2compute . $secretKey) == $signature;
		}

		return false;
	}
 
	
	private function getPaymentURL($rawPricingInfo, $pricePointId){
		// Load XML
		$pricingDoc = new DOMDocument();
		$pricingDoc->loadXML($rawPricingInfo);
		$responseElem = $pricingDoc->getElementsByTagName('response')->item(0);
		// Check if response code is ok
		if($responseElem->getAttribute('code') != 0) {
			return null;
		}
		// Find the pricepoint
		$ppElems = $pricingDoc->getElementsByTagName('pricepoint');
		foreach ($ppElems as $ppElem) {
			$ppId = $ppElem->getAttribute('id');
			if($ppId == $pricePointId) {
				$urlElem = $ppElem->getElementsByTagName('buy_url')->item(0);
				$url = $urlElem->nodeValue;
				if(! empty($url)) {
					return $url;
				}
			}
		}
		return null;
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
		$logFile = $logpath.'/akpayment_allopass_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_allopass_ipn-1.php';
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
		$logData .= $isValid ? 'VALID ALLOPASS IPN' : 'INVALID ALLOPASS IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}

	private function parseAPMatching($rawData)
	{
		if(empty($rawData)) return array();
		
		$ret = array();
		
		// Just in case something funky happened...
		$rawData = str_replace("\\n", "\n", $rawData);
		$rawData = str_replace("\r", "\n", $rawData);
		$rawData = str_replace("\n\n", "\n", $rawData);
		
		$lines = explode("\n", $rawData);
		
		foreach($lines as $line) {
			$line = trim($line);
			$parts = explode('=', $line, 2);
			if(count($parts) != 2) continue;
			
			$level = trim($parts[0]);
			$levelId = $this->ASLevelToId($level);
			if($levelId < 0) continue;
			
			$rawAlloPass = $parts[1];
			$alloPass = explode(':', $rawAlloPass);
			if(empty($alloPass)) continue;
			if(count($alloPass) != 3) continue;
			
			$siteId = trim($alloPass[0]);
			if(empty($siteId)) continue;
			$productId = trim($alloPass[1]);
			if(empty($productId)) continue;
			$ppId = trim($alloPass[2]);
			if(empty($ppId)) continue;
		
			$pricePoint = array(
				'site_id'		=> $siteId,
				'product_id'	=> $productId,
				'pp_id'			=> $ppId
			);
			
			$ret[$levelId] = $pricePoint;
		}
		
		return $ret;
	}
	
	/**
	 * Converts an Akeeba Subscriptions level to a numeric ID
	 * 
	 * @param $title string The level's name to be converted to an ID
	 *
	 * @return int The subscription level's ID or -1 if no match is found
	 */
	private function ASLevelToId($title)
	{
		static $levels = null;
		
		// Don't process invalid titles
		if(empty($title)) return -1;
		
		// Fetch a list of subscription levels if we haven't done so already
		if(is_null($levels)) {
			$levels = array();
			$list = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->getList();
			if(count($list)) foreach($list as $level) {
				$thisTitle = strtoupper($level->title);
				$levels[$thisTitle] = $level->akeebasubs_level_id;
			}
		}
		
		$title = strtoupper($title);
		if(array_key_exists($title, $levels)) {
			// Mapping found
			return($levels[$title]);
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}
}