<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2014 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentSaferpay extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'saferpay',
			'ppKey'			=> 'PLG_AKPAYMENT_SAFERPAY_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/saferpay_logo.png'
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
		
		$error_url = 'index.php?option='.JRequest::getCmd('option').
			'&view=level&slug='.$level->slug.
			'&layout='.JRequest::getCmd('layout','default');
		$error_url = JRoute::_($error_url,false);

		// 1/2 Request payment URL
		$requestData = (object)array(
			'ACCOUNTID'		=> trim($this->params->get('account_id','')),
			'AMOUNT'		=> (int)($subscription->gross_amount * 100),
			'CURRENCY'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'ORDERID'		=> $subscription->akeebasubs_subscription_id,
			'DESCRIPTION'	=> htmlentities($level->title),
			'DELIVERY'		=> 'no',
			'CCNAME'		=> 'yes',
			'CCCVC'			=> 'yes',
			'NOTIFYURL'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=saferpay&mode=success&sid=' . $subscription->akeebasubs_subscription_id,
			'FAILLINK'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=saferpay&mode=fail&sid=' . $subscription->akeebasubs_subscription_id,
			'SUCCESSLINK'	=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'BACKLINK'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id))
		);
		
		$response = '';
		try {
			$requestQuery = http_build_query($requestData, '', '&');
			$requestOptions = array('http' => array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $requestQuery)
				);
			$requestContext = stream_context_create($requestOptions);

			$response = file_get_contents('https://www.saferpay.com/hosting/CreatePayinit.asp', false, $requestContext);		
		}catch (Exception $e) {
			JFactory::getApplication()->redirect($error_url, $e->getMessage(), 'error');
			return false;
		}
		
		// Check response
		if(strtolower(substr($response, 0, 25 )) != 'https://www.saferpay.com/') {
			JFactory::getApplication()->redirect($error_url, 'The setup on this server does not work correctly for outgoig SSL-calls.', 'error');
			return false;
		}
		
		// 2/2 Build the payment URL
		$responseParams = array();
		$responseParts = parse_url($response);
		parse_str($responseParts["query"], $responseParams);
		
		$paymentData = (object)array(
			'URL'		=> $responseParts["scheme"] . "://" . $responseParts["host"] . $responseParts["path"],
			'DATA'		=> $responseParams["DATA"],
			'SIGNATURE'	=> $responseParams["SIGNATURE"]
		);

		@ob_start();
		include dirname(__FILE__).'/saferpay/form.php';
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
		if($isValid) {
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'There is no valid subscription ID';
		}
		
		// Fail mode
		if($isValid && $data['mode'] == 'fail') {
			$updates = array(
					'akeebasubs_subscription_id'	=> $id,
					'processor_key'					=> md5(microtime(false)),
					'state'							=> 'X',
					'enabled'						=> 0
			);
			$subscription->save($updates);
			return true;
		}
		
		// Check mode
		if($isValid && $data['mode'] != 'success') {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = "Invalid mode " . $data['mode'];
		}
		
		// Check xml data
		if($isValid) {
			$xmlData = new DOMDocument();
			$xmlData->loadXML($data['DATA']);
			
			if (!$xmlData->documentElement->hasAttribute("ACCOUNTID") 
				|| !$xmlData->documentElement->hasAttribute("AMOUNT") 
				|| !$xmlData->documentElement->hasAttribute("CURRENCY") 
				|| !$xmlData->documentElement->hasAttribute("ORDERID")) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "XML data doesn't contain the required parameters";
			}
		}
		
		// Verify the data
		if($isValid) {
			$verifyData = (object)array(
				'ACCOUNTID'	=> trim($this->params->get('account_id','')),
				'DATA'		=> $data['DATA'],
				'SIGNATURE'	=> $data['SIGNATURE']
			);

			$verifyResponse = '';
			try {
				$verifyQuery = http_build_query($verifyData, '', '&');
				$verifyOptions = array('http' => array(
					'method'  => 'POST',
					'header'  => 'Content-type: application/x-www-form-urlencoded',
					'content' => $verifyQuery)
					);
				$verifyContext = stream_context_create($verifyOptions);
				$verifyResponse = file_get_contents('https://www.saferpay.com/hosting/VerifyPayConfirm.asp', false, $verifyContext);		
			}catch (Exception $e) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Verification request faild";
			}
		}
		
		// Check verification response
		if($isValid) {
			if(substr($verifyResponse, 0, 3) != 'OK:') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Verification request not ok";
			}
			$verifyResponseParams = array();
			parse_str(substr($verifyResponse, 3), $verifyResponseParams);
		}
		
		// Check account ID
		if($isValid) {
			if($xmlData->documentElement->getAttribute("ACCOUNTID") != trim($this->params->get('account_id',''))) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Account ID is not correct";
			}
		}
		
		// Check subscription ID
		if($isValid) {
			if($xmlData->documentElement->getAttribute("ORDERID") != $subscription->akeebasubs_subscription_id) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Subscription ID is not correct";
			}
		}
		
		// Check currency
		if($isValid) {
			if(strtoupper($xmlData->documentElement->getAttribute("CURRENCY")) != strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'))) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Currency is not correct";
			}
		}
		
		// Check amount
		if($isValid) {
			if($isValid && !is_null($subscription)) {
				$mc_gross = $xmlData->documentElement->getAttribute("AMOUNT");
				$gross = (int)($subscription->gross_amount * 100);
				if($mc_gross > 0) {
					// A positive value means "payment". The prices MUST match!
					// Important: NEVER, EVER compare two floating point values for equality.
					$isValid = ($gross - $mc_gross) < 0.01;
				}
				if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}
		
		// Complete the payment
		if($isValid) {
			$completeData = array();
			$accountId = trim($this->params->get('account_id',''));
			$completeData["ACCOUNTID"] = $accountId;
			$completeData["ID"] = $verifyResponseParams["ID"];
			if (substr($accountId,0 ,6 ) == "99867-") {
				$completeData["spPassword"] = "XAjc3Kna";	
			}
			
			$completeResponse = "";
			try {
				$completeQuery = http_build_query($completeData, '', '&');
				$completeOptions = array('http' => array(
					'method'  => 'POST',
					'header'  => 'Content-type: application/x-www-form-urlencoded',
					'content' => $completeQuery));
				$completeContext  = stream_context_create($completeOptions);
				$completeResponse = file_get_contents('https://www.saferpay.com/hosting/PayCompleteV2.asp', false, $completeContext);		
			}
			catch (Exception $ex) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Complete request faild";
			}
		}
		
		// Check response of completed payment
		if($isValid) {
			if(substr($completeResponse, 0, 3) != 'OK:') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Complete request not ok";
			}
		}
		
		// Check xml response of completed payment
		if($isValid) {
			$xmlCompleteData = new DOMDocument();
			$xmlCompleteData->loadXml(substr($completeResponse, 3));
			if ($xmlCompleteData->documentElement->tagName != "IDP"
				|| !$xmlCompleteData->documentElement->hasAttribute("RESULT")
				|| $xmlCompleteData->documentElement->getAttribute("RESULT") != "0") {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Complete response does not contain the required parameters";
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;
		
		// Is payment successful?
		if($xmlCompleteData->documentElement->getAttribute("RESULT") == "0") {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $verifyResponseParams["ID"],
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
}