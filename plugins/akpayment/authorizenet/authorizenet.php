<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentAuthorizeNet extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'authorizenet',
			'ppKey'			=> 'PLG_AKPAYMENT_AUTHORIZENET_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/authorizenet-logo.gif',
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
		
		$nameParts = explode(' ', trim($user->name), 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
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
			//'url' => 'https://test.authorize.net/gateway/transact.dll',
			'url'					=> 'https://secure.authorize.net/gateway/transact.dll',
			'x_version'				=> '3.1',
			'x_type'				=> 'AUTH_CAPTURE',
			'x_login'				=> trim($this->params->get('api_id','')),
			'x_amount'				=> sprintf('%.2f',$subscription->gross_amount),
			'x_tax'					=> sprintf('%.2f',$subscription->tax_amount),
			'x_currency_code'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','USD')),
			'x_description'			=> $level->title,
			'x_invoice_num'			=> $subscription->akeebasubs_subscription_id,
			'x_fp_sequence'			=> $subscription->akeebasubs_subscription_id,
			'x_fp_timestamp'		=> time(),
			'x_test_request'		=> $this->params->get('sandbox',0),
			'x_show_form'			=> 'PAYMENT_FORM',
			'x_first_name'			=> $firstName,
			'x_last_name'			=> $lastName,
			'x_address'				=> trim($kuser->address1),
			'x_city'				=> trim($kuser->city),
			'x_zip'					=> trim($kuser->zip),
			'x_country'				=> AkeebasubsHelperSelect::decodeCountry(trim($kuser->country)),
			'x_email'				=> trim($user->email),
			'x_cancel_url'			=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'x_relay_response'		=> 'TRUE',
			'x_relay_url'			=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=authorizenet&sid='.$subscription->akeebasubs_subscription_id
		);
		
		$state = trim($kuser->state);
		if(!empty($state)) {
			$data->x_state = $state;
		}
		
		if($kuser->isbusiness) {
			$data->x_company = trim($kuser->businessname);
		}
		
		$transactionKey = trim($this->params->get('key',''));
		$data->x_fp_hash = $this->getFingerprint(
				$data->x_login,
				$transactionKey,
				$data->x_amount,
				$data->x_fp_sequence,
				$data->x_fp_timestamp,
				$data->x_currency_code
			);

		@ob_start();
		include dirname(__FILE__).'/authorizenet/form.php';
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
			$id = $data['x_invoice_num'];
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
		}
        
		// Check that the transaction has not been previously processed
		$transactionId = $this->getTransactionNumber($data);
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $transactionId
					&& $subscription->statue == 'C') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same transaction twice";
			}
		}
		
		// Check that amount_gross is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['x_amount']);
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
				'&view=level&slug='.$subscription->slug.
				'&layout='.JRequest::getCmd('layout','default');
			$error_url = JRoute::_($error_url,false);
			JFactory::getApplication()->redirect($error_url,$data['akeebasubs_failure_reason'],'error');
			return false;
		}

		// Check the payment_status
		switch($data['x_response_code']) {
			case 1:
				$newStatus = 'C';
				break;
			case 4:
				$newStatus = 'P';
				break;
			default:
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $transactionId,
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
	
	private function getFingerprint($apiLoginId, $transactionKey, $amount, $sequence, $timestamp, $currencyCode)
    {
        if(function_exists('hash_hmac')) {
            return hash_hmac("md5", $apiLoginId . "^" . $sequence . "^" . $timestamp . "^" . $amount . "^" . $currencyCode, $transactionKey); 
        }
        return bin2hex(mhash(MHASH_MD5, $apiLoginId . "^" . $sequence . "^" . $timestamp . "^" . $amount . "^" . $currencyCode, $transactionKey));
    }
	
	private function getTransactionNumber($data)
	{
		$testMode = $this->params->get('sandbox',0);
		if($testMode) {
			// The param x_trans_id is always 0 in test-mode.
			// For this case generate another unique id:
			return md5($data['x_invoice_num'] . time());	
		}
		return $data['x_trans_id'];
	}
	
	private function isValidIPN($data)
	{
		$hashValue = trim($this->params->get('hash',''));
		if(empty($hashValue)) {
			// If the hash value is not entered we skip this check
			return true;
		}
		$hashString = $hashValue .
				trim($this->params->get('api_id','')) .
				$data['x_trans_id'] .
				$data['x_amount'];
		$calculatedHash = strtoupper(md5($hashString));
		return $calculatedHash == $data['x_MD5_Hash'];
	}
}