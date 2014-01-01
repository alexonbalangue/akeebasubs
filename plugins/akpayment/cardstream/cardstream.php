<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2014 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentCardstream extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'cardstream',
			'ppKey'			=> 'PLG_AKPAYMENT_CARDSTREAM_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/cardstream.png',
		));

		parent::__construct($subject, $config);
	}

	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * 'paymentForm' and a visible submit button.
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

		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();

		$data = (object)array(
			'merchantID'					=> $this->getMerchantID(),
			'amount'						=> (int)($subscription->gross_amount * 100),
			'countryCode'					=> trim($kuser->country),
			'currencyCode'					=> strtoupper(AkeebasubsHelperCparams::getParam('currency','GBP')),
			'transactionUnique'				=> $subscription->akeebasubs_subscription_id,
			'orderRef'						=> $level->title,
			'redirectURL'					=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=cardstream&sid='.$subscription->akeebasubs_subscription_id.'&mode=redirect',
			'callbackURL'					=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=cardstream&sid='.$subscription->akeebasubs_subscription_id.'&mode=cb',
			'customerName'					=> trim($user->name),
			'customerEmail'					=> trim($user->email),
			'customerAddress'				=> $this->getCustomerAddress($kuser),
			'customerPostCode'				=> $this->getCustomerPostCode($kuser),
			'item1Description'				=> $level->title,
			'item1Quantity'					=> 1,
			'item1GrossValue'				=> sprintf('%.2f',$subscription->gross_amount),
			'taxValue'						=> sprintf('%.2f',$subscription->tax_amount)
		);

		@ob_start();
		include dirname(__FILE__).'/cardstream/form.php';
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
			$id = $data['transactionUnique'];
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
                $slug = '';
			}
		}

		// Check that amount_gross is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription) && ($data['responseCode'] == 0)) {
			$mc_gross = floatval($data['amountReceived']);
			$gross = $subscription->gross_amount;
			if($mc_gross > 0) {
				// A positive value means 'payment'. The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			} else {
				$isPartialRefund = false;
				$temp_mc_gross = -1 * $mc_gross;
				$isPartialRefund = ($gross - $temp_mc_gross) > 0.01;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}

		// Check that the transaction has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['transactionID'] && $subscription->statue == 'C' && $data['mode'] == 'cb') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = 'I will not process the same transaction twice';
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) {
			$error_url = 'index.php?option=' . JRequest::getCmd('option') .
				'&view=level&slug=' . $slug .
				'&layout=' . JRequest::getCmd('layout','default');
			$error_url = JRoute::_($error_url,false);
			JFactory::getApplication()->redirect($error_url,$data['akeebasubs_failure_reason'],'error');
			return false;
		} else if($subscription->statue == 'C' && $data['mode'] == 'redirect') {
			// If this payment is already completed (by the callback) and the client gets redirected forward the client to the thank you page
			$thankYou = JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
			JFactory::getApplication()->redirect($thankYou);
			return true;
		}

		// Check the payment_status
		if($data['responseCode'] == 0) {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['transactionID'],
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

		// Redirect to thank you or cancel page
		if($newStatus == 'C') {
			$redirectUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
		} else {
			$redirectUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id, false);
		}
		JFactory::getApplication()->redirect($redirectUrl);
		return true;
	}
	
	private function getMerchantID()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return '100001';
		}
		return $this->params->get('merchant_id', '');
	}
	
	private function getCustomerAddress($kuser)
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'Flat 6 347
Somewhere';
		}
		return trim($kuser->address1);
	}
	
	private function getCustomerPostCode($kuser)
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'TA17 8A';
		}
		return trim($kuser->zip);
	}
}