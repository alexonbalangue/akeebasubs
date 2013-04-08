<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentMoip extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'moip',
			'ppKey'			=> 'PLG_AKPAYMENT_MOIP_TITLE',
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
			'url'			=> $this->getPaymentURL(),
			'merchant'		=> $this->params->get('merchant',''),
			'postback'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=upay',
			'success'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
		);

		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();

		@ob_start();
		include dirname(__FILE__).'/moip/form.php';
		$html = @ob_get_clean();

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;

		// Surprisingly, MoIP has no fraud detection whatsoever. WTF!
		$isValid = true;

		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('id_transacao', $data) ? (int)$data['id_transacao'] : -1;
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("ext_trans_id" field) is invalid';
		}

		// Check that valor is correct
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['valor']);
			// Convert to floating point and check
			$mc_gross = $mc_gross / 100;
			$gross = $subscription->gross_amount;
			$isValid = ($gross - $mc_gross) < 0.01;
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}

		// Check that cod_moip has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['cod_moip']) {
				if($subscription->state == 'C') {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same cod_moip twice";
				}
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;

		// Check the payment_status
		switch($data['status_pagamento'])
		{
			case 3: // Billet printed but not yet paid
			case 6: // Being analyzed (possible fraud)
				$newStatus = 'P';
				break;

			case 1: // Authorized
			case 4: // Completed
				$newStatus = 'C';
				break;

			// Apparently MOIP's instructions translation is waaaay off.
			// They always send status #2 when the user pays and has to be
			// ignored...
			case 2: // Abandoned or in progress
				$newStatus = 'N'; // We have to do no further processing
				break;

			case 5: // Cancelled by client, bank, MOIP or receiver
			case 7: // Refunded
			default:
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'		=> $data['cod_moip'],
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
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://desenvolvedor.moip.com.br/sandbox/PagamentoMoIP.do';
		} else {
			return 'https://www.moip.com.br/PagamentoMoIP.do';
		}
	}
}