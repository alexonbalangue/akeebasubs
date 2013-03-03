<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentScnetintegrated extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'scnetintegrated',
			'ppKey'			=> 'PLG_AKPAYMENT_SCNETINTEGRATED_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/scnet.jpg'
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
		
		// Force HTTPS
		$app = JFactory::getApplication();
        $uri = JFactory::getURI();
		if ($uri->isSSL() == false) {
			$uri->setScheme('https');
			$app->redirect($uri->toString());
			return $app->close();
		}
		
		$data = (object)array(
			'callback'	=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=scnetintegrated',
			'arg0'		=> $this->getMerchantID(),
			'arg1'		=> $this->getMerchantPassword(),
			'arg5'		=> sprintf('%.2f',$subscription->gross_amount), //Amount
			'arg7'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','AUD')), //Currency
			'arg8'		=> $subscription->akeebasubs_subscription_id, //InvoiceId
			'arg9'		=> trim($this->params->get('adminemail','')),
			'arg10'		=> trim($user->email),
			'arg12'		=> $level->title,
			'arg13'		=> trim($user->name),
			'arg14'		=> trim($kuser->address1),
			'arg16'		=> trim($kuser->zip),
			'arg22'		=> trim($kuser->city),
			'arg23'		=> AkeebasubsHelperSelect::decodeCountry(trim($kuser->country))
		);

		@ob_start();
		include dirname(__FILE__).'/scnetintegrated/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		$isValid = true;
		
		// Get subscription
		if($isValid) {
			$id = $data['arg8'];
			$subscription = null;
			if($id > 0) {
				$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->setId($id)
					->getItem();
				$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
					->setId($subscription->akeebasubs_level_id)
					->getItem();
				$error_url = 'index.php?option='.JRequest::getCmd('option').
					'&view=level&slug='.$level->slug.
					'&layout='.JRequest::getCmd('layout','default');
				$error_url = JRoute::_($error_url,false);
				if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id) ) {
					$subscription = null;
					$isValid = false;
				}
			} else {
				$isValid = false;
			}
		}
		
		// Check credit card number
		if($isValid && empty($data['arg2'])) {
			JFactory::getApplication()->redirect($error_url,'You need to enter your credit card number.','error');
			return false;
		}
		
		// Check expiration date
		if($isValid && $data['arg3'] == '0') {
			JFactory::getApplication()->redirect($error_url,'You need to select the expiration date of your credit card.','error');
			return false;
		}
		
		// Perform the transaction
		if($isValid) {
			try {
				$soap = new SoapClient($this->getPaymentURL().'?WSDL',array('location' => $this->getPaymentURL()));
				$response = $soap->performTransaction($data);
				if($this->params->get('antifraud',1)) {
					$isValid = $this->isValidIPN($response, $data);
					if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';	
				}
			}catch(Exception $e) {
				JFactory::getApplication()->redirect($error_url,$e->getMessage(),'error');
				return false;
			}	
		}
		
		if($isValid && strtoupper($response->return->summaryResponseCode) == 'ERROR') {
			JFactory::getApplication()->redirect($error_url,$response->return->responseText,'error');
			return false;
		}
        
		// Check that bank_id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $response->return->orderNumber && $subscription->state == 'C') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same order twice";
			}
		}
		
		// Check that amount_gross is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($response->return->amount);
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
		$this->logIPN($response->return, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) {
			JFactory::getApplication()->redirect($error_url,$data['akeebasubs_failure_reason'],'error');
			return false;
		}
		
		// Payment status
		if(strtoupper($response->return->summaryResponseCode) == 'APPROVED') {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $response->return->orderNumber,
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
        
		if($newStatus == 'C') {
			// Redirect the user to the "thank you" page if payment is complete
			$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$subscription->slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
			$app->redirect($thankyouUrl);
		} else {
			$app->redirect($error_url,$response->return->responseText,'error');
		}
		return true;
	}
	
	
	/**
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://www.scnet.com.au/ipayby/ipaybyws';
		} else {
			return trim($this->params->get('url',''));
		}
	}
	
	private function getQueryURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://www.scnet.com.au/ipayby/ipaybyqueryws';
		} else {
			return 'https://secure.ipayby.com.au/ipayby/ipaybyqueryws';
		}
	}

	
	/**
	 * Gets the SCNet Merchant ID
	 */
	private function getMerchantID()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'SCNet_Cert6';
		} else {
			return trim($this->params->get('merchant_id',''));
		}
	}
	
	/**
	 * Gets the SCNet Merchant ID
	 */
	private function getMerchantPassword()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'testSCNet';
		} else {
			return trim($this->params->get('merchant_pass',''));
		}
	}
	
	private function isValidIPN($response, $data)
	{
		try {
			$queryData = (object)array(
				'arg0'		=> $this->getMerchantID(),
				'arg1'		=> $this->getMerchantPassword(),
				'arg2'		=> $response->return->orderNumber
			);
			$soap = new SoapClient($this->getQueryURL().'?WSDL',array('location' => $this->getQueryURL()));
			$queryResponse = $soap->performQuery($queryData);
			// Check credit card number
			$ccNr = $queryResponse->return->cardNumber;
			preg_match('/^([^#]+)[#]+([^#]+)$/', $ccNr, $ccMatches);
			if($ccMatches[1] != substr($data['arg2'], 0, strlen($ccMatches[1]))) {
				return false;
			}
			if($ccMatches[2] != substr($data['arg2'], -strlen($ccMatches[2]))) {
				return false;
			}
			// Check expiry data
			if($queryResponse->return->expiryDate != $data['arg3']) {
				return false;
			}
			// Check currency
			if(strtoupper($queryResponse->return->currency) != strtoupper(AkeebasubsHelperCparams::getParam('currency','AUD'))) {
				return false;
			}
			// Check amount
			if($queryResponse->return->amount != $response->return->amount) {
				return false;
			}
			// Check summaryResponseCode
			if($queryResponse->return->summaryResponseCode != $response->return->summaryResponseCode) {
				return false;
			}
			// Check responseCode
			if(isset($response->return->responseCode) && $queryResponse->return->responseCode != $response->return->responseCode) {
				return false;
			}
		}catch(Exception $e) {
			return false;
		}
		return true;
	}
	
	private function selectExpirationDate()
	{
		$year = gmdate('Y');
		
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 0; $i <= 10; $i++) {
			$y = sprintf('%04u', $i+$year);
			for($j = 1; $j <= 12; $j++) {
				$m = sprintf('%02u', $j);
				$options[] = JHTML::_('select.option', ($m.substr($y, -2)), ($m.'/'.$y));
			}
		}
		
		return JHTML::_('select.genericlist', $options, 'arg3', 'class="input-medium"', 'value', 'text', '', 'arg3');
	}
}