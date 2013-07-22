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
			'url'			=> $this->getPaymentURL(),
			'postback'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=paysafe',
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

		// Connect to PaySafe's SOAP API
		$api = new SOPGClassicMerchantClient($data->url->api);

		// Create the disposition
		$response = $api->createDisposition($data->username, $data->password, $data->mtid,
			null, $subscription->gross_amount, $data->currency, $data->success, $data->cancel,
			$subscription->akeebasubs_subscription_id, $data->postback, null);

		if (($response->resultCode != 0) || ($response->errorCode ==0))
		{
			die("PaySafe error creating disposition -- Result code {$response->resultCode} -- Error code {$response->errorCode}");
		}

		$data->mid = $response->mid;
		$data->subId = $response->subId;

		// Redirect the user
		@ob_start();
		include dirname(__FILE__).'/paysafe/form.php';
		$html = @ob_get_clean();

		return $html;
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

		// Extract subscription ID from $data['mtid']
		if ($isValid) {
			$mtid = array_key_exists('mtid', $data) ? $data['mtid'] : '0_-1';
			list($random, $id) = explode('_', $mtid);
			$id = (int)$id;

			$subscription = null;

			if ($id > 0)
			{
				$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->setId($id)
					->getItem();

				if (($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id))
				{
					$subscription = null;
					$isValid = false;
				}
			}
			else
			{
				$isValid = false;
			}
		}

		if(!$isValid)
		{
			$data['akeebasubs_failure_reason'] = 'The referenced subscription ID in mtid is invalid';
		}

		// Fraud attempt? Do nothing more!
		if (!$isValid)
		{
			$this->logIPN($data, $isValid);

			return false;
		}

		// Get some data
		$data = (object)array(
			'url'			=> $this->getPaymentURL(),
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'username'		=> $this->params->get('username', ''),
			'password'		=> $this->params->get('password', ''),
			'subId'			=> null,
		);

		// Connect to PaySafe's SOAP API
		$api = new SOPGClassicMerchantClient($data->url->api);

		// Execute the debit
		$response = $api->executeDebit($data->username, $data->password, $mtid, $data->subId,
			$subscription->gross_amount, $data->currency, 1, null);

		if (($response->resultCode != 0) || ($response->errorCode ==0))
		{
			$isValid = false;
			$data['akeebasubs_failure_reason'] = "PaySafe error executing debit -- Result code {$response->resultCode} -- Error code {$response->errorCode}";
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'		=> $mtid,
			'state'				=> 'C',
			'enabled'			=> 0
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
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$ret = (object)array(
			'api'		=> '',
			'customer'	=> '',
		);

		$sandbox = $this->params->get('sandbox',0);

		if ($sandbox)
		{
			$ret->api = 'https://soatest.paysafecard.com/psc/services/PscService?wsdl';
			$ret->customer = 'https:// customer.test.at.paysafecard.com/psccustomer/GetCustomerPanelServlet';
		}
		else
		{
			$ret->api = 'https://soa.paysafecard.com/psc/services/PscService?wsdl';
			$ret->customer = 'https:// customer.cc.at.paysafecard.com/psccustomer/GetCustomerPanelServlet';
		}

		return $ret;
	}

	/**
	 * Validates the incoming data against PayPal's IPN to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN(&$data)
	{
		if (!array_key_exists('serialNumbers', $data))
		{
			$data['akeebasubs_ipncheck_failure'] = 'No serial numbers in request';

			return false;
		}

		if (!array_key_exists('eventType', $data))
		{
			$data['akeebasubs_ipncheck_failure'] = 'No eventType in request';

			return false;
		}

		if ($data['eventType'] != 'ASSIGN_CARDS')
		{
			$data['akeebasubs_ipncheck_failure'] = 'Invalid eventType';

			return false;
		}

		return true;
	}
}