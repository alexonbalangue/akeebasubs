<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentPaysafe extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'paysafe',
			'ppKey'			=> 'PLG_AKPAYMENT_PAYSAFE_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/paysafe.gif'
		));

		parent::__construct($subject, $config);

		// Load the PaySafe SOAP client class
		@include_once __DIR__ . '/paysafe/client/SOPGClassicMerchantClient.php';
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

		$time = gettimeofday();

		$data = (object)array(
			'postback'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=paysafe&subid=' . $subscription->akeebasubs_subscription_id,
			'success'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'mtid'			=> $time['sec'] . $time['usec'] . '_' . $subscription->akeebasubs_subscription_id,
			'username'		=> $this->params->get('username', ''),
			'password'		=> $this->params->get('password', ''),
			'mid'			=> '',
			'subId'			=> '',
		);

		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();

		$sandbox = $this->params->get('sandbox',0);
		$mode = $sandbox ? 'test' : 'live';

		// Connect to PaySafe's SOAP API
		$api = new SOPGClassicMerchantClient(false, 'en', false, $mode);
		$api->merchant($data->username, $data->password);
		$api->setCustomer($subscription->gross_amount, $data->currency, $data->mtid, $subscription->akeebasubs_subscription_id);
		$api->setURL($data->success, $data->cancel, $data->postback);
		$paymentPanel = $api->createDisposition();

		if ($paymentPanel == false)
		{
			die("PaySafe error creating disposition");
		}

		JFactory::getApplication()->redirect($paymentPanel);
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;

		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid)
		{
			$data['akeebasubs_failure_reason'] = 'Invalid data; this does not look like a PaySafe response';
		}

		if ($isValid) {
			$sandbox = $this->params->get('sandbox',0);
			$mode = $sandbox ? 'test' : 'live';

			// Connect to PaySafe's SOAP API
			$api = new SOPGClassicMerchantClient(false, 'en', false, $mode);
			$api->merchant($data->username, $data->password);

			$status = $api->getSerialNumbers($data['mtid'], $data['cur'], $subId = '');

			if ($status != 'execute')
			{
				$isValid = false;

				$data['akeebasubs_failure_reason'] = 'Expected status: execute. Got status: ' . $status;
			}
		}

		if ($isValid)
		{
			$testexecute = $api->executeDebit( $data['amo'], '1' );

			if (!$testexecute)
			{
				$isValid = false;

				$data['akeebasubs_failure_reason'] = 'executeDebit failed';
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if (!$isValid)
		{
			return false;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id'	=> $data['subid'],
			'processor_key'					=> $data['mtid'],
			'state'							=> 'C',
			'enabled'						=> 0
		);

		JLoader::import('joomla.utilities.date');

		$this->fixDates($subscription, $updates);

		// Save the changes
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
	 * Validates the incoming data against PayPal's IPN to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN(&$data)
	{
		if (!array_key_exists('mtid', $data))
		{
			$data['akeebasubs_ipncheck_failure'] = 'No mtid in request';

			return false;
		}

		if (!array_key_exists('cur', $data))
		{
			$data['akeebasubs_ipncheck_failure'] = 'No currency in request';

			return false;
		}

		return true;
	}
}