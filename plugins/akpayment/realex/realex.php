<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentRealex extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'realex',
			'ppKey'			=> 'PLG_AKPAYMENT_REALEX_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/rlx_gen_logo.jpg'
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
		
		$timestamp = date('YmdHis');
		$data = (object)array(
			'URL'				=> 'https://epage.payandshop.com/epage.cgi',
			'MERCHANT_ID'		=> trim($this->params->get('merchant_id','')),
			'ORDER_ID'			=> $subscription->akeebasubs_subscription_id,
			'ACCOUNT'			=> trim($this->params->get('account','')),
			'AMOUNT'			=> (int)($subscription->gross_amount * 100),
			'CURRENCY'			=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'TIMESTAMP'			=> $timestamp,
			'AUTO_SETTLE_FLAG'	=> 1,
			'COMMENT1'			=> $level->title . ' #' . $subscription->akeebasubs_subscription_id
		);
		
		$sha1hash = sha1($data->TIMESTAMP
				. '.' . $data->MERCHANT_ID
				. '.' . $data->ORDER_ID
				. '.' . $data->AMOUNT
				. '.' . $data->CURRENCY);
		$sha1hash = sha1($sha1hash
				. '.' . trim($this->params->get('shared_secret','')));
		$data->SHA1HASH = $sha1hash;

		@ob_start();
		include dirname(__FILE__).'/realex/form.php';
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
			$id = $data['ORDER_ID'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The ORDER_ID is invalid';
		}
		
		// Check merchant ID
		if($isValid) {
			if(strtoupper($data['MERCHANT_ID']) != strtoupper(trim($this->params->get('merchant_id','')))) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Merchant ID doesn't match";
			}
		}
		
		// Check that transaction has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($data['PASREF'] == $subscription->processor_key) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same transcation twice";
			}
		}
		
		// Check that amount_gross is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = $data['AMOUNT'];
			$gross = (int)($subscription->gross_amount * 100);
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
		
		// Transaction result
		if($data['RESULT'] == '00' && !empty($data['AUTHCODE'])) {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['PASREF'],
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
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$sha1hash = sha1($data['TIMESTAMP']
				. '.' . $data['MERCHANT_ID']
				. '.' . $data['ORDER_ID']
				. '.' . $data['RESULT']
				. '.' . $data['MESSAGE']
				. '.' . $data['PASREF']
				. '.' . $data['AUTHCODE']);
		$sha1hash = sha1($sha1hash
				. '.' . trim($this->params->get('shared_secret','')));
		return $sha1hash == $data['SHA1HASH'];
	}
}