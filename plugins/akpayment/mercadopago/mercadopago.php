<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentMercadopago extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'mercadopago',
			'ppKey'			=> 'PLG_AKPAYMENT_MERCADOPAGO_TITLE',
			'ppImage'		=> JURI::root().'plugins/akpayment/mercadopago/mercadopago/logo.png'
		));

		parent::__construct($subject, $config);

		// No cURL? Well, that's no point on continuing...
		if(!function_exists('curl_init'))
		{
			if(version_compare(JVERISON, '3.0', 'ge'))
			{
				throw new Exception('Mercadopago payment plugin needs cURL extension in order to work', 500);
			}
			else
			{
				JError::raiseError(500, 'Mercadopago payment plugin needs cURL extension in order to work');
			}
		}
	}

	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * "paymentForm" and a visible submit button.
	 *
	 * @param   string                      $paymentmethod
	 * @param   JUser                       $user
	 * @param   AkeebasubsTableLevel        $level
	 * @param   AkeebasubsTableSubscription $subscription
	 *
	 * @throws  Exception|JError
	 * @return  string
	 */
	public function onAKPaymentNew($paymentmethod, $user, $level, $subscription)
	{
		if($paymentmethod != $this->ppName) return false;

		require_once JPATH_ROOT.'/plugins/akpayment/mercadopago/mercadopago/lib/mercadopago.php';
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/image.php';

		// Required info are missing
		if(!$this->params->get('client_id') || !$this->params->get('client_secret'))
		{
			if(version_compare(JVERSION, '3.0', 'ge'))
			{
				throw new Exception('Mercadopago Payment Processor: Please supply a ClientID and a Client Secret info before continuing', 500);
			}
			else
			{
				JError::raiseError(500, 'Mercadopago Payment Processor: Please supply a ClientID and a Client Secret info before continuing');
			}
		}

		$mp = new MP($this->params->get('client_id'), $this->params->get('client_secret'));
		$mp->sandbox_mode((bool)$this->params->get('sandbox'));

		$preference = array(
			'external_reference' => $subscription->akeebasubs_subscription_id,
			'back_urls' => array(
				'success' => JRoute::_(JURI::root().'index.php?option=com_akeebasubs&view=message&slug='.$level->slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false),
				'failure' => JRoute::_(JURI::root().'index.php?option=com_akeebasubs&view=message&slug='.$level->slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id, false)
			),
			'items' => array(
				array(
					'title'        => $level->title,
					'quantity'     => 1,
					'currency_id'  => strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
					'unit_price'   => floatval($subscription->gross_amount),
					'picture_url'  => AkeebasubsHelperImage::getURL($level->image)
				)
			)
		);

		$preferenceResult = $mp->create_preference($preference);

		// No success on getting remote data? Stop here!
		if(!in_array($preferenceResult['status'], array(201, 200)))
		{
			if(version_compare(JVERISON, '3.0', 'ge'))
			{
				throw new Exception('There was an error while contacting the payment processor. Status code: '.$preferenceResult['status'], 500);
			}
			else
			{
				JError::raiseError(500, 'There was an error while contacting the payment processor. Status code: '.$preferenceResult['status']);
			}
		}

		$data = (object)array(
			'url'			=> $this->params->get('sandbox') ? $preferenceResult["response"]["sandbox_init_point"] : $preferenceResult["response"]["init_point"]
		);

		@ob_start();
		include dirname(__FILE__).'/mercadopago/form.php';
		$html = @ob_get_clean();

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;

		// Check IPN data for validity (i.e. protect against fraud attempt)
		$subscription = null;
		$isValid      = $this->isValidIPN($data);

		if(!$isValid)
		{
			$data['akeebasubs_failure_reason'] = 'MercadoPago reports transaction as invalid';
		}

		// Load the relevant subscription row
		if($isValid)
		{
			$id = array_key_exists('subscription', $data) ? (int)$data['subscription'] : -1;
			if($id > 0)
			{
				$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
									->setId($id)
									->getItem();
				if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id) )
				{
					$subscription = null;
					$isValid = false;
				}
			}
			else
			{
				$isValid = false;
			}

			if(!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("subscription" field) is invalid';
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;

		if($isValid && !is_null($subscription))
		{
			$mc_gross = floatval($data['amount']);
			$gross = $subscription->gross_amount;

			if($mc_gross > 0)
			{
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			}
			else
			{
				$temp_mc_gross = -1 * $mc_gross;
				$isPartialRefund = ($gross - $temp_mc_gross) > 0.01;
			}
			if(!$isValid)
			{
				$data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}

		// Check that txn_id has not been previously processed
		if($isValid && !is_null($subscription) && !$isPartialRefund)
		{
			if($subscription->processor_key == $data['id'])
			{
				if($subscription->state == 'C')
				{
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same payment id twice";
				}
			}
		}

		// Check that the currency is correct
		if($isValid && !is_null($subscription))
		{
			$mc_currency = strtoupper($data['currency']);
			$currency    = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));

			if($mc_currency != $currency)
			{
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invalid currency; expected $currency, got $mc_currency";
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;

		// Check the payment_status
		switch(strtolower($data['status']))
		{
			case 'approved':
				$newStatus = 'C';
				break;
			case 'pending'      :
			case 'in_process'   :
			case 'in_mediation' :
				$newStatus = 'P';
				break;

			case 'rejected' :
			case 'cancelled':
			default:
				// Partial refunds can only by issued by the merchant. In that case,
				// we don't want the subscription to be cancelled. We have to let the
				// merchant adjust its parameters if needed.
				if($isPartialRefund) {
					$newStatus = 'C';
				} else {
					$newStatus = 'X';
				}
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id'	=> $id,
			'processor_key'		            => $data['id'],
			'state'				            => $newStatus,
			'enabled'			            => 0
		);


		JLoader::import('joomla.utilities.date');
		if($newStatus == 'C')
		{
			$this->fixSubscriptionDates($subscription, $updates);
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

		return true;
	}

	/**
	 * Validates the incoming data against to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN(&$data)
	{
		require_once JPATH_ROOT.'/plugins/akpayment/mercadopago/mercadopago/lib/mercadopago.php';

		if(!$data['id'])
		{
			$data['akeebasubs_ipncheck_failure'] = 'Missing transaction ID';
			return false;
		}

		$mp = new MP($this->params->get('client_id'), $this->params->get('client_secret'));
		$mp->sandbox_mode((bool)$this->params->get('sandbox'));

		try
		{
			$response = $mp->get_payment_info((int)$data['id']);
		}
		catch(Exception $e)
		{
			$data['akeebasubs_ipncheck_failure'] = 'MercadoPago thrown and exception: '.$e->getCode().' - '.$e->getMessage();
			return false;
		}

		// Let's inject useful info
		$data['subscription'] = $response['response']['collection']['external_reference'];
		$data['currency']     = $response['response']['collection']['currency_id'];
		$data['amount']       = $response['response']['collection']['total_paid_amount'];
		$data['status']       = $response['response']['collection']['status'];

		return true;
	}
}