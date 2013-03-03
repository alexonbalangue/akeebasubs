<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentEwayrapid3 extends plgAkpaymentAbstract
{
	private $ewayService = null;
	
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'ewayrapid3',
			'ppKey'			=> 'PLG_AKPAYMENT_EWAYRAPID3_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/eway.gif',
		));
		
		parent::__construct($subject, $config);
		
		// Load libraray and initialize settings
		require_once dirname(__FILE__).'/ewayrapid3/library/Rapid3.0.php';
		$service = new RapidAPI();
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			$soapURL = 'https://api.sandbox.ewaypayments.com/Soap.asmx?WSDL';
			$key = trim($this->params->get('sb_key',''));
			$password = trim($this->params->get('sb_password',''));
		} else {
			$soapURL = 'https://api.ewaypayments.com/Soap.asmx?WSDL';
			$key = trim($this->params->get('key',''));
			$password = trim($this->params->get('password',''));
		}
		$service->APIConfig['Payment.Username'] = $key;
		$service->APIConfig['Payment.Password'] = $password;
		$service->APIConfig['PaymentService.Soap'] = $soapURL;
		$service->APIConfig['Request:Method'] = 'SOAP';
		$this->ewayService = $service;
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
		// Check that this is the requested payment plugin
		if($paymentmethod != $this->ppName) return false;
		
		// Split the name in first and last name
		$nameParts = explode(' ', $user->name, 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		
		// Fetch our extended user information
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		// Customer
		$request = new CreateAccessCodeRequest();
		$request->Customer->Reference = $subscription->akeebasubs_subscription_id;
		$request->Customer->Title = 'Mr.';
		$request->Customer->FirstName = $firstName;
		$request->Customer->LastName = $lastName;
		if($kuser->isbusiness) {
			$request->Customer->CompanyName = trim($kuser->businessname);
		}
		$request->Customer->Street1 = trim($kuser->address1);
		$request->Customer->City = trim($kuser->city);
		if(!empty($kuser->state)) {
			$request->Customer->State = trim($kuser->state);
		}
		$request->Customer->PostalCode = trim($kuser->zip);
		$request->Customer->Country = strtolower(trim($kuser->country));
		$request->Customer->Email = trim($user->email);
		
		// Item/product
		$item = new LineItem();   
		$item->SKU = $level->akeebasubs_level_id;
		$item->Description = $level->title;
		$request->Items->LineItem[0] = $item;

		// Payment
		$request->Payment->TotalAmount = (int)($subscription->gross_amount * 100);
		$request->Payment->InvoiceNumber = $subscription->akeebasubs_subscription_id;
		$request->Payment->InvoiceDescription = $level->title . ' #' . $subscription->akeebasubs_subscription_id;
		$request->Payment->InvoiceReference = $subscription->akeebasubs_subscription_id;
		$request->Payment->CurrencyCode = strtoupper(AkeebasubsHelperCparams::getParam('currency','AUD'));
		// Url to the page for getting the result with an AccessCode
		$request->RedirectUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=ewayrapid3&sid=' . $subscription->akeebasubs_subscription_id;
		// Method for this request. e.g. ProcessPayment, Create TokenCustomer, Update TokenCustomer & TokenPayment
		$request->Method = 'ProcessPayment';

		try {
			// Call RapidAPI
			$result = $this->ewayService->CreateAccessCode($request);
		} catch(Exception $e) {
			JError::raiseError(500, 'You have an error in your eWay setup: ' . $e->getMessage());
			return false;
		}
		if(isset($result->Errors)) {
			$errorMsg = '';
			foreach(explode(',', $result->Errors) as $e) {
				$errorMsg .= $this->ewayService->APIConfig[$e] . ', ';
			}
			$errorMsg = substr($errorMsg, 0, -2);
			JError::raiseError(500, 'You have an error in your eWay setup: ' . $errorMsg);
			return false;
		}

		@ob_start();
		include dirname(__FILE__).'/ewayrapid3/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		$isValid = true;
		
		// Load the relevant subscription row
		$id = $data['sid'];
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
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'The subscription ID is invalid';
		
		if($isValid) {
			// Build request for getting the result with the access code
			$request = new GetAccessCodeResultRequest();
			$request->AccessCode = $data['AccessCode'];
			// Call RapidAPI to get the result
			$result = $this->ewayService->GetAccessCodeResult($request);
			// Check for error
			if(isset($result->Errors)) {
				$errorMsg = '';
				foreach(explode(',', $result->Errors) as $e) {
					$errorMsg .= $this->ewayService->APIConfig[$e] . ', ';
				}
				$errorMsg = substr($errorMsg, 0, -2);
				$isValid = false;
				$data['akeebasubs_failure_reason'] = $errorMsg;
			}
		}
		
		// Check response message
		if($isValid) {
			$errorMsg = '';
			foreach(explode(',', $result->ResponseMessage) as $m) {
				if($m != 'A2000') $isValid = false;
				$errorMsg .= $this->ewayService->APIConfig[$m] . ', ';
			}
			if(!$isValid) {
				$errorMsg = substr($errorMsg, 0, -2);
				$data['akeebasubs_failure_reason'] = $errorMsg;	
			}
		}
        
		// Check invoice reference
		if($isValid) {
			if($result->InvoiceReference != $subscription->akeebasubs_subscription_id) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invoice reference is not correct.";
			}
		}
        
		// Check that transaction has not been previously processed
		if($isValid) {
			if($result->TransactionID == $subscription->processor_key) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not processe this transaction twice";
			}
		}
		
		// Check that the amount is correct
		if($isValid && !is_null($subscription)) {
			$mc_gross = $result->TotalAmount;
			$gross = (int)($subscription->gross_amount * 100);
			// Important: NEVER, EVER compare two floating point values for equality.
			$isValid = ($gross - $mc_gross) < 0.01;
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
		
		// Payment status
		if($result->TransactionStatus) {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}
		
		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id'	=> $id,
			'processor_key'					=> $result->TransactionID,
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
	
	public function selectMonth()
	{
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 1; $i <= 12; $i++) {
			$m = sprintf('%02u', $i);
			$options[] = JHTML::_('select.option',$m,$m);
		}
		
		return JHTML::_('select.genericlist', $options, 'EWAY_CARDEXPIRYMONTH', 'class="input-small"', 'value', 'text', '', 'EWAY_CARDEXPIRYMONTH');
	}
	
	public function selectYear()
	{
		$year = gmdate('Y');
		
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 0; $i <= 10; $i++) {
			$y = sprintf('%04u', $i+$year);
			$options[] = JHTML::_('select.option',$y,$y);
		}
		
		return JHTML::_('select.genericlist', $options, 'EWAY_CARDEXPIRYYEAR', 'class="input-small"', 'value', 'text', '', 'EWAY_CARDEXPIRYYEAR');
	}
}