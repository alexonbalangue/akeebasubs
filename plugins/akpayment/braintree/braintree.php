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

		// Create payment
		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=braintree&sid='.$subscription->akeebasubs_subscription_id;
		if($level->recurring) {
			// Recurring payment
			$data = Braintree_TransparentRedirect::createCustomerData(
				array(
					'redirectUrl' => $callbackUrl
				)
			);
		} else {
			// One-time payment
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
		}

		@ob_start();
		include dirname(__FILE__).'/braintree/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this TEST
		if($paymentmethod != $this->ppName) return false;
		
		if($data['type'] == 'webhook') {
			// Clean up second question mark in response
			$matches = array();
			preg_match('/000\?([^=]+)=([^&]+)/', $data['marker'], $matches);
			if(sizeof($matches) > 2) {
				$data[$matches[1]] = $matches[2];	
			}
			return $this->processWebhook($data);
		} else {
			return $this->processCallback($data);
		}
	}
	
	/**
	 * This function will be called after the customer sends the form and
	 * the callback from braintree is received.
	 */
	private function processCallback($data)
	{
		// Load the relevant subscription row
		$id = $data['sid'];
		$subscription = null;
		$isValid = $this->isSubscriptionValid($id, $subscription);
		if(!$isValid) {
			$data['akeebasubs_failure_reason'] = 'The subscription ID is invalid';
		} else {
			$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem();
		}
		
		// Confirm our request to braintree
		if($isValid) {
			try {
				$queryString = JURI::getInstance()->getQuery();
				$confirmationResult = Braintree_TransparentRedirect::confirm($queryString);
				if(! $confirmationResult->success) {
					$isValid = false;
					if ($confirmationResult->transaction->processorResponseCode >= 2000
							&& $confirmationResult->transaction->processorResponseCode <= 2062) {
						$errorMessage = 'Card declined. Please check your details. (code ' . $confirmationResult->transaction->processorResponseCode . ')';
					} else {
						$errorMessage = $confirmationResult->message;
					}
					$data['akeebasubs_failure_reason'] = $errorMessage;
}
			} catch(Exception $e) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = $e->getMessage();
			}
		}
		
		// Create the braintree-subscription for recurring payments
		if($level->recurring) {
			if($isValid) {
				// Get braintree CC-token
				$ccToken = '';
				try {
					$ccToken = $confirmationResult->customer->creditCards[0]->token;
					if(empty($ccToken)) {
						$isValid = false;
						$data['akeebasubs_failure_reason'] = 'Can\'t get the CC token';
					}
				} catch(Exception $e) {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = 'Can\'t get the CC token';
				}
			}

			// Create the braintree-subscription
			if($isValid) {
				try {
					$subscriptionResult = Braintree_Subscription::create(array(
						'paymentMethodToken'	=> $ccToken,
						'planId'				=> trim($level->slug),
						'id'					=> $id,
						'price'					=> sprintf('%.2f', $subscription->gross_amount),
						'options'				=> array('startImmediately' => true)
					));
					if(! $subscriptionResult->success) {
						$isValid = false;
						if ($subscriptionResult->transaction->processorResponseCode >= 2000
								&& $subscriptionResult->transaction->processorResponseCode <= 2062) {
							$errorMessage = 'Card declined.  Please check your details. (code ' . $subscriptionResult->transaction->processorResponseCode . ')';
						} else {
							$errorMessage = $subscriptionResult->message;
						}
						$data['akeebasubs_failure_reason'] = $errorMessage;
					}
				} catch(Exception $e) {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = $e->getMessage();
				}
			}
		}
		
		// Get transaction
		if($isValid) {
			if($level->recurring) {
				$transaction = $subscriptionResult->subscription->transactions[0];
			} else {
				$transaction = $confirmationResult->transaction;	
			}
			if(! isset($transaction)) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Can\'t get the transaction";
			}
		}
		
		// Check that transaction id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $transaction->id && $subscription->state == 'C') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same order twice";
			}
		}
        
		// Check transaction type
		if($isValid) {
			if($transaction->type != 'sale') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Wrong type " . $transaction->type;
			}
		}

		// Check that amount is correct
		if($isValid) {
			$isValid = $this->isValidAmount($transaction->amount, $subscription);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}
			
		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) {
			$error_url = 'index.php?option='.JRequest::getCmd('option').
				'&view=level&slug='.$level->slug.
				'&layout='.JRequest::getCmd('layout','default');
			$error_url = JRoute::_($error_url,false);
			JFactory::getApplication()->redirect($error_url,$data['akeebasubs_failure_reason'],'error');
			return false;
		}
		
		// Payment status
		if($transaction->status == 'submitted_for_settlement') {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $transaction->id,
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
	 * This function will be called when a webhook from braintree is received
	 * which gives update about recurring payments.
	 */
	private function processWebhook($data)
	{
		$isValid = true;
		
		// This verification is done when the merchant defines
		// a new webhook that uses this plugin:
		// http://example.com/index.php?option=com_akeebasubs&view=callback&paymentmethod=braintree&type=webhook&marker=000
		if(isset($data['bt_challenge'])) {
			$app = JFactory::getApplication();
			@ob_end_clean();
			echo Braintree_WebhookNotification::verify($data['bt_challenge']);	
			$app->close();	
			return true;
		}
		
		// Parse the notification
		try {
			$webhookNotification = Braintree_WebhookNotification::parse(
				$data['bt_signature'], $data['bt_payload']
			);	
		} catch(Exception $e) {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = $e->getMessage();
		}
		
		// Check kind of notification
		if($isValid) {
			$data['kind'] = $webhookNotification->kind;
			if($webhookNotification->kind != 'subscription_charged_successfully') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = 'This notification type is not supported: ' . $notificationType;
			}
		}
		
		// Load the subscription
		if($isValid) {
			$subscription = null;
			$data['subcriptionId'] = $webhookNotification->subscription->id;
			$subcriptionId = $webhookNotification->subscription->id;
			$isValid = $this->isSubscriptionValid($subcriptionId, $subscription);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The subscription ID is invalid';
		}
		
		// Check that transaction id has not been previously processed
		if($isValid) {
			$transaction = end($webhookNotification->subscription->transactions);
			$data['transactionId'] = $transaction->id;
			if($subscription->processor_key == $transaction->id && $subscription->state == 'C') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same order twice";
			}
		}
		
		// Check that amount is correct
		if($isValid) {
			$data['transactionAmount'] = $transaction->amount;
			$isValid = $this->isValidAmount($transaction->amount, $subscription);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}
		
		// Log the IPN data
		$this->logIPN($data, $isValid);
		
		// Leave this function if the request is invalid
		if (!$isValid) {
			header('HTTP/1.1 403 ' . $data['akeebasubs_failure_reason']);
			return false;
		}
		
		// Update subscription status (this also automatically calls the plugins)
		$newStatus = 'C';
		$updates = array(
			'akeebasubs_subscription_id'	=> $subcriptionId,
			'processor_key'					=> $transaction->id,
			'state'							=> $newStatus,
			'enabled'						=> 0
		);
		JLoader::import('joomla.utilities.date');
		if($newStatus == 'C') {
			$this->fixDates($subscription, $updates);
		}
		// In the case of a successful recurring payment, fetch the old subscription's data
		if(($newStatus == 'C') && ($subscription->state == 'C')) {
			$jNow = new JDate();
			$jStart = new JDate($subscription->publish_up);
			$jEnd = new JDate($subscription->publish_down);
			$now = $jNow->toUnix();
			$start = $jStart->toUnix();
			$end = $jEnd->toUnix();
			// Create a new record for the old subscription
			$oldData = $subscription->getData();
			$oldData['akeebasubs_subscription_id'] = 0;
			$oldData['publish_down'] = $jNow->toSql();
			$oldData['enabled'] = 0;
			$oldData['contact_flag'] = 3;
			$oldData['notes'] = "Automatically renewed subscription on ".$jNow->toSql();			
			
			// Calculate new start/end time for the subscription
			$allSubs = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
				->paystate('C')
				->level($subscription->akeebasubs_level_id)
				->user_id($subscription->user_id);
			$max_expire = 0;
			if(count($allSubs)) foreach($allSubs as $aSub) {
				$jExpire = new JDate($aSub->publish_down);
				$expire = $jExpire->toUnix();
				if($expire > $max_expire) $max_expire = $expire;
			}
			
			$duration = $end - $start;
			$start = max($now, $max_expire);
			$end = $start + $duration;
			$jStart = new JDate($start);
			$jEnd = new JDate($end);
			
			$updates['publish_up'] = $jStart->toSql();
			$updates['publish_down'] = $jEnd->toSql();
			
			// Save the record for the old subscription
			$table = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
				->getTable();
			$table->reset();
			$table->bind($oldData);
			$table->store();
		}
		// Save the changes
		$subscription->save($updates);
		
		// Run the onAKAfterPaymentCallback events
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array(
			$subscription
		));
		
		header('HTTP/1.1 200');
		return true;
	}
	
	private function isSubscriptionValid($id, &$subscription = null)
	{
		if($id > 0) {
			$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->setId($id)
				->getItem();
			if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id) ) {
				$subscription = null;
				return false;
			}
		} else {
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
	
	private function selectExpirationDate($type)
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
		
		return JHTML::_('select.genericlist', $options, $type . '[credit_card][expiration_date]', 'class="input-medium"', 'value', 'text', '', 'braintree_credit_card_exp');
	}
}