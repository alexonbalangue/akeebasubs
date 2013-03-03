<?php
/**
 * @package		akeebasubs
 * @copyright		Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentCashU extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'cashu',
			'ppKey'			=> 'PLG_AKPAYMENT_CASHU_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/logo_cashu_live.jpg',
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
		JLoader::import('joomla.utilities.date');
		
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
}