<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentMoipAssinaturas extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'moipassinaturas',
			'ppKey'			=> 'PLG_AKPAYMENT_MOIPASSINATURAS_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/moip_logo.gif'
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
		
		$error_url = 'index.php?option='.JRequest::getCmd('option').
			'&view=level&slug='.$level->slug.
			'&layout='.JRequest::getCmd('layout','default');
		$error_url = JRoute::_($error_url,false);
				
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		// Check state
		if(empty($kuser->state)) {
			JFactory::getApplication()->redirect($error_url, JText::_('PLG_AKPAYMENT_MOIPASSINATURAS_NO_STATE'), 'error');
			return false;
		}	
		
		// Custom params
		$customParams = json_decode($kuser->params);
		
		// Split birthday in year-month-day
		$birthday = trim($customParams->birthday);
		$birthdayMatches = array();
		preg_match('/^([\d]+)-([\d]+)-([\d]+)$/', $birthday, $birthdayMatches);
		$birthdateYear = $birthdayMatches[1];
		$birthdateMonth = $birthdayMatches[2];
		$birthdateDay = $birthdayMatches[3];
		
		// Try to find street number
		$address1 = trim($kuser->address1);
		$address2 = trim($kuser->address2);
		$street = '';
		$number = '';
		$complement = '';
		$addressMatches = array();
		if(preg_match('/^(.+) ([\d]+)$/', $address1, $addressMatches)) {
			// Number last
			$street = $addressMatches[1];
			$number = $addressMatches[2];
			$complement = $address2;
		} else if(preg_match('/^([\d]+) (.+)$/', $address1, $addressMatches)) {
			// Number first
			$street = $addressMatches[2];
			$number = $addressMatches[1];
			$complement = $address2;
		} else if(preg_match('/^(.+) ([\d]+)$/', $address2, $addressMatches)) {
			// Number last
			$street = $address1;
			$number = $birthdayMatches[2];
			$complement = $birthdayMatches[1];
		} else if(preg_match('/^([\d]+) (.+)$/', $address2, $addressMatches)) {
			// Number first
			$street = $address1;
			$number = $birthdayMatches[1];
			$complement = $birthdayMatches[2];
		} else {
			JFactory::getApplication()->redirect($error_url, JText::_('PLG_AKPAYMENT_MOIPASSINATURAS_NO_STREET_NR'), 'error');
			return false;
		}
		
		// Split phone in area-code and phone-number
		$phone = trim($customParams->phone);
		$phoneAreaCode = '';
		$phoneNumber = '';
		$phoneMatches = array();
		if(preg_match('/^([\d]+)[\D]+([\d]+)$/', $phone, $phoneMatches)) {
			$phoneAreaCode = $phoneMatches[1];
			$phoneNumber = $phoneMatches[2];
		} else {
			$phoneOnlyWithNumbers = preg_replace('/\D/', '', $phone);
			if(!strlen($phoneOnlyWithNumbers) > 4) {
				JFactory::getApplication()->redirect($error_url, JText::_('PLG_AKPAYMENT_MOIPASSINATURAS_INVALID_PHONE'), 'error');
				return false;
			}
			$phoneAreaCode = substr($phoneOnlyWithNumbers, 0, 2);
			$phoneNumber = substr($phoneOnlyWithNumbers, 2);
		}
		
		// MoIP customer details
		$customer = array(
			'fullname' => trim($user->name),
			'email' => trim($user->email),
			'code' => 'ak_' . trim($user->id),
			'cpf' => trim($customParams->cpf),
			'birthdate_day' => $birthdateDay,
			'birthdate_month' => $birthdateMonth,
			'birthdate_year' => $birthdateYear,
			'phone_area_code' => $phoneAreaCode,
			'phone_number' => $phoneNumber,
			'address' => array(
				'street' => $street,
				'number' => $number,
				'complement' => $complement,
				'district' => trim($customParams->district),
				'zipcode' => trim($kuser->zip),
				'city' => trim($kuser->city),
				'state' => trim($kuser->state),
				'country' => $this->translateCountry($kuser->country)
			)
		);
		
		// Check if customer exists
		$jsonExistingCustomer = $this->httpRequest(
				$this->getMoipServerHost(),
				'customers/' . $customer['code'],
				'GET');
		$existingCustomer = json_decode($jsonExistingCustomer);
	
		if(!empty($existingCustomer)) {
			// Customer exists. So, check if details match
			if($existingCustomer->fullname != $customer['fullname']
					|| $existingCustomer->email != $customer['email']
					|| $existingCustomer->cpf != $customer['cpf']
					|| $existingCustomer->birthdate_day != $customer['birthdate_day']
					|| $existingCustomer->birthdate_month != $customer['birthdate_month']
					|| $existingCustomer->birthdate_year != $customer['birthdate_year']
					|| $existingCustomer->phone_area_code != $customer['phone_area_code']
					|| $existingCustomer->phone_number != $customer['phone_number']
					|| $existingCustomer->address->street != $customer['address']['street']
					|| $existingCustomer->address->number != $customer['address']['number']
					|| $existingCustomer->address->complement != $customer['address']['complement']
					|| $existingCustomer->address->district != $customer['address']['district']
					|| $existingCustomer->address->zipcode != $customer['address']['zipcode']
					|| $existingCustomer->address->city != $customer['address']['city']
					|| $existingCustomer->address->state != $customer['address']['state']
					|| $existingCustomer->address->country != $customer['address']['country']) {
				// If not all the details match, edit the customer
				if(!$this->httpRequest(
						$this->getMoipServerHost(),
						'customers/' . $customer['code'],
						'PUT',
						json_encode($customer))) {
							JFactory::getApplication()->redirect($error_url, 'The MoIP customer could not be updated.', 'error');
							return false;
						}
			}
		} else {
			// Customer does not exist yet, so create it
			$createCustomerResponse = '';
			if(!$this->httpRequest(
					$this->getMoipServerHost(),
					'customers?new_vault=false',
					'POST',
					json_encode($customer))) {
						$logData = array();
						$logData['errorResponse'] = $createCustomerResponse;
						$this->logIPN($logData, false);
						JFactory::getApplication()->redirect($error_url, 'The MoIP customer could not be created. ' . $createCustomerResponse, 'error');
						return false;
			}
		}
		
		// Set the customers credit-card
		$doc = JFactory::getDocument();
		$doc->addScript($this->getMoipJSUrl());
		$doc->addScriptDeclaration("
			$(document).ready(function(){
				$('#payment-form').submit(function(){
					var sid = $('#sid').val();
					if(!!sid) {
						return true;
					} else {	
						$('#payment-button').attr('disabled', 'disabled');
						var token = '" . trim($this->params->get('token','')) . "';
						var moip = new MoipAssinaturas(token); 
						var customer = new Customer();
						customer.code = '" . $customer['code'] . "';
						customer.billing_info = build_billing_info();

						moip.update_credit_card(customer).callback(function(data){
							if(data.has_errors()){
								$('#payment-errors ul').empty();
								for (i = 0; i < data.errors.length; i++) {
									var erro = data.errors[i].description;
									$('#payment-errors ul').append('<li>' + erro + '</li>');
								}
								$('#payment-errors').show();
								$('#payment-button').removeAttr('disabled');
							}else{
								$('#sid').val('" . $subscription->akeebasubs_subscription_id . "');
								$('#payment-form').submit();
							}
						});
						return false;
					}
				});
			});

			var build_billing_info = function() {
				var billing_info_params = {
					fullname : $('#card-holder').val(), 
					expiration_month: $('#card-expiry-month').val(),
					expiration_year: $('#card-expiry-year').val(),
					credit_card_number: $('#card-number').val()
				};
				return new BillingInfo(billing_info_params);
			};
		");
		
		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=moipassinaturas&mode=subscribe';

		@ob_start();
		include dirname(__FILE__).'/moipassinaturas/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		$mode = $data['mode'];
		if(strtolower($mode) == 'webhook') {
			return $this->processWebhook();
		} else {
			return $this->processSubscription($data);
		}
	}
	
	private function processSubscription($data)
	{
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
		
		if(!$isValid) {
			$data['akeebasubs_failure_reason'] = 'The subscription ID is invalid';
			return false;
		}
		
		$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->setId($subscription->akeebasubs_level_id)
			->getItem();
		$error_url = 'index.php?option='.JRequest::getCmd('option').
			'&view=level&slug='.$level->slug.
			'&layout='.JRequest::getCmd('layout','default');
		$error_url = JRoute::_($error_url,false);
		
		// MoIP plan details
		$plan = array(
			'code' => 'ak_' . substr($level->slug, 0, 62),
			'name' => substr($level->title, 0, 65),
			'description' => substr($level->description, 0, 255),
			'amount' => (int)($subscription->gross_amount * 100),
			'interval' => array(
				'length' => $level->duration,
				'unit' => 'DAY',
			),
			'status' => 'ACTIVE'
		);

		// Check if plan exists
		$jsonExistingPlan = $this->httpRequest(
				$this->getMoipServerHost(),
				'plans/' . $plan['code'],
				'GET');
		$existingPlan = json_decode($jsonExistingPlan);

		if(!empty($existingPlan)) {
			// Plan exists. So, check if details match
			if($existingPlan->name != $plan['name']
					|| ($existingPlan->description != 'false' && $existingPlan->description != $plan['description'])
					|| $existingPlan->amount != $plan['amount']
					|| $existingPlan->interval->length != $plan['interval']['length']
					|| $existingPlan->interval->unit != $plan['interval']['unit']
					|| $existingPlan->billing_cycles != $plan['billing_cycles']
					|| $existingPlan->status != $plan['status']) {
				// If not all the details match, edit the plan
				if(!$this->httpRequest(
						$this->getMoipServerHost(),
						'plans/' . $plan['code'],
						'PUT',
						json_encode($plan))) {
							JFactory::getApplication()->redirect($error_url, 'The MoIP plan could not be updated.', 'error');
							return false;
						}
			}
		} else {
			// Plan does not exist yet, so create it
			if(!$this->httpRequest(
					$this->getMoipServerHost(),
					'plans',
					'POST',
					json_encode($plan))) {
						JFactory::getApplication()->redirect($error_url, 'The MoIP plan could not be created.', 'error');
						return false;
			}
		}
				
		// MoIP subscription details
		$moipSubscription = array(
			'code' => 'ak_' . $id,
			'plan' => array(
				'code' => $plan['code']
			),
			'customer' => array(
				'code' => 'ak_' . $subscription->user_id
			)
		);
		
		// Create the subscription
		if(!$this->httpRequest(
				$this->getMoipServerHost(),
				'subscriptions',
				'POST',
				json_encode($moipSubscription))) {
					JFactory::getApplication()->redirect($error_url, 'The MoIP subscription could not be created.', 'error');
					return false;
		}
		
		// Redirect the user to the "thank you" page
		$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$subscription->slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
		JFactory::getApplication()->redirect($thankyouUrl);
		return true;
	}
	
	private function processWebhook()
	{
		$isValid = true;
		
		// Get the response data
		$jsonResponse = file_get_contents("php://input");
		$data = json_decode($jsonResponse, true);
		
		// Check notification token
		if($_SERVER['HTTP_AUTHORIZATION'] != trim($this->params->get('notification_token',''))) {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = 'Http authorization does not match. Please make sure you entered the correct notification token.';
		}
		
		// Check event
		if($isValid) {
			$event = $data['event'];
			// We are only interessted in events about invoices
			if(substr($event, 0, 8) != 'invoice.') {
				return true;
			}
		}
		
		// Get the subscription
		if($isValid) {
			$invoiceId = $data['resource']['id'];
			$subscription = null;
			if(isset($data['resource']['subscription_code'])) {
				// Get subscription by ID
				$subscriptionCode = $data['resource']['subscription_code'];
				if(substr($subscriptionCode, 0, 3) != 'ak_') {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = 'We don\'t handle this subscription because it\'s not created by this plugin';
				} else {
					$subscriptionId = substr($subscriptionCode, 3);
					$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
						->setId($subscriptionId)
						->getItem();
					if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $subscriptionId) ) {
						$subscription = null;
						$isValid = false;
						$data['akeebasubs_failure_reason'] = 'Cannot find the subscription with the ID ' . $subscriptionId;
					}
				}
			} else {
				// Get subscription by key
				$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->processor('moipassinaturas')
					->paykey($invoiceId)
					->getFirstItem(true);
				if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->processor_key != $invoiceId) ) {
					$subscription = null;
					$isValid = false;
					$data['akeebasubs_failure_reason'] = 'Cannot find the subscription with the key ' . $invoiceId;
				}
			}
		}
		
		// Check if this payment is already completed
		if($isValid && ($subscription->processor_key == $invoiceId) && ($subscription->state == 'C')) {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = "I will not processe this invoice twice";
		}
		
		// Get the invoice details
		if($isValid) {
			$jsonInvoiceDetails = $this->httpRequest(
					$this->getMoipServerHost(),
					'invoices/' . $invoiceId,
					'GET');
			$invoiceDetails = json_decode($jsonInvoiceDetails);
			if(empty($invoiceDetails)) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Cannot get the details for the invoice " . $invoiceId;
			}
		}
		
		// Check customer
		if($isValid && ($invoiceDetails->customer->code != 'ak_' . $subscription->user_id)) {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = "Customer code does not match";
		}
		
		// Check plan
		if($isValid) {			
			$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem();
			if($isValid && ($invoiceDetails->plan->code !=  'ak_' . substr($level->slug, 0, 62))) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Plan code does not match";	
			}
		}
		
		// Check that amount is correct
		if($isValid && !is_null($subscription)) {
			$mc_gross = $data['amount'];
			$gross = (int)($subscription->gross_amount * 100);
			if($mc_gross > 0) {
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}
		
		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;
		
		// Check the payment status
		switch($data['status']['code']) {
			case 1: // Open
				$newStatus = 'P';
				break;
			case 2: // Waiting confirmation
				$newStatus = 'P';
				break;
			case 3: // Paid
				$newStatus = 'C';
				break;
			default:
				$newStatus = 'X';
				break;
		}
		

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $subscription->akeebasubs_subscription_id,
				'processor_key'					=> $invoiceId,
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
		
		// If subscription is not recurring suspend it
		if(! $level->recurring) {
			$this->httpRequest(
				$this->getMoipServerHost(),
				'subscriptions/ak_' . $subscription->akeebasubs_subscription_id . '/suspend',
				'PUT');
		}
		
		return true;
	}

	private function httpRequest($host, $path, $method = 'GET', $params = '')
	{
		// Create the connection
		$sock = fsockopen('ssl://' . $host, 443);
		fputs($sock, "$method /assinaturas/v1/$path HTTP/1.1\r\n");
		fputs($sock, "Host: $host\r\n");
		fputs($sock, "Content-type: application/json\r\n");
		if (in_array($method, array('POST', 'PUT'))) {
			fputs($sock, "Content-length: ".strlen($params)."\r\n");
		}
		fputs($sock, "Authorization: Basic " . $this->getBasicAuthorization() . "\r\n");
		fputs($sock, "Connection: close\r\n\r\n");
		if (in_array($method, array('POST', 'PUT'))) {
			fputs($sock, $params);
		}

		// Buffer the result
		$response = "";
		while (!feof($sock)) {
			$response .= fgets($sock, 1024);
		}
		fclose($sock);
		
		if (in_array($method, array('POST', 'PUT'))) {
			// Check if our post was successful
			$pattern = '/HTTP[^ ]+ 20[01]/';
			if(preg_match($pattern, $response)) {
				return true;
			}
			return false;
		} else {
			// Get the json part of the response
			$matches = array();
			$pattern = '/[^{]*(.+)[^}]*/';
			preg_match($pattern, $response, $matches);
			$json = $matches[1];
			return $json;
		}
	}
	

	private function getBasicAuthorization()
	{
		$authString = trim($this->params->get('token','')) . ':'
				. trim($this->params->get('key',''));
		$authB64String =  base64_encode($authString);
		return $authB64String;
	}
	
	private function getMoipServerHost()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'sandbox.moip.com.br';
		} else {
			return 'api.moip.com.br';
		}
	}
	
	private function getMoipJSUrl()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://sandbox.moip.com.br/moip-assinaturas.min.js';
		} else {
			return 'https://api.moip.com.br/moip-assinaturas.min.js';
		}
	}
	
	public function selectMonth()
	{
		$options = array();
		$options[] = JHTML::_('select.option','00','--');
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
		$options[] = JHTML::_('select.option','00','--');
		for($i = 0; $i <= 10; $i++) {
			$y = sprintf('%04u', $i+$year);
			$options[] = JHTML::_('select.option',substr($y, -2),$y);
		}
		
		return JHTML::_('select.genericlist', $options, 'card-expiry-year', 'class="input-small"', 'value', 'text', '', 'card-expiry-year');
	}
}