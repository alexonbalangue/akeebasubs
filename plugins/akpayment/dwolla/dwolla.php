<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentDwolla extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'dwolla',
			'ppKey'			=> 'PLG_AKPAYMENT_DWOLLA_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/dwolla.png',
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
		
		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=dwolla&sid=' . $subscription->akeebasubs_subscription_id;
		
		$data = (object)array(
			'url'			=> 'https://www.dwolla.com/payment/pay',
			'key'			=> $this->params->get('key',''),
			'callback'		=> $callbackUrl,
			'redirect'		=> $callbackUrl,
			'name'			=> $level->title,
			'description'	=> $level->title . ' - [' . $user->username . ']',
			'destinationid'	=> $this->params->get('accountid',''),
			'amount'		=> sprintf('%.2f',$subscription->gross_amount),
			'shipping'		=> '0.00',
			'tax'			=> sprintf('%.2f',$subscription->tax_amount),
			'orderid'		=> $subscription->akeebasubs_subscription_id,
			'timestamp'		=> time()
		);
		
		$data->signature = hash_hmac('sha1',
				$data->key.'&'.$data->timestamp.'&'.$data->orderid,
				trim($this->params->get('secret','')));

		@ob_start();
		include dirname(__FILE__).'/dwolla/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		if($data['error'] == 'failure') {
			$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->setId($data['sid'])
					->getItem();
			if($data['error_description'] == 'User Cancelled') {
				$cancelUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$subscription->slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id, false);
				JFactory::getApplication()->redirect($cancelUrl);
			} else {
				$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
					->setId($subscription->akeebasubs_level_id)
					->getItem();
				$error_url = 'index.php?option='.JRequest::getCmd('option').
					'&view=level&slug='.$level->slug.
					'&layout='.JRequest::getCmd('layout','default');
				$error_url = JRoute::_($error_url,false);
				JFactory::getApplication()->redirect($error_url,$data['error_description'],'error');
			}
			return false;
		}
        
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['orderid'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The reference is invalid';
		}
        
		// Check that transaction has not been previously processed
		if($isValid) {
			if($data['transaction'] == $subscription->processor_key && $subscription->state == 'C') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not processed this transaction twice";
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['amount']);
			$gross = $subscription->gross_amount;
			if($mc_gross > 0) {
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			} else {
				$isPartialRefund = false;
				$temp_mc_gross = -1 * $mc_gross;
				$isPartialRefund = ($gross - $temp_mc_gross) > 0.01;
			}
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
		if($data['postback'] == 'success' && $data['status'] == 'Completed') {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';	
		}

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['transaction'],
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
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$checksum = hash_hmac('sha1',
				$data['checkoutId'].'&'.$data['amount'],
				trim($this->params->get('secret','')));
		return $checksum == $data['signature'];
	}
}