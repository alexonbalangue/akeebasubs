<?php
/**
 * @package		akeebasubs
 * @copyright		Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentCashU extends JPlugin
{
	private $ppName = 'cashu';
	private $ppKey = 'PLG_AKPAYMENT_CASHU_TITLE';

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
		$jlang->load('plg_akpayment_cashu', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_cashu', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_cashu', JPATH_ADMINISTRATOR, null, true);
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
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/logo_cashu_live.jpg';
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
		
		$data = (object)array(
			'url'					=> 'https://www.cashu.com/cgi-bin/pcashu.cgi',
			'merchant_id'			=> trim($this->params->get('merchant_id','')),
			'amount'				=> sprintf('%.2f', $subscription->gross_amount),
			// Accepted values: USD, CSH, AED, EUR, JOD, EGP, SAR, DZD, LBP, MAD, QAR, TRY
			'currency'				=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'language'				=> trim($this->params->get('language','en')),
			'display_text'			=> $level->title . ' - [' . $user->username . ']',
			'session_id'			=> str_replace(' ', '', $level->title) . '-' . $subscription->akeebasubs_subscription_id,
			'txt1'					=> $level->title,
			'txt2'					=> $subscription->akeebasubs_subscription_id,
			'test_mode'				=> $this->params->get('sandbox', 0)
		);
		
		$serviceName = trim($this->params->get('service_name',''));
		if($serviceName) {
			$data->service_name = $serviceName;
		}
		
		$enhanced_encryption = $this->params->get('enhanced_encryption', 0);
		if($enhanced_encryption) {
			$data->token = md5(
				strtolower($data->merchant_id) .
				':' . strtolower($data->amount) .
				':' . strtolower($data->currency) .
				':' . strtolower($data->session_id) .
				':' . trim($this->params->get('merchant_keyword',''))
				);	
		} else {
			$data->token = md5(
				strtolower($data->merchant_id) .
				':' . strtolower($data->amount) .
				':' . strtolower($data->currency) .
				':' . trim($this->params->get('merchant_keyword',''))
				);			
		}

		@ob_start();
		include dirname(__FILE__).'/cashu/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		jimport('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// This response is a XML document
		$dataDoc = new DOMDocument();
		$dataDoc->loadXML($data['sRequest']);
        
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($dataDoc);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';
		
		// Check if response code is OK
		if($isValid) {
			$isValid = $this->getDataVal($dataDoc, 'responseCode') == 'OK';
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'ResponseCode is not OK.';
		}
		
		// Check if merchant_id is the same
		if($isValid) {
			$mId = $this->getDataVal($dataDoc, 'merchant_id');
			$isValid = $mId == trim($this->params->get('merchant_id',''));
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The merchant_id is not correct: ' . $mId;
		}
		
		// Check if currency is the same
		if($isValid) {
			$cur = $this->getDataVal($dataDoc, 'currency');
			$isValid = strnatcasecmp($cur, AkeebasubsHelperCparams::getParam('currency','EUR')) == 0;
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The currency is not correct: ' . $cur;
		}

		// Load the relevant subscription row
		if($isValid) {
			$id = $this->getDataVal($dataDoc, 'txt2');
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The ORDER_NUMBER is invalid';
		}
        
		// Check that cashU_trnID has not been previously processed
		if($isValid && !is_null($subscription)) {
			$key = $this->getDataVal($dataDoc, 'cashU_trnID');
			if($subscription->processor_key == $key) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same cashU_trnID twice";
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($this->getDataVal($dataDoc, 'amount'));
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
		
		// Payment status always complete if this point is reached
		$newStatus = 'C';

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $key,
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

			$updates['publish_up'] = $jStart->toSql();
			$updates['publish_down'] = $jEnd->toSql();
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
	
	private function getDataVal($xmlDoc, $param) {
		$elem = $xmlDoc->getElementsByTagName($param)->item(0);
		return $elem->nodeValue;
	}
    
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($dataDoc)
	{
		$hashCode = md5(
				strtolower(trim($this->params->get('merchant_id',''))) .
				':' . $this->getDataVal($dataDoc, 'cashU_trnID') .
				':' . trim($this->params->get('merchant_keyword',''))
				);
		return $hashCode == $this->getDataVal($dataDoc, 'cashUToken');
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}
		$logFile = $logpath.'/akpayment_cashu_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_cashu_ipn-1.php';
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
		$logData .= $isValid ? 'VALID CASHU IPN' : 'INVALID CASHU IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}