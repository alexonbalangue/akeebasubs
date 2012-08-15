<?php
/**
 * @package		akeebasubs
 * @copyright		Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentClickandBuy extends JPlugin
{
	private $ppName = 'clickandbuy';
	private $ppKey = 'PLG_AKPAYMENT_CLICKANDBUY_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		require_once dirname(__FILE__).'/clickandbuy/library/nusoap.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_clickandbuy', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_clickandbuy', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_clickandbuy', JPATH_ADMINISTRATOR, null, true);
	}

	public function onAKPaymentGetIdentity()
	{
		$title = $this->params->get('title','');
		if(empty($title)) $title = JText::_($this->ppKey);
		$ret = array(
			'name'		=> $this->ppName,
			'title'		=> $title
		);
		$ret['image'] = trim($this->params->get('ppimage',''));
		if(empty($ret['image'])) {
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/clickandbuy.gif';
		}
		return (object)$ret;
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
		
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		$grossAmount = sprintf('%.2f', $subscription->gross_amount);
		$currency = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
		$timestamp = gmdate("YmdHis");	
		$token = $timestamp . '::' . strtoupper(sha1(
			trim($this->params->get('project_id','')) .
				'::' . trim($this->params->get('secret_key','')) .
				'::' . $timestamp
				));
		
		$billingAddress = array(
			'address'	=> array (
				'street'	=> trim($kuser->address1),
				'zip'		=> trim($kuser->zip),
				'city'		=> trim($kuser->city),
				'country'	=> strtoupper(trim($kuser->country))
			)
		);
		
		if(! empty($kuser->state)) {
			$billingAddress['address']['state'] = trim($kuser->state);
		}
		
		if($kuser->isbusiness) {
			$billingType = 'company';
			$billingAddress->name = trim($kuser->businessname);
		} else {
			$billingType = 'consumer';
			$billingAddress->firstName = $firstName;
			$billingAddress->lastName = $lastName;
		}
		
		$pRequestData = array(
			'authentication'	=> array(
				'merchantID'	=> trim($this->params->get('merchant_id','')),
				'projectID'		=> trim($this->params->get('project_id','')),
				'token'			=> $token						
			),
			'details'			=> array(
				'amount'			=> array(
					'amount'	=> $grossAmount,
					'currency'	=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
				),
				'successURL'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
				'failureURL'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
				'externalID'		=> $subscription->akeebasubs_subscription_id,
				'consumerLanguage'	=> 'EN', // @TODO
				'orderDetails'		=> array(
					'text'		=> $level->title . ' - [' . $user->username . ']',
					'itemList'	=> array(
						new soapval('item', false, array(
							'itemType'		=> 'TEXT',
							'description'	=> $level->title
							)
						),
						new soapval('item', false, array(
								'itemType'		=> 'ITEM',
								'description'	=> $level->title,
								'quantity'		=> 1,
								new soapval('unitPrice', false, array(
									'amount'	=> $grossAmount,
									'currency'	=> $currency
								)),
								new soapval('totalPrice', false, array(
									'amount'	=> $grossAmount,
									'currency'	=> $currency
								))
							)
						),	
						new soapval('item', false, array(
								'itemType'		=> 'SUBTOTAL',
								'description'	=> $level->title,
								new soapval('totalPrice', false, array(
									'amount'	=> $grossAmount,
									'currency'	=> $currency
								))
							)
						),
						new soapval('item', false, array(
								'itemType'		=> 'VAT',
								'description'	=> $level->title,
								new soapval('totalPrice', false, array(
									'amount'	=> $grossAmount, // @TODO vat
									'currency'	=> $currency
								))
							)
						),
						new soapval('item', false, array(
								'itemType'		=> 'TOTAL',
								'description'	=> $level->title,
								new soapval('totalPrice', false, array(
									'amount'	=> $grossAmount,
									'currency'	=> $currency
								))
							)
						)
					)
				),
				'billing'			=> array(
					$billingType	=> $billingAddress
				)
			)
		);
		
		$soap = new nusoap_client('https://api.clickandbuy.com/webservices/soap/pay_1_1_0');
		$soap->soap_defencoding = "UTF-8";
		$soap->call(
				'payRequest_Request',
				$pRequestData,
				"http://api.clickandbuy.com/webservices/pay_1_1_0/\" xmlns=\"http://api.clickandbuy.com/webservices/pay_1_1_0/",
				'http://api.clickandbuy.com/webservices/pay_1_1_0/',
				false,
				null,
				"rpc",
				"literal"
				);
		
		// This is for debbuging while developing the plugin
		/*if ($soap->fault) {
			$nusoapResult['error_type'] = 'fault';		
			$nusoapResult['faultcode'] = $soap->faultcode;
			$nusoapResult['faultstring'] = $soap->faultstring;		
			$nusoapResult['faultdetail'] = $soap->faultdetail;						

		} elseif($soap->getError()) {
			$nusoapResult['error_type'] = 'error';
			$nusoapResult['error'] = $soap->getError();		
		} else {
			$success = true;
		}

		$nusoapResult['success'] = $success;
		$nusoapResult['values'] = $result;
		$nusoapResult['req_name'] = $reqName;
		$nusoapResult['request'] = $soap->request;
		$nusoapResult['response'] = $soap->response;*/
		
		// This is a test with SoapClient as an alternative to the nusoap_client above:
		/*$soap1 = new SoapClient('https://api.clickandbuy.com/webservices/soap/pay_1_1_0', array('encoding'=>'UTF-8'));
		$pRequest = $soap1->payRequest_Request(
				array(
					'authentication'	=> array(
						'merchantID'	=> trim($this->params->get('merchant_id','')),
						'projectID'		=> trim($this->params->get('project_id','')),
						'token'			=> $token						
					),
					'details'			=> array(
						'amount'		=> array(
							'amount'	=> $grossAmount,
							'currency'	=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'))
						),
						'orderDetails'	=> array(
							'text'		=> $level->title . ' - [' . $user->username . ']',
						),
					'successURL'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
					'failureURL'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
					'externalID'		=> $subscription->akeebasubs_subscription_id
					)
				)
			);*/
		
		// @TODO Set the data for the form

		@ob_start();
		include dirname(__FILE__).'/clickandbuy/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		jimport('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// This response is a XML document
		$dataDoc = new DOMDocument();
		$dataDoc->loadXML($data['sRequest']);
        
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($dataDoc);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';
		
		// Check if response code is OK
		if($isValid) {
			$isValid = $this->getDataVal($dataDoc, 'responseCode') == 'OK';
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'ResponseCode is not OK.';
		}
		
		// Check if merchant_id is the same
		if($isValid) {
			$mId = $this->getDataVal($dataDoc, 'merchant_id');
			$isValid = $mId == trim($this->params->get('merchant_id',''));
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The merchant_id is not correct: ' . $mId;
		}
		
		// Check if currency is the same
		if($isValid) {
			$cur = $this->getDataVal($dataDoc, 'currency');
			$isValid = strnatcasecmp($cur, AkeebasubsHelperCparams::getParam('currency','EUR')) == 0;
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The currency is not correct: ' . $cur;
		}

		// Load the relevant subscription row
		if($isValid) {
			$id = $this->getDataVal($dataDoc, 'txt2');
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The ORDER_NUMBER is invalid';
		}
        
		// Check that cashU_trnID has not been previously processed
		if($isValid && !is_null($subscription)) {
			$key = $this->getDataVal($dataDoc, 'cashU_trnID');
			if($subscription->processor_key == $key) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same cashU_trnID twice";
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($this->getDataVal($dataDoc, 'amount'));
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
				'akeebasubs_subscription_id'	=> (int)$id,
				'processor_key'					=> $key,
				'state'							=> $newStatus,
				'enabled'						=> 0
		);
		jimport('joomla.utilities.date');
		if($newStatus == 'C') {
			// Fix the starting date if the payment was accepted after the subscription's start date. This
			// works around the case where someone pays by e-Check on January 1st and the check is cleared
			// on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
			// the user would have paid us and we'd never given him a subscription!
			$jNow = new JDate();
			$jStart = new JDate($subscription->publish_up);
			$jEnd = new JDate($subscription->publish_down);
			$now = $jNow->toUnix();
			$start = $jStart->toUnix();
			$end = $jEnd->toUnix();
			
			if($start < $now) {
				$duration = $end - $start;
				$start = $now;
				$end = $start + $duration;
				$jStart = new JDate($start);
				$jEnd = new JDate($end);
			}

			$updates['publish_up'] = $jStart->toSql();
			$updates['publish_down'] = $jEnd->toSql();
			$updates['enabled'] = 1;

		}
		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array(
			$subscription
		));
        
		return true;
	}
	
	private function getDataVal($xmlDoc, $param) {
		$elem = $xmlDoc->getElementsByTagName($param)->item(0);
		return $elem->nodeValue;
	}
    
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($dataDoc)
	{
		$hashCode = md5(
				strtolower(trim($this->params->get('merchant_id',''))) .
				':' . $this->getDataVal($dataDoc, 'cashU_trnID') .
				':' . trim($this->params->get('merchant_keyword',''))
				);
		return $hashCode == $this->getDataVal($dataDoc, 'cashUToken');
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}
		$logFile = $logpath.'/akpayment_clickandbuy_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_clickandbuy_ipn-1.php';
				if(JFile::exists($altLog)) {
					JFile::delete($altLog);
				}
				JFile::copy($logFile, $altLog);
				JFile::delete($logFile);
				$dummy = "<?php die(); ?>\n";
				JFile::write($logFile, $dummy);
			}
		}
		$logData = JFile::read($logFile);
		if($logData === false) $logData = '';
		$logData .= "\n" . str_repeat('-', 80);
		$logData .= $isValid ? 'VALID CLICKANDBUY IPN' : 'INVALID CLICKANDBUY IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}