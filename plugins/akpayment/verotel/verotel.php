<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentVerotel extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'verotel',
			'ppKey'			=> 'PLG_AKPAYMENT_VEROTEL_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/logoSmall_verotel.png'
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
		
		$data = (object)array(
			'url'				=> 'https://secure.verotel.com/order/purchase',
			'shopID'			=> trim($this->params->get('shopid','')),
			'priceAmount'		=> sprintf('%.2f',$subscription->gross_amount),
			// Currency must be one of these: USD, EUR, GBP, NOK, SEK, DKK, CAD or CHF
			'priceCurrency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'description'		=> $level->title . ' - [ ' . $user->username . ' ]',
			'referenceID'		=> $subscription->akeebasubs_subscription_id
		);
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
        
		$signatureKey = $this->params->get('key','');
		$data->signature = sha1($signatureKey . ":description=" . $data->description .
			':priceAmount=' . $data->priceAmount .
			':priceCurrency=' .$data->priceCurrency .
			':referenceID=' .$data->referenceID .
			':shopID=' . $data->shopID.
			':version=1'
		);

		@ob_start();
		include dirname(__FILE__).'/verotel/form.php';
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
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['referenceID'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenceID is invalid';
		}
        
		// Check that saleID has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['saleID']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same saleID twice";
			}
		}
        
		// Check that priceCurrency is correct
		if($isValid && !is_null($subscription)) {
			$mc_currency = strtoupper($data['priceCurrency']);
			$currency = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
			if($mc_currency != $currency) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invalid currency; expected $currency, got $mc_currency";
			}
		}
		
		// Check that priceAmount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['priceAmount']);
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
		if(!$isValid) return false;
		
		// Payment status always complete if this point is reached
		$newStatus = 'C';

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $data['referenceID'],
				'processor_key'					=> $data['saleID'],
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

		// Callback is valid - respond with OK
		@ob_end_clean();
		echo "OK";
		$app->close();
        
		return true;
	}
    
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$isValid = true;

		// Check the required data
		$signatureKey = $this->params->get('key','');
		if(empty($signatureKey)) $isValid = false;
		if(empty($data['priceAmount'])) $isValid = false;
		if(empty($data['priceCurrency'])) $isValid = false;
		if(empty($data['referenceID'])) $isValid = false;
		if(empty($data['saleID'])) $isValid = false;
		if(empty($data['shopID'])) $isValid = false;
		if(empty($data['signature'])) $isValid = false;

		// Check the signature
		if($isValid) {
			$signature = sha1($signatureKey .
				":priceAmount=" . $data['priceAmount'] .
				":priceCurrency=" . $data['priceCurrency'] .
				":referenceID=" . $data['referenceID'] .
				":saleID=" . $data['saleID'] .
				":shopID=" . $data['shopID']);

			$isValid = $data['signature'] == $signature;
		}

		return $isValid;
	}
 
	private function getResponseValue($purchaseResponse, $parameter)
	{
		preg_match("/^$parameter: ([^\r\n\t\f]+)/m", $purchaseResponse, $matches);
		if(! empty($matches[1])) {
			return trim($matches[1]);
		}
		return "";
	}
}