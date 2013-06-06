<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentPrzelewy24 extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'przelewy24',
			'ppKey'			=> 'PLG_AKPAYMENT_PRZELEWY24_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/przelewy24.png'
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
		
		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=przelewy24';
		
		$data = (object)array(
			'url'					=> 'https://secure.przelewy24.pl/index.php',
			'p24_session_id'		=> str_replace(' ', '', $level->title) . '-' . $subscription->akeebasubs_subscription_id,
			'p24_id_sprzedawcy'		=> trim($this->params->get('seller_id','')),
			// Amount in Polish Grosz (PLN/100)
			'p24_kwota'				=> (int)($subscription->gross_amount * 100),
			'p24_opis'				=> $level->title,
			'p24_klient'			=> trim($user->name),
			'p24_adres'				=> trim($kuser->address1),
			'p24_kod'				=> trim($kuser->zip),
			'p24_miasto'			=> trim($kuser->city),
			'p24_kraj'				=> strtoupper(trim($kuser->country)),
			'p24_email'				=> trim($user->email),
			'p24_return_url_ok'		=> $callbackUrl,
			'p24_return_url_error'	=> $callbackUrl
		);
		
		// Language settings PL/EN/DE/ES/IT
		try {
			$lang = strtolower(substr(JFactory::getLanguage()->getTag(), 0, 2));
			if($lang == 'pl') {
				$data->p24_language = 'pl';
			} else if($lang == 'de') {
				$data->p24_language = 'de';
			} else if($lang == 'es') {
				$data->p24_language = 'es';
			} else if($lang == 'it') {
				$data->p24_language = 'it';
			} else {
				$data->p24_language = 'en';
			}
		} catch(Exception $e) {
			$data->p24_language = 'pl';
		}
		
		$crc = md5($data->p24_session_id .
				'|'	. $data->p24_id_sprzedawcy .
				'|' . $data->p24_kwota .
				'|' . trim($this->params->get('crc_key','')));
		$data->p24_crc = $crc;

		@ob_start();
		include dirname(__FILE__).'/przelewy24/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Load the relevant subscription row
		$sessionId = $data['p24_session_id'];
		$id = substr(strrchr($sessionId, '-'), 1);
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
		
		// Error response
		if($isValid && isset($data['p24_error_code'])) {
			$data['akeebasubs_failure_reason'] = "Error code " . $data['p24_error_code'];
			$this->logIPN($data, false);
			$updates = array(
					'akeebasubs_subscription_id'	=> $data['p24_session_id'],
					'processor_key'					=> '',
					'state'							=> 'X',
					'enabled'						=> 0
			);
			
			$error_url = 'index.php?option='.JRequest::getCmd('option').
				'&view=level&slug='.$subscription->slug.
				'&layout='.JRequest::getCmd('layout','default');
			$error_url = JRoute::_($error_url,false);
			JFactory::getApplication()->redirect($error_url,$data['akeebasubs_failure_reason'],'error');
			return false;
		}
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';
        
		// Check that p24_order_id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['p24_order_id']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same transaction " . $data['p24_order_id'] . " twice";
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['p24_kwota']);
			$gross = (int)($subscription->gross_amount * 100);
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
		
		if($isValid) {
			$verificationRequest = array();
			$verificationRequest['p24_id_sprzedawcy'] = trim($this->params->get('seller_id',''));
			$verificationRequest['p24_session_id'] = $data['p24_session_id'];
			$verificationRequest['p24_order_id'] = $data['p24_order_id'];
			$verificationRequest['p24_kwota'] = $data['p24_kwota'];
			$crc = md5($verificationRequest['p24_session_id'] .
				'|'	. $verificationRequest['p24_order_id'] .
				'|' . $verificationRequest['p24_kwota'] .
				'|' . trim($this->params->get('crc_key','')));
			$verificationRequest['p24_crc'] = $crc;
			$requestQuery = http_build_query($verificationRequest);
			$requestContext = stream_context_create(array(
				'http' => array (
					'method' => 'POST',
					'header' => "Connection: close\r\n".
								"Content-Length: " . strlen($requestQuery) . "\r\n",
					'content'=> $requestQuery)
				));
			$response = file_get_contents (
					'https://secure.przelewy24.pl/transakcja.php',
					false,
					$requestContext);
			// Check response
			if(! preg_match('/^RESULT.+TRUE$/s', trim($response))) {
				$isValid = false;
				$errorMessage = preg_replace('/[\s]+/', ': ', trim($response));
				$data['akeebasubs_failure_reason'] = $errorMessage;
			}
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

		// Payment complete at this point
		$newStatus = 'C';

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['p24_order_id'],
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
	
	private function isValidIPN($data)
	{
		$crc = md5($data['p24_session_id'] .
				'|'	. $data['p24_order_id'] .
				'|' . $data['p24_kwota'] .
				'|' . trim($this->params->get('crc_key','')));
		return $data['p24_crc'] == $crc;
	}
}