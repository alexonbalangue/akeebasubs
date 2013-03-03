<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentBeanstream extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'beanstream',
			'ppKey'			=> 'PLG_AKPAYMENT_BEANSTREAM_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/beanstream-logo.jpg',
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
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=beanstream&marker=000';
		
		$data = (object)array(
			'url'				=> 'https://www.beanstream.com/scripts/payment/payment.asp',
			'merchant_id'		=> trim($this->params->get('merchant_id','')),
			'trnOrderNumber'	=> $subscription->akeebasubs_subscription_id,
			'trnAmount'			=> sprintf('%.2f',$subscription->gross_amount),
			'declinedPage'		=> $callbackUrl,
			'approvedPage'		=> $callbackUrl,
			'ordName'			=> trim($user->name),
			'ordEmailAddress'	=> trim($user->email),
			'ordAddress1'		=> trim($kuser->address1),
			'ordAddress2'		=> trim($kuser->address2),
			'ordCity'			=> trim($kuser->city),
			'ordProvince'		=> trim($kuser->state),
			'ordPostalCode'		=> trim($kuser->zip),
			'ordCountry'		=> strtoupper(trim($kuser->country))
		);
		
		// Language settings ("fre" or "eng")
		try {
			$lang = strtolower(substr(JFactory::getLanguage()->getTag(), 0, 2));
			$data->trnLanguage = ($lang == 'fr') ? 'fre' : 'eng';
		} catch(Exception $e) {
			// Shouldn't happend. But setting the language is optional... so do nothing here.
		}

		@ob_start();
		include dirname(__FILE__).'/beanstream/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Clean up second question mark in response
		preg_match('/000\?trnApproved=(.+)/', $data['marker'], $matches);
		$data['trnApproved'] = $matches[1];
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['trnOrderNumber'];
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
        
		// Check that trnId has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['trnId']
					&& !(empty($data['trnId']) && $data['messageText'] == "Payment Canceled")) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same transaction " . $data['trnId'] . " twice";
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['trnAmount']);
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

		// Check the payment_status
		switch($data['trnApproved'])
		{
			case 1:
				$newStatus = 'C';
				break;
			default:
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['trnId'],
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
		
		// Redirect to success- or decline-URL
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		$redirectUrl = null;
		if($data['trnApproved'] == 1) {
			$defaultSuccessUrl = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
			$redirectUrl = trim($this->params->get('success_url', $defaultSuccessUrl));
		} else {
			$defaultCancelUrl = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id));
			if(empty($data['trnId']) && $data['messageText'] == "Payment Canceled") {
				// Customer cancelled the payment
				$redirectUrl = $defaultCancelUrl;
			} else {
				$redirectUrl = trim($this->params->get('decline_url', $defaultCancelUrl));
			}
		}
		// Only append the query to non-SEO URLs (doesn't contain a dot)
		// so that these will still work too, but the parameters are not added (which is not necessarily needed)
		if(strpos($redirectUrl, '.') !== false) {
			$query = $_SERVER['QUERY_STRING'];
			preg_match('/marker=000\?(.+)/', $query, $matches);
			$query = $matches[1];
			if(strpos($redirectUrl, '?') === false) {
				$redirectUrl .= '?' . $query;
			} else {
				$redirectUrl .= '&' . $query;
			}	
		}
		$app->redirect($redirectUrl);
   
		return true;
	}
	
	private function isValidIPN($data)
	{
		static $responseVars = array(
			'trnApproved',
			'trnId',
			'messageId',
			'messageText',
			'authCode',
			'responseType',
			'trnAmount',
			'trnDate',
			'trnOrderNumber',
			'trnLanguage',
			'trnCustomerName',
			'trnEmailAddress',
			'trnPhoneNumber',
			'avsProcessed',
			'avsId',
			'avsResult',
			'avsAddrMatch',
			'avsPostalMatch',
			'avsMessage',
			'cvdId',
			'cardType',
			'trnType',
			'paymentMethod',
			'ref1',
			'ref2',
			'ref3',
			'ref4',
			'ref5'
		);
		if($data['hashValue']) {
			$query = $_SERVER['QUERY_STRING'];
			$hashString = '';
			foreach($responseVars as $var) {
				preg_match('/' . $var . '=([^&]+)/', $query, $matches);
				$val = '';
				if(isset($matches[1])) $val = $matches[1];
				$hashString .= $var . '=' . $val . '&';
			}
			$hashString = substr($hashString, 0, -1);
			$calcHash = sha1($hashString . trim($this->params->get('hash_key','')));
			return $calcHash == $data['hashValue'];	
		}
		return true;
	}
}