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

		// Create the checkout
		try {
			$wpResponse = $wepay->request('checkout/create', array(
				'account_id'        => $accountId,
				'amount'            => sprintf('%.2f', $subscription->gross_amount),
				'short_description' => $level->title,
				'type'              => 'SERVICE',
				'redirect_uri'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=wepay&sid=' . $subscription->akeebasubs_subscription_id
			));	
		}catch(Exception $e) {
			$error_url = 'index.php?option='.JRequest::getCmd('option').
				'&view=level&slug='.$level->slug.
				'&layout='.JRequest::getCmd('layout','default');
			$error_url = JRoute::_($error_url,false);
			JFactory::getApplication()->redirect($error_url, $e->getMessage(), 'error');
		}
		
		// Check the response
		$checkoutId = $wpResponse->checkout_id;
		$checkoutUri = $wpResponse->checkout_uri;
		$subscription->save(array(
			'processor_key'	=> $checkoutId
		));

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
                $slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
                    ->setId($subscription->akeebasubs_level_id)
                    ->getItem()
                    ->slug;
			} else {
				$isValid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'There is no valid subscription ID';
		}
		
		// Check checkout ID
		if($isValid) {
			if($subscription->state == 'N' && $data['checkout_id'] != $subscription->processor_key) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Checkout ID is not correct.";
			}
		}
		
		// Get more details by asking WePay
		if($isValid) {
			try {
				$accessToken = trim($this->params->get('access_token', ''));
				$wepay = new WePay($accessToken);
				$wpResponse = $wepay->request('checkout', array(
					'checkout_id'	=> $data['checkout_id']
				));
				$data['account_id'] = $wpResponse->account_id;
				$data['state'] = $wpResponse->state;
				$data['amount'] = $wpResponse->amount;
				$data['create_time'] = $wpResponse->create_time;
			} catch(Exception $e) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = $e->getMessage();
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
			if($subscription->state == 'C' && $data['create_time'] == $subscription->processor_key) {
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
			$error_url = 'index.php?option='.JRequest::getCmd('option').
				'&view=level&slug='.$slug.
				'&layout='.JRequest::getCmd('layout','default');
			$error_url = JRoute::_($error_url,false);
			JFactory::getApplication()->redirect($error_url, $data['akeebasubs_failure_reason'], 'error');
			return false;
		}
		
		// Is payment successful?
		if(in_array($data['state'], array('authorized', 'reserved', 'captured', 'settled'))) {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['create_time'],
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
		$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&slug=' . $slug . '&layout=order&subid=' . $subscription->akeebasubs_subscription_id, false);
		JFactory::getApplication()->redirect($thankyouUrl);
	}
}
