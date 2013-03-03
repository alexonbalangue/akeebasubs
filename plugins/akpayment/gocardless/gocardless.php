<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentGocardless extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'gocardless',
			'ppKey'			=> 'PLG_AKPAYMENT_GOCARDLESS_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/gocardless_logo.png',
		));
		
		parent::__construct($subject, $config);
		
		// Load the GoCardless library
		require_once dirname(__FILE__).'/gocardless/lib/GoCardless.php';
		
		// Get the config settings
		$appId = trim($this->params->get('app_id',''));
		$appSecret = trim($this->params->get('app_secret',''));
		$merchantToken = trim($this->params->get('merchant_token',''));
		$merchantId = trim($this->params->get('merchant_id',''));
		$sandbox = $this->params->get('sandbox',0);
		
		// Use sandbox if setting enables it
		if($sandbox) {
			GoCardless::$environment = 'sandbox';
		}
		
		// Use the GoCardless settings to set the account details
		$account_details = array(
			'app_id'        => $appId,
			'app_secret'    => $appSecret,
			'merchant_id'   => $merchantId,
			'access_token'  => $merchantToken
		);
		GoCardless::set_account_details($account_details);
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
		
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		$nameParts = explode(' ', trim($user->name), 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		
		$payment_details = array(
			'redirect_uri'	=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=gocardless',
			'cancel_uri'	=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'state'			=> $subscription->akeebasubs_subscription_id,
			'amount'		=> sprintf('%.2f', $subscription->gross_amount),
			'name'			=> $level->title,
			'user'    => array(
				'first_name'	=> $firstName,
				'last_name'		=> $lastName,
				'email'			=> trim($user->email)
			)
		);
		
		$url = GoCardless::new_bill_url($payment_details);

		@ob_start();
		include dirname(__FILE__).'/gocardless/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		if(isset($data['state'])) {
			return $this->processGCRedirect($data);
		} else {
			return $this->processGCWebhook($data);
		}
	}
	
	/**
	 * Processes the GoCardless redirect. This callback is received right after the customer
	 * finished the payment process via the GoCardless payment form. In this function the payment
	 * will be confirmed and the payment has usually the status 'pending' at this stage.
	 * 
	 * The next step, when the payment is 'paid', GoCardless will send a 'webhook' that
	 * will be handled by the next function 'processGCWebhook'.
	 */
	private function processGCRedirect($data)
	{
		$isValid = true;
		
		// Load the relevant subscription row
		$id = $data['state'];
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
			// Check IPN data for validity (i.e. protect against fraud attempt)
			$isValid = $this->isValidIPN($data['resource_id'], $subscription);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';
		}
		
		if($isValid) {
			try {
				$confirm_params = array(
					'resource_uri'	=> $data['resource_uri'],
					'resource_id'	=> $data['resource_id'],
					'resource_type'	=> $data['resource_type'],
					'signature'		=> $data['signature'],
					'state'			=> $id
				);

				// Returns the confirmed resource if successful, otherwise throws an exception
				$confirm = GoCardless::confirm_resource($confirm_params);
				if($confirm == null) {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "Invalid response";
				}
			}catch(Exception $e) {
				$isValid = false;
				$errorMessage = $e['error'][0];
				$data['akeebasubs_failure_reason'] = $errorMessage;
			}
		}
		
		// Check that transaction id has not been previously processed
		if($isValid && !is_null($subscription)) {
			$isValid = $this->isValidProccessId($confirm->id, $subscription);
			if(!$isValid) $data['akeebasubs_failure_reason'] = "I will not process the same order twice";
		}
        
		// Check transaction type
		if($isValid) {
			$type = strtoupper($data['resource_type']);
			if($type != 'BILL') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Wrong type " . $type;
			}
		}

		// Check that amount is correct
		if($isValid) {
			$isValid = $this->isValidAmount($confirm->amount, $subscription);
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
		$newStatus = $this->getPaymentStatus($confirm->status);

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $confirm->id,
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
	
	/**
	 * Processes the GoCardless webhook. This callback is received when CoCardless sends an update
	 * of the payment by a webhook. Usually the payment is 'pending' in the first place and we are
	 * waiting with this function to receive an updated that the payment is 'paid'.
	 * 
	 * See https://gocardless.com/docs/php/merchant_tutorial_webhook.
	 */
	private function processGCWebhook()
	{
		$data = array();
		$webhook = file_get_contents('php://input');
		$decodedWebhook = json_decode($webhook, true);
		$data['webhook'] = $decodedWebhook;
		$data = $decodedWebhook['payload'];
		
		// Validate webhook
		$isValid = GoCardless::validate_webhook($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = "Invalid signature";
		        
		// Check transaction type
		if($isValid) {
			$type = strtoupper($data['resource_type']);
			$data['type'] = $type;
			if($type != 'BILL') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Wrong type " . $type;
			}
		}
		
		// Load the relevant subscription row
		$resourceId = $data['bills'][0]['id'];
		$data['resourceId'] = $resourceId;
		$subscription = null;
		if($isValid) {
			$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->processor('gocardless')
				->paystate('P')
				->paykey($resourceId)
				->getFirstItem(true);
			if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->processor_key != $resourceId) ) {
				$subscription = null;
				$isValid = false;
			}
		}
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'The subscription ID is invalid';
		
		// Get the bill
		if($isValid) {
			try {
				$bill = GoCardless_Bill::find($resourceId);
				if($bill == null) {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "Invalid response";
				}
			}catch(Exception $e) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = $e->getMessage();
			}
		}
		
		// Check that transaction id has not been previously processed
		if($isValid && !is_null($subscription)) {
			$data['billid'] = $bill->id;
			$isValid = $this->isValidProccessId($bill->id, $subscription);
			if(!$isValid) $data['akeebasubs_failure_reason'] = "I will not process the same order twice";
		}

		// Check that amount is correct
		if($isValid) {
			$data['billamount'] = $bill->amount;
			$isValid = $this->isValidAmount($bill->amount, $subscription);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}
		
		// Log the IPN data
		$data['billstatus'] = $bill->status;
		$this->logIPN($data, $isValid);
		
		if (!$isValid) {
			header('HTTP/1.1 403 ' . $data['akeebasubs_failure_reason']);
			return false;
		}
		
		// Payment status
		$newStatus = $this->getPaymentStatus($bill->status);

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $subscription->akeebasubs_subscription_id,
				'processor_key'					=> $bill->id,
				'state'							=> $newStatus,
				'enabled'						=> 0
		);
		JLoader::import('joomla.utilities.date');
		if($newStatus == 'C') {
			$this->fixDates($subscription, $updates);
		}
		$subscription->save($updates);
		
		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $confirm->source_id,
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
		
		header('HTTP/1.1 200 OK');
		return true;
	}
	
	private function getPaymentStatus($status)
	{
		$status = strtoupper($status);
		if($status == 'PENDING') {
			return 'P';
		}
		if($status == 'CREATED') {
			return 'P';
		}
		if($status == 'PAID') {
			return 'C';
		}
		return 'X';
	}
	
	private function isValidProccessId($id, $subscription)
	{
		if($id == $subscription->processor_key &&
				($subscription->state == 'C' || $subscription->state == 'X')) {
			return false;
		}
		return true;
	}
	
	private function isValidAmount($mc_gross, $subscription)
	{
		$gross = $subscription->gross_amount;
		if($mc_gross > 0) {
			// A positive value means "payment". The prices MUST match!
			// Important: NEVER, EVER compare two floating point values for equality.
			return ($gross - $mc_gross) < 0.01;
		}
		return false;
	}
    
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($resourceId)
	{
		$bill = GoCardless_Bill::find($resourceId);
		if($resourceId != $bill->id) {
			return false;
		}
		$merchantId = trim($this->params->get('merchant_id',''));
		if(strtoupper($merchantId) != strtoupper($bill->merchant_id)) {
			return false;
		}
		return true;
	}
}
