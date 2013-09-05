<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentWePay extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'wepay',
			'ppKey'			=> 'PLG_AKPAYMENT_WEPAY_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/wepay.png'
		));

		parent::__construct($subject, $config);

		require_once dirname(__FILE__).'/wepay/lib/wepay.php';
		
		$sandbox = $this->params->get('sandbox', 0);
		$merchantId = trim($this->params->get('merchant_id', ''));
		$secret = trim($this->params->get('secret', ''));
		if($sandbox) {
			WePay::useStaging($merchantId, $secret);
		} else {
			WePay::useProduction($merchantId, $secret);
		}
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
		
		$accountId = trim($this->params->get('account_id', ''));
		$accessToken = trim($this->params->get('access_token', ''));
		$wepay = new WePay($accessToken);

		if($this->isRecurring($level)) {
			// Set end time to 100 billing cycles
			$hundretCylesInDays = $level->duration * 100;
			$endTime = new JDate("+$hundretCylesInDays days");
			try {
				$wpResponse = $wepay->request('preapproval/create', array(
					'account_id'		=> $accountId,
					'period'			=> $this->getRecurringPeriod($level),
					'end_time'			=> $endTime->format('Y-m-d'),
					'amount'			=> sprintf('%.2f', $subscription->gross_amount),
					'mode'				=> 'regular',
					'short_description'	=> $level->title,
					'redirect_uri'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=wepay&sid=' . $subscription->akeebasubs_subscription_id,
					'callback_uri'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=wepay&sid=' . $subscription->akeebasubs_subscription_id . '&cbtype=ipn',
					'auto_recur'		=> 'true'
				));
			}catch(Exception $e) {
				$error_url = 'index.php?option='.JRequest::getCmd('option').
					'&view=level&slug='.$level->slug.
					'&layout='.JRequest::getCmd('layout','default');
				$error_url = JRoute::_($error_url,false);
				JFactory::getApplication()->redirect($error_url, $e->getMessage(), 'error');
			}
		
			// Get the response
			$checkoutId = $wpResponse->preapproval_id;
			$checkoutUri = $wpResponse->preapproval_uri;
			$subscription->save(array(
				'processor_key'	=> $checkoutId
			));		
		} else {
			// Create the checkout
			try {
				$wpResponse = $wepay->request('checkout/create', array(
					'account_id'		=> $accountId,
					'amount'			=> sprintf('%.2f', $subscription->gross_amount),
					'short_description'	=> $level->title,
					'type'				=> 'SERVICE',
					'redirect_uri'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=wepay&sid=' . $subscription->akeebasubs_subscription_id
				));	
			}catch(Exception $e) {
				$error_url = 'index.php?option='.JRequest::getCmd('option').
					'&view=level&slug='.$level->slug.
					'&layout='.JRequest::getCmd('layout','default');
				$error_url = JRoute::_($error_url,false);
				JFactory::getApplication()->redirect($error_url, $e->getMessage(), 'error');
			}
		
			// Get the response
			$checkoutId = $wpResponse->checkout_id;
			$checkoutUri = $wpResponse->checkout_uri;
			$subscription->save(array(
				'processor_key'	=> $checkoutId
			));
		}
		
		// Error handling
		if(empty($checkoutId) || empty($checkoutUri)) {
			$error_url = 'index.php?option='.JRequest::getCmd('option').
				'&view=level&slug='.$level->slug.
				'&layout='.JRequest::getCmd('layout','default');
			$error_url = JRoute::_($error_url,false);
			JFactory::getApplication()->redirect($error_url, $wpResponse, 'error');
		}

		@ob_start();
		include dirname(__FILE__).'/wepay/form.php';
		$html = @ob_get_clean();

		return $html;
	}


	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		$isIPN = ($data['cbtype'] == 'ipn');
		$isValid = true;

		// Load the relevant subscription row
		if($isValid) {
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
				$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
						->setId($subscription->akeebasubs_level_id)
						->getItem();
			} else {
				$isValid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'There is no valid subscription ID';
		}
		
		$isFirstRecurringCallback = $this->isRecurring($level) && !$isIPN;
		
		// Check callback type
		if($isValid) {
			// New subscriptions (state: N) are always handled by the redirect-uri. We can ignore IPNs
			// in this case, which is only used for recurring payments after a new subscription is initially accepted.
			if($subscription->state == 'N' && $isIPN) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Ignore IPN callbacks for new subscriptions.";
			}
		}
		
		// Check checkout ID
		if($isFirstRecurringCallback) {
			if($isValid) {
				if($subscription->state == 'N' && $data['preapproval_id'] != $subscription->processor_key) {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "Pre-approval ID is not correct.";
				}
			}
		} else {
			if($isValid) {
				if($subscription->state == 'N' && $data['checkout_id'] != $subscription->processor_key) {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "Checkout ID is not correct.";
				}
			}	
		}
		
		// Get more details by asking WePay
		if($isValid) {
			try {
				$accessToken = trim($this->params->get('access_token', ''));
				$wepay = new WePay($accessToken);
				if($isFirstRecurringCallback) {
					$wpResponse = $wepay->request('preapproval', array(
						'preapproval_id'	=> $data['preapproval_id']
					));
					$data['period'] = $wpResponse->period;
				} else {
					$wpResponse = $wepay->request('checkout', array(
						'checkout_id'	=> $data['checkout_id']
					));
				}
				$data['key'] = $wpResponse->create_time;
				$data['account_id'] = $wpResponse->account_id;
				$data['state'] = $wpResponse->state;
				$data['amount'] = $wpResponse->amount;
			} catch(Exception $e) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = $e->getMessage();
			}
		}
		
		// Check recurring period
		if($isFirstRecurringCallback) {
			if($data['period'] != $this->getRecurringPeriod($level)) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "The period of the recurring subscription is not correct.";
			}
		}
		
		// Check account ID
		if($isValid) {
			if($data['account_id'] != trim($this->params->get('account_id', ''))) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Account ID is not correct.";
			}
		}
		
		// Check that transaction has not been previously processed
		if($isValid) {
			if($subscription->state == 'C' && $data['key'] == $subscription->processor_key) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not processe this transaction twice";
			}
		}
		
		// Check amount
		if($isValid) {
			if($isValid && !is_null($subscription)) {
				$mc_gross = floatval($data['amount']);
				$gross = $subscription->gross_amount;
				if($mc_gross > 0) {
					// A positive value means "payment". The prices MUST match!
					// Important: NEVER, EVER compare two floating point values for equality.
					$isValid = ($gross - $mc_gross) < 0.01;
				}
				if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);
		
		// Fraud attempt? Do nothing more!
		if(!$isValid) {
			if(!$isIPN) {
				$error_url = 'index.php?option='.JRequest::getCmd('option').
					'&view=level&slug=' . $level->slug .
					'&layout='.JRequest::getCmd('layout','default');
				$error_url = JRoute::_($error_url,false);
				JFactory::getApplication()->redirect($error_url, $data['akeebasubs_failure_reason'], 'error');	
			}
			return false;
		}
		
		// Is payment successful?
		if($isFirstRecurringCallback) {
			if(in_array($data['state'], array('approved', 'completed'))) {
				$newStatus = 'C';
			} else {
				$newStatus = 'X';
			}			
		} else {
			if(in_array($data['state'], array('authorized', 'reserved', 'captured', 'settled'))) {
				$newStatus = 'C';
			} else {
				$newStatus = 'X';
			}	
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['key'],
				'state'							=> $newStatus,
				'enabled'						=> 0
		);
		JLoader::import('joomla.utilities.date');
		if($newStatus == 'C') {
			$this->fixDates($subscription, $updates);
		}
		// In the case of a successful recurring payment, fetch the old subscription's data
		if($this->isRecurring($level) && ($newStatus == 'C') && ($subscription->state == 'C')) {
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
		
		if(!$isIPN) {
			// Redirect the user to the "thank you" page
			$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&slug=' . $level->slug . '&layout=order&subid=' . $subscription->akeebasubs_subscription_id, false);
			JFactory::getApplication()->redirect($thankyouUrl);
		}
		return true;
	}
	
	private function getRecurringPeriod($level) {
		// Supported durations by WePay:
		// every day (daily), every week (weekly), every month, every year (yearly)
		$period = 'yearly';
		if($level->duration < 7) {
			$period = 'daily';
		} else if($level->duration < 28) {
			$period = 'weekly';
		} else if($level->duration < 359) {
			$period = 'monthly';
		}
		return $period;
	}
	
	private function isRecurring($level) {
		$isFixedExpirationSet = !empty($level->fixed_date) && (substr($level->fixed_date, 0, 10) != '0000-00-00');
		return $level->recurring && !$level->forever && !$isFixedExpirationSet;
	}
}