<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentStripe extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'stripe',
			'ppKey'			=> 'PLG_AKPAYMENT_STRIPE_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/stripe-logo.png',
		));
		
		parent::__construct($subject, $config);
		
		require_once dirname(__FILE__).'/stripe/lib/Stripe.php';
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
		$doc->addScriptDeclaration("
			Stripe.setPublishableKey('" . $this->getPublicKey() . "');
			");
		$doc->addScript("https://js.stripe.com/v2/");
		if(version_compare(JVERSION, '3.0', 'ge')) {
			// jQuery
			$doc->addScriptDeclaration("
				jQuery(function($){
					var stripeResponseHandler = function(status, response) {
						$('.control-group').removeClass('error');
						if (response.error) {
							if(response.error.code == 'incorrect_number') {
								$('#control-group-card-number').addClass('error');
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INCORRECT_NUMBER') . "\");
							}else if(response.error.code == 'invalid_number') {
								$('#control-group-card-number').addClass('error');
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INVALID_NUMBER') . "\");
							}else if(response.error.code == 'invalid_expiry_month') {
								$('#control-group-card-expiry').addClass('error');
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INVALID_EXP_MONTH') . "\");
							}else if(response.error.code == 'invalid_expiry_year') {
								$('#control-group-card-expiry').addClass('error');
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INVALID_EXP_YEAR') . "\");
							}else if(response.error.code == 'invalid_cvc') {
								$('#control-group-card-cvc').addClass('error');
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INVALID_CVC') . "\");
							}else if(response.error.code == 'expired_card') {
								$('#control-group-card-expiry').addClass('error');
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_EXPIRED_CARD') . "\");
							}else if(response.error.code == 'incorrect_cvc') {
								$('#control-group-card-cvc').addClass('error');
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INCORRECT_CVC') . "\");
							}else if(response.error.code == 'card_declined') {
								$('#control-group-card-number').addClass('error');
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_CARD_DECLINED') . "\");
							}else if(response.error.code == 'missing') {
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_MISSING') . "\");
							}else if(response.error.code == 'processing_error') {
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_PROCESSING_ERROR') . "\");
							}else if(status == 401) {
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_UNAUTHORIZED') . "\");
							}else if(status == 402) {
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_REQUEST_FAILED') . "\");
							}else if(status == 404) {
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_NOT_FOUND') . "\");
							}else if(status >= 500) {
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_SERVER_ERROR') . "\");
							}else {
								$('#payment-errors').text(\"" . JText::_('PLG_AKPAYMENT_STRIPE_FORM_UNKNOWN_ERROR') . "\");
							}
							$('#payment-errors').show();
							$('#payment-button').removeAttr('disabled');
						} else {
							$('#payment-errors').hide();
							var token = response.id;
							$('#token').val(token);
							$('#payment-form').submit();
						}
					};

					$('#payment-form').submit(function(e){
						var token = $('#token').val();
						if(!!token) {
							return true;
						}else{							
							$('#payment-button').attr('disabled', 'disabled');
							Stripe.createToken({
								number:$('#card-number').val(),
								exp_month:$('#card-expiry-month').val(),
								exp_year:$('#card-expiry-year').val(),
								cvc:$('#card-cvc').val()
							}, stripeResponseHandler);
							return false;
						}
					});
				});
			");
		} else {
			// Mootools
			$doc->addScriptDeclaration("\n" . 'window.addEvent(\'domready\', function(){
						function StripeResponseHandler(status, response) {
							$$(\'.control-group\').removeClass(\'error\');
							if (response.error) {
								if(response.error.code == \'incorrect_number\') {
									$(\'control-group-card-number\').addClass(\'error\');
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INCORRECT_NUMBER') . '");
								}else if(response.error.code == \'invalid_number\') {
									$(\'control-group-card-number\').addClass(\'error\');
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INVALID_NUMBER') . '");
								}else if(response.error.code == \'invalid_expiry_month\') {
									$(\'control-group-card-expiry\').addClass(\'error\');
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INVALID_EXP_MONTH') . '");
								}else if(response.error.code == \'invalid_expiry_year\') {
									$(\'control-group-card-expiry\').addClass(\'error\');
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INVALID_EXP_YEAR') . '");
								}else if(response.error.code == \'invalid_cvc\') {
									$(\'control-group-card-cvc\').addClass(\'error\');
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INVALID_CVC') . '");
								}else if(response.error.code == \'expired_card\') {
									$(\'control-group-card-expiry\').addClass(\'error\');
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_EXPIRED_CARD') . '");
								}else if(response.error.code == \'incorrect_cvc\') {
									$(\'control-group-card-cvc\').addClass(\'error\');
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_INCORRECT_CVC') . '");
								}else if(response.error.code == \'card_declined\') {
									$(\'control-group-card-number\').addClass(\'error\');
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_CARD_DECLINED') . '");
								}else if(response.error.code == \'missing\') {
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_MISSING') . '");
								}else if(response.error.code == \'processing_error\') {
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_PROCESSING_ERROR') . '");
								}else if(status == 401) {
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_UNAUTHORIZED') . '");
								}else if(status == 402) {
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_REQUEST_FAILED') . '");
								}else if(status == 404) {
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_NOT_FOUND') . '");
								}else if(status >= 500) {
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_SERVER_ERROR') . '");
								}else {
									$(\'payment-errors\').set(\'html\', "' . JText::_('PLG_AKPAYMENT_STRIPE_FORM_UNKNOWN_ERROR') . '");
								}
								$(\'payment-errors\').setStyle(\'display\',\'\');
								$(\'payment-button\').set(\'disabled\', false);
							} else {
								$(\'payment-errors\').setStyle(\'display\',\'none\');
								var token = response.id;
								$(\'token\').set(\'value\', token);
								$(\'payment-form\').submit();
							}
						}

						$(\'payment-form\').addEvents({
							submit: function(){
								Stripe.createToken({
									number:$(\'card-number\').value,
									exp_month:$(\'card-expiry-month\').value,
									exp_year:$(\'card-expiry-year\').value,
									cvc:$(\'card-cvc\').value
								}, StripeResponseHandler);
								$(\'payment-button\').set(\'disabled\', true);
								return false;
							}
						});
					});' . "\n");
		}
		
		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=stripe&sid='.$subscription->akeebasubs_subscription_id;
		$data = (object)array(
			'url'			=> $callbackUrl,
			'amount'		=> (int)($subscription->gross_amount * 100),
			'currency'		=> strtolower(AkeebasubsHelperCparams::getParam('currency','usd')),
			'description'	=> $level->title . ' #' . $subscription->akeebasubs_subscription_id,
			'cardholder'	=> $user->name
		);

		@ob_start();
		include dirname(__FILE__).'/stripe/form.php';
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
				$apiKey = $this->getPrivateKey();
				Stripe::setApiKey($apiKey);
				$params = array(
					'amount'		=> $data['amount'],
					'currency'		=> $data['currency'],
					'card'			=> $data['token'],
					'description'	=> $data['description']
				);
				$transaction = Stripe_Charge::create($params);	
			}catch(Exception $e) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = $e->getMessage();
			}
		}
		
		if($isValid && !empty($transaction['failure_message'])) {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = "Stripe failure: " . $transaction['failure_message'];
		}
        
		// Check that transaction has not been previously processed
		if($isValid) {
			if($transaction['id'] == $subscription->processor_key) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not processe this transaction twice";
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = $transaction['amount'];
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
		
		if($isValid) {
			if($data['currency'] != strtolower($transaction['currency'])) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Currency doesn't match.";
			}
		}
		
		$sandbox = $this->params->get('sandbox');
		if($isValid) {
			if($sandbox == $transaction['livemode']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Transaction done in wrong mode.";
			}
		}
			
		// Log the IPN data
		$this->logIPN($transaction, $isValid);

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
		if($transaction['paid']) {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $transaction['id'],
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