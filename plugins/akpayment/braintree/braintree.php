<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentBraintree extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'braintree',
			'ppKey'			=> 'PLG_AKPAYMENT_BRAINTREE_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/braintree-logo.jpg',
		));
		
		parent::__construct($subject, $config);
		
		require_once dirname(__FILE__).'/braintree/lib/Braintree.php';
		
		$sandbox = $this->params->get('sandbox',0);
		$merchantId = trim($this->params->get('merchant_id',''));
		$pubKey = trim($this->params->get('pub_key',''));
		$privKey = trim($this->params->get('priv_key',''));
		$environment = $sandbox ? 'sandbox' : 'production';
		Braintree_Configuration::environment($environment);
		Braintree_Configuration::merchantId($merchantId);
		Braintree_Configuration::publicKey($pubKey);
		Braintree_Configuration::privateKey($privKey);
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
		
		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=braintree&sid='.$subscription->akeebasubs_subscription_id;
		$data = Braintree_TransparentRedirect::transactionData(
			array(
				'transaction' => array(
					'type'		=> Braintree_Transaction::SALE,
					'amount'	=> sprintf('%.2f', $subscription->gross_amount),
					'options'	=> array('submitForSettlement' => true)
				),
				'redirectUrl' => $callbackUrl
			)
		);

		@ob_start();
		include dirname(__FILE__).'/braintree/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		jimport('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		$isValid = true;
		
		// Load the relevant subscription row
		$id = $data['sid'];
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
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'The subscription ID is invalid';
		
		if($isValid) {
			try {
				$queryString = JURI::getInstance()->getQuery();
				$result = Braintree_TransparentRedirect::confirm($queryString);
				if(! $result->success) {
					$isValid = false;
					$errors = $result->errors->deepAll();
					$data['akeebasubs_failure_reason'] = $errors[0]->message;
				}	
			}catch(Exception $e) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = $e->getMessage();
			}
		}
		// Check that transaction id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $result->transaction->id && $subscription->state == 'C') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same order twice";
			}
		}
        
		// Check that transaction type
		if($isValid) {
			if($result->transaction->type != 'sale') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Wrong type " . $result->transaction->type;
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = $result->transaction->amount;
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
		if(!$isValid) {
			$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem();
			$error_url = 'index.php?option='.JRequest::getCmd('option').
				'&view=level&slug='.$level->slug.
				'&layout='.JRequest::getCmd('layout','default');
			$error_url = JRoute::_($error_url,false);
			JFactory::getApplication()->redirect($error_url,$data['akeebasubs_failure_reason'],'error');
			return false;
		}
		
		// Payment status
		if($result->transaction->status == 'submitted_for_settlement') {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $result->transaction->id,
				'state'							=> $newStatus,
				'enabled'						=> 0
		);
		jimport('joomla.utilities.date');
		if($newStatus == 'C') {
			$this->fixDates($subscription, $updates);
		}
		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array(
			$subscription
		));
		
		// Redirect the user to the "thank you" page
		$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$subscription->slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
		JFactory::getApplication()->redirect($thankyouUrl);
		return true;
	}
	
	private function selectExpirationDate()
	{
		$year = gmdate('Y');
		
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 0; $i <= 10; $i++) {
			$y = sprintf('%04u', $i+$year);
			for($j = 1; $j <= 12; $j++) {
				$m = sprintf('%02u', $j);
				$options[] = JHTML::_('select.option', ($m.'/'.$y), ($m.'/'.$y));
			}
		}
		
		return JHTML::_('select.genericlist', $options, 'transaction[credit_card][expiration_date]', 'class="input-medium"', 'value', 'text', '', 'braintree_credit_card_exp');
	}
}