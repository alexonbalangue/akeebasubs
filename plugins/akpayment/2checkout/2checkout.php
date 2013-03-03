<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpayment2checkout extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> '2checkout',
			'ppKey'			=> 'PLG_AKPAYMENT_2CHECKOUT_TITLE',
			'ppImage'		=> 'https://www.2checkout.com/images/paymentlogoshorizontal.png',
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

		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;

		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		$data = (object)array(
			'url' => ($this->params->get('checkout') == 'single') ? 'https://www.2checkout.com/checkout/spurchase' : 'https://www.2checkout.com/checkout/purchase',
			'sid'			=> $this->params->get('sid',''),
			'x_receipt_link_url'	=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'params'		=> $this->params,
			'name'			=> $user->name,
			'email'			=> $user->email
		);

		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();

		@ob_start();
		include dirname(__FILE__).'/2checkout/form.php';
		$html = @ob_get_clean();

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;

		// Check if it's one of the message types supported by this plugin
		$message_type = $data['message_type'];
		$isValid = in_array($message_type, array(
			'ORDER_CREATED', 'REFUND_ISSUED', 'RECURRING_INSTALLMENT_SUCCESS', 'FRAUD_STATUS_CHANGED', 'INVOICE_STATUS_CHANGED'
		));
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'INS message type "'.$message_type.'" is not supported.';

		// Check IPN data for validity (i.e. protect against fraud attempt)
		if($isValid) {
			$isValid = $this->isValidIPN($data);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Transaction MD5 signature is invalid. Fraudulent transaction or testing mode enabled.';
		}

		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('item_id_1', $data) ? (int)$data['item_id_1'] : -1;
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("item_id_1" field) is invalid';
		}

		// Check that order_number has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['sale_id'].'/'.$data['invoice_id']) {
				if(($subscription->state == 'C') && ($message_type == 'ORDER_CREATED')) {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same sale_id/invoice_id twice";
				}
			}
		}

		// Check that total is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['invoice_list_amount']);
			$gross = $subscription->gross_amount;
			if($mc_gross > 0) {
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			} else {
				$valid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount (invoice_list_amount) does not match the subscription amount';
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;

		// Load the subscription level and get its slug
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;

		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		switch($message_type) {
			case 'ORDER_CREATED':
			case 'FRAUD_STATUS_CHANGED':
			case 'INVOICE_STATUS_CHANGED':
				// Let me translate the goofy statuses sent by 2Checkout to English for ya
				switch($data['invoice_status'])
				{
					case 'approved':
						// "Approved" means "we're about to request the money" or something like that, dunno
						$newStatus = 'C';
						break;

					case 'pending':
						// "Pending" means "accepted by bank, the money is not in your account yet"
						$newStatus = 'C';
						break;

					case 'deposited':
						// "Deposited" means "the money is yours".
						$newStatus = 'C';
						// However, if the subscription is CANCELLED then a refund has already
						// been issued, but 2Checkout sends a "deposited" status. What the hell?!
						if($subscription->state == 'X') {
							$newStatus = 'X';
						}
						break;

					case 'declined':
					default:
						// "Declined" means "you ain't gonna have your money, bro"
						$newStatus = 'X';
						break;
				}
				break;

			case 'REFUND_ISSUED':
				$newStatus = 'X';
				break;

			case 'RECURRING_INSTALLMENT_SUCCESS':
				// @todo Handle recurring payments
				$newStatus = 'C';
				break;

		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'		=> $data['sale_id'].'/'.$data['invoice_id'],
			'state'				=> $newStatus,
			'enabled'			=> 0
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
	 * Validates the incoming data against the md5 posted by 2Checkout to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN($data)
	{
		// This is the MD5 calculations in 2Checkout's INS guide
		$incoming_md5 = strtoupper($data['md5_hash']);
		$calculated_md5 = md5(
			$data['sale_id'].
			$data['vendor_id'].
			$data['invoice_id'].
			$this->params->get('secret','')
		);
		$calculated_md5 = strtoupper($calculated_md5);

		return ($calculated_md5 == $incoming_md5);
	}
}