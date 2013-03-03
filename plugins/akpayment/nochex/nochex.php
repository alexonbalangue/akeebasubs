<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentNochex extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'nochex',
			'ppKey'			=> 'PLG_AKPAYMENT_NOCHEX_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/nochex-logo.gif'
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
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		$rootURL = rtrim(JURI::base(),'/');
		
		$data = (object)array(
			'url'					=> 'https://secure.nochex.com',
			'merchant_id'			=> trim($this->params->get('merchant_id','')),
			'amount'				=> sprintf('%.2f',$subscription->gross_amount),
			'order_id'				=> $subscription->akeebasubs_subscription_id,
			'description'			=> $level->title . ' - [ ' . $user->username . ' ]',
			'billing_fullname'		=> trim($user->name),
			'billing_address'		=> trim($kuser->address1) . '
' . trim($kuser->city) . '
' . AkeebasubsHelperSelect::decodeCountry(trim($kuser->country)),
			'billing_postcode'		=> trim($kuser->zip),
			'email_address'			=> trim($user->email),
			'callback_url'			=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=nochex',
			'success_url'			=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel_url'			=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id))
		);
		
		if($this->params->get('sandbox',0)) {
			$data->test_transaction = '100';
			$data->test_success_url = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
		}

		@ob_start();
		include dirname(__FILE__).'/nochex/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this$
		if($paymentmethod != $this->ppName) return false;
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['order_id'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The ' . $data[$p['subscription_id']] . ' is invalid. ' . $data['message'];
		}
        
		// Check that to_email is the merchant's one
		if($isValid && !is_null($subscription)) {
			if(trim($this->params->get('merchant_id','')) != trim($data['to_email'])) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "The recipient of the payment is " . $data['to_email'] . " but the merchant's one is " . $this->params->get('merchant_id','');
			}
		}
        
		// Check that transaction_id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['transaction_id']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same " . $data['transaction_id'] . " twice";
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
		if(!$isValid) return false;

		// Payment status always complete if this point is reached
		$newStatus = 'C';

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['transaction_id'],
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
   
		return true;
	}
	
	private function getCallbackURL()
	{
		$ssl = $this->params->get('secureipn',0);
		$scheme = $ssl ? 'ssl://' : '';
		return $scheme.'www.nochex.com';
	}
	
	private function getCallbackPort()
	{
		$ssl = $this->params->get('secureipn',0);
		return $ssl ? 443 : 80;
	}
	
	private function isValidIPN($data)
	{
		if(! empty($data)) {
			// Build the request (send the same parameters back that we received)
			$req = '';
			foreach ($data as $param => $val) {
				$req .= urlencode($param) . '=' . urlencode($val) . '&';
			}
			$req = substr($req, 0, -1);
			
			if(strlen($req) > 0) {
				$header = "POST /nochex.dll/apc/apc HTTP/1.0\r\n";
				$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

				// Send the request and get the result
				$fp = fsockopen($this->getCallbackURL(), $this->getCallbackPort(), $errno, $errstr, 10);
				if(!$fp) {
					// HTTP ERROR
					return false;
				} else {
					fputs ($fp, $header . $req);
					$res = '';
					while (!feof($fp)) {
						$res .= fgets($fp, 1024);
					}
					fclose ($fp);
				}

				// Validate the result
				if(strstr($res, "AUTHORISED")) {
					return true;
				}
			}
		}
		return false;
	}
}