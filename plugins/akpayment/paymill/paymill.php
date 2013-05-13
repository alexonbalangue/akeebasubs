<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentPaymill extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'paymill',
			'ppKey'			=> 'PLG_AKPAYMENT_PAYMILL_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/paymill.png',
		));

		parent::__construct($subject, $config);

		require_once __DIR__.'/paymill/lib/Services/Paymill/Base.php';
		require_once __DIR__.'/paymill/lib/Services/Paymill/Transactions.php';
		require_once __DIR__.'/paymill/lib/Services/Paymill/Clients.php';
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

		$doc = JFactory::getDocument();
		$doc->addScriptDeclaration(
			"\nvar PAYMILL_PUBLIC_KEY = '" . $this->getPublicKey() . "';\n");
		$doc->addScript("https://bridge.paymill.de/");
		$doc->addScriptDeclaration(
			'
				window.addEvent(\'domready\', function(){
					function PaymillResponseHandler(error, result) {
						$$(\'.control-group\').removeClass(\'error\');
						if (error) {
							if(error.apierror == \'3internal_server_error\') {
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_3INTERNAL_SERVER_ERROR') . '\');
							}else if(error.apierror == \'internal_server_error\') {
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_3INTERNAL_SERVER_ERROR') . '\');
							}else if(error.apierror == \'invalid_public_key\') {
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_PUBLIC_KEY') . '\');
							}else if(error.apierror == \'3ds_cancelled\') {
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_3DS_CANCELLED') . '\');
							}else if(error.apierror == \'field_invalid_card_number\') {
								$(\'control-group-card-number\').addClass(\'error\');
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_CARD_NUMBER') . '\');
							}else if(error.apierror == \'field_invalid_card_exp_year\') {
								$(\'control-group-card-expiry\').addClass(\'error\');
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_EXP_YEAR') . '\');
							}else if(error.apierror == \'field_invalid_card_exp_month\') {
								$(\'control-group-card-expiry\').addClass(\'error\');
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_EXP_MONTH') . '\');
							}else if(error.apierror == \'field_invalid_card_exp\') {
								$(\'control-group-card-expiry\').addClass(\'error\');
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_CARD_EXP') . '\');
							}else if(error.apierror == \'field_invalid_card_cvc\') {
								$(\'control-group-card-cvc\').addClass(\'error\');
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_CARD_CVC') . '\');
							}else if(error.apierror == \'field_invalid_card_holder\') {
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_INVALID_CARD_HOLDER') . '\');
							}else {
								$(\'payment-errors\').set(\'html\', \'' . JText::_('PLG_AKPAYMENT_PAYMILL_FORM_UNKNOWN_ERROR') . '\');
							}
							$(\'payment-errors\').setStyle(\'display\',\'\');
							$(\'payment-button\').set(\'disabled\', false);
						} else {
							$(\'payment-errors\').setStyle(\'display\',\'none\');
							var token = result.token;
							$(\'token\').set(\'value\', token);
							$(\'payment-form\').submit();
						}
					}

					$(\'payment-form\').addEvents({
						submit: function(){
							paymill.createToken({
								number:$(\'card-number\').value,
								exp_month:$(\'card-expiry-month\').value,
								exp_year:$(\'card-expiry-year\').value,
								cvc:$(\'card-cvc\').value,
								amount_int:$(\'amount\').value,
								currency:$(\'currency\').value,
								cardholder:$(\'card-holder\').value
							}, PaymillResponseHandler);
							$(\'payment-button\').set(\'disabled\', true);
							return false;
						}
					});
				});'."\n");

		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=paymill&sid='.$subscription->akeebasubs_subscription_id;
		$data = (object)array(
			'url'			=> $callbackUrl,
			'amount'		=> (int)($subscription->gross_amount * 100),
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'description'	=> $level->title . ' #' . $subscription->akeebasubs_subscription_id,
			'carholder'		=> $user->name,
		);

		@ob_start();
		include dirname(__FILE__).'/paymill/form.php';
		$html = @ob_get_clean();

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		$isValid = true;

		// Load the relevant subscription row
		$id = $data['sid'];
		$subscription = null;

		if ($id > 0)
		{
			$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->setId($id)
				->getItem();
			if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id) )
			{
				$subscription = null;
				$isValid = false;
			}
		}
		else
		{
			$isValid = false;
		}

		if (!$isValid)
		{
			$data['akeebasubs_failure_reason'] = 'The subscription ID is invalid';
		}

		if($isValid) {
			// Initialise common variables
			$apiKey = $this->getPrivateKey();
			$apiEndpoint = 'https://api.paymill.de/v2/';
			$client = '';

			try
			{
				$params = array(
					'amount'		=> $data['amount'],
					'currency'		=> $data['currency'],
					'token'			=> $data['token'],
					'description'	=> $data['description']
				);

				$transactionsObject = new Services_Paymill_Transactions(
					$apiKey, $apiEndpoint
				);
				$transaction = $transactionsObject->create($params);
			}
			catch(Exception $e)
			{
				$isValid = false;
				$data['akeebasubs_failure_reason'] = $e->getMessage();
			}
		}

		// Update the client record
		if($isValid)
		{
			$user = JFactory::getUser($subscription->user_id);

			try
			{
				$clientsObject = new Services_Paymill_Clients($apiKey, $apiEndpoint);
				$clientRecord = $transaction['client'];
				$clientparams = array(
					'id'			=> $clientRecord['id'],
					'email'			=> $user->email,
					'description'	=> $user->name . '(subscription #' . $subscription->akeebasubs_subscription_id . ')',
				);
			}
			catch (Exception $exc)
			{

			}
		}

		// Check that transaction has not been previously processed
		// Nicholas 2013-05-13: This does not seem to work with PayMill
		/**
		if ($isValid)
		{
			if($transaction['payment']['id'] == $subscription->processor_key)
			{
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not processe this transaction twice";
			}
		}
		**/

		// Check that amount is correct
		$isPartialRefund = false;

		if($isValid && !is_null($subscription))
		{
			$mc_gross = $transaction['amount'];
			$gross = (int)($subscription->gross_amount * 100);

			if ($mc_gross > 0)
			{
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			}
			else
			{
				$isPartialRefund = false;
				$temp_mc_gross = -1 * $mc_gross;
				$isPartialRefund = ($gross - $temp_mc_gross) > 0.01;
			}

			if (!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}

		if ($isValid)
		{
			if($this->params->get('sandbox') == $transaction['livemode'])
			{
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Transaction done in wrong mode.";
			}
		}

		// 2013-01-31 nicholas: I removed those checks because the credit card
		// information MUST NOT be sent back to the site. That's the whole point
		// of using the bridge Javascript to get a token: we send the CC info
		// directly to PayMill and let them return us a secure piece of data
		// (token) that we can use to charge our users. This allows secure
		// transactions even on servers not certified for PCI compliance.
		/*
		if($isValid) {
			if(substr($data['card-number'], -4) != $transaction['payment']['last4']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Creditcard number doesn't match.";
			}
		}

		if($isValid) {
			if($data['card-expiry-month'] != $transaction['payment']['expire_month']
					|| $data['card-expiry-year'] != $transaction['payment']['expire_year']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Expiry date doesn't match.";
			}
		}
		*/

		// Log the IPN data
		$this->logIPN($transaction, $isValid);

		// Fraud attempt? Do nothing more!
		if (!$isValid)
		{
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
		// Check the payment_status
		switch($transaction['status'])
		{
			case 'closed':
			case 'partial_refunded':
				$newStatus = 'C';
				break;

			case 'open':
			case 'pending':
			case 'preauthorize':
				$newStatus = 'P';
				break;

			case 'failed':
			case 'refunded':
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $transaction['payment']['id'],
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

		// Redirect the user to the "thank you" page
		$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$subscription->slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
		JFactory::getApplication()->redirect($thankyouUrl);
		return true;
	}

	private function getPublicKey()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return trim($this->params->get('sb_public_key',''));
		} else {
			return trim($this->params->get('public_key',''));
		}
	}

	private function getPrivateKey()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return trim($this->params->get('sb_private_key',''));
		} else {
			return trim($this->params->get('private_key',''));
		}
	}

	public function selectMonth()
	{
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 1; $i <= 12; $i++) {
			$m = sprintf('%02u', $i);
			$options[] = JHTML::_('select.option',$m,$m);
		}

		return JHTML::_('select.genericlist', $options, 'card-expiry-month', 'class="input-small"', 'value', 'text', '', 'card-expiry-month');
	}

	public function selectYear()
	{
		$year = gmdate('Y');

		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 0; $i <= 10; $i++) {
			$y = sprintf('%04u', $i+$year);
			$options[] = JHTML::_('select.option',$y,$y);
		}

		return JHTML::_('select.genericlist', $options, 'card-expiry-year', 'class="input-small"', 'value', 'text', '', 'card-expiry-year');
	}
}