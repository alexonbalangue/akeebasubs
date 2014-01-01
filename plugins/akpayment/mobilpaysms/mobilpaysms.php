<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2014 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentMobilpaysms extends plgAkpaymentAbstract
{
	private $mpMapping = array();

	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'mobilpaysms',
			'ppKey'			=> 'PLG_AKPAYMENT_MOBILPAYSMS_TITLE',
			'ppImage'		=> JURI::root().'plugins/akpayment/mobilpaysms/mobilpaysms/logo.gif'
		));

		parent::__construct($subject, $config);

		// Load level to alloPass mapping from plugin parameters
		$rawMpMapping = $this->params->get('mpmapping','');
		$this->mpMapping = $this->parseMpMatching($rawMpMapping);
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

		// MobilpaySMS Info for this level
		$mobilpay = $this->mpMapping[$level->akeebasubs_level_id];
		if(empty($mobilpay)) {
			return JError::raiseError(500, 'Cannot proceed with the payment. No Mobilpay information are definied for this subscription.');
		}

		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->setId($subscription->akeebasubs_level_id)
			->getItem()
			->slug;

		$rootURL    = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/'))
		{
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		// Let's get all the data and process them using MobiPro API
		require_once 'mobilpaysms/library/Request/Abstract.php';
		require_once 'mobilpaysms/library/Request/Sms.php';
		require_once 'mobilpaysms/library/Invoice.php';

		$x509FilePath = JPATH_ROOT.'/plugins/akpayment/mobilpaysms/mobilpaysms/private/public.cer';

		if(!file_exists($x509FilePath))
		{
			if(version_compare(JVERSION, '3.0', 'ge'))
			{
				throw new Exception('Public certificate is needed in order to elaborate requested data', 500);
			}
			else
			{
				JError::raiseError(500, 'Public certificate is needed in order to elaborate requested data');
			}

			return false;
		}

		srand((double) microtime() * 1000000);

		try{
			$objPmReqSms 						= new Mobilpay_Payment_Request_Sms();
			$objPmReqSms->signature 			= $this->params->get('signature', '');
			$objPmReqSms->service 		        = $mobilpay['serviceId'];
			$objPmReqSms->orderId 				= md5(uniqid(rand()));
			$objPmReqSms->returnUrl 			= $rootURL.JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
			$objPmReqSms->confirmUrl 			= $rootURL.JRoute::_('index.php?option=com_akeebasubs&view=callback&paymentmethod=mobilpaysms', false);

			$objPmReqSms->invoice              = new Mobilpay_Payment_Invoice();
			// Only RON currency is supported by this payment method...
			$objPmReqSms->invoice->currency	    = 'RON';
			$objPmReqSms->invoice->amount		= $subscription->net_amount;
			$objPmReqSms->invoice->details		= $level->title . ' - [ ' . $user->username . ' ]';
			$objPmReqSms->params['custom']		= $subscription->akeebasubs_subscription_id;

			$objPmReqSms->encrypt($x509FilePath);
		}
		catch(Exception $e)
		{
			return false;
		}

		$data = (object)array(
			'url'			=> $this->getPaymentURL()
		);

		@ob_start();
		include dirname(__FILE__).'/mobilpaysms/form.php';
		$html = @ob_get_clean();

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;

		$transInfo = $this->processTransaction($data);
		$isValid   = $transInfo['valid'];

		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Mobilpay reports transaction as invalid';

		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('custom', $transInfo) ? (int)$transInfo['custom'] : -1;
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("custom" field) is invalid';
		}

		$mc_gross = floatval($transInfo['amount']);
		$gross    = $subscription->gross_amount;
		if($mc_gross > 0) {
			// A positive value means "payment". The prices MUST match!
			// Important: NEVER, EVER compare two floating point values for equality.
			$isValid = ($gross - $mc_gross) < 0.01;
			if(!$isValid) $data['akeebasubs_failure_reason'] = "The amounts don't match";
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid)
		{
			//Let's inform MobilPro we've done
			$this->echoXML($transInfo['xml']);
			return false;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id'	=> $id,
			'processor_key'					=> $transInfo['orderId'],
			'state'							=> $transInfo['state'],
			'enabled'						=> 0
		);
		// On recurring payments also store the subscription ID
		if(array_key_exists('subscr_id', $data)) {
			$subscr_id = $data['subscr_id'];
			$params = $subscription->params;
			if(!is_array($params)) {
				$params = json_decode($params, true);
			}
			if(is_null($params) || empty($params)) {
				$params = array();
			}
			$params['recurring_id'] = $subscr_id;
			$updates['params'] = $params;
		}

		JLoader::import('joomla.utilities.date');
		if($transInfo['state'] == 'C')
		{
			$this->fixDates($subscription, $updates);
		}

		// Save the changes
		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array($subscription));

		// Let's echo the result XML.
		// PLEASE NOTE!!! THe execution stops here, since we have to shutdown Joomla (JFactory::getApplication()->close())
		$this->echoXML($transInfo['xml']);

		return true;
	}

	/**
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'http://sandboxsecure.mobilpay.ro';
		} else {
			return 'https://secure.mobilpay.ro';
		}
	}

	private function processTransaction($data)
	{
		// Let's load the API classes
		require_once 'mobilpaysms/library/Request/Abstract.php';
		require_once 'mobilpaysms/library/Request/Sms.php';
		require_once 'mobilpaysms/library/Request/Notify.php';
		require_once 'mobilpaysms/library/Invoice.php';
		require_once 'mobilpaysms/library/Address.php';

		$return 		= array();

		$errorCode 		= 0;
		$errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
		$message		= '';

		if(isset($data['env_key']) && isset($data['data']))
		{
			$privateKeyFilePath = JPATH_ROOT.'/plugins/akpayment/mobilpaysms/mobilpaysms/private/private.key';

			if(!file_exists($privateKeyFilePath))
			{
				if(version_compare(JVERSION, '3.0', 'ge'))
				{
					throw new Exception('Private key is needed in order to elaborate requested data', 500);
				}
				else
				{
					JError::raiseError(500, 'Private key is needed in order to elaborate requested data');
				}

				return false;
			}

			try
			{
				$objPmReq = Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($data['env_key'], $data['data'], $privateKeyFilePath);

				switch($objPmReq->objPmNotify->action)
				{
					case 'confirmed':
						$return['state'] = 'C';
						$message = $objPmReq->objPmNotify->getCrc();
						break;
					case 'confirmed_pending':
					case 'paid_pending':
					case 'paid':
						$return['state'] = 'P';
						$message = $objPmReq->objPmNotify->getCrc();
						break;
					case 'canceled':
					case 'credit':
					default:
						$return['state'] = 'X';
						$errorType	= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
						$errorCode 	= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_ACTION;
						$message 	= 'mobilpay_refference_action paramaters is invalid';
						break;
				}

				$return['amount']  = $objPmReq->objPmNotify->processedAmount;
				$return['orderId'] = $objPmReq->orderId;
				$return['custom']  = $objPmReq->params['custom'];
				$return['valid']   = true;
			}
			catch(Exception $e)
			{
				$errorType 	= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY;
				$errorCode	= $e->getCode();
				$message 	= $e->getMessage();

				$return['valid'] = false;
			}
		}
		else
		{
			$errorType 	= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
			$errorCode	= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS;
			$message 	= 'mobilpay.ro posted invalid parameters';

			$return['valid'] = false;
		}

		$return['xml'] = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
		if($errorCode == 0)
		{
			$return['xml'] .= "<crc>{$message}</crc>";
		}
		else
		{
			$return['xml'] .= "<crc error_type=\"{$errorType}\" error_code=\"{$errorCode}\">{$message}</crc>";
		}

		return $return;
	}

	private function echoXML($string)
	{
		header('Content-type: application/xml');
		echo $string;
		JFactory::getApplication()->close();
	}

	private function debug($string)
	{
		$handle = fopen(JPATH_ROOT.'/log.txt', 'a+');
		fwrite($handle, date('Y-m-d H:i:s').' --- '.$string.PHP_EOL);
		fclose($handle);
	}

	private function parseMpMatching($rawData)
	{
		if(empty($rawData)) return array();

		$ret = array();

		// Just in case something funky happened...
		$rawData = str_replace("\\n", "\n", $rawData);
		$rawData = str_replace("\r", "\n", $rawData);
		$rawData = str_replace("\n\n", "\n", $rawData);

		$lines = explode("\n", $rawData);

		foreach($lines as $line) {
			$line = trim($line);
			$parts = explode('=', $line, 2);
			if(count($parts) != 2) continue;

			$level = trim($parts[0]);
			$levelId = $this->ASLevelToId($level);
			if($levelId < 0) continue;

			$serviceId = $parts[1];
			if (empty($serviceId))
			{
				continue;
			}

			$pricePoint = array(
				'serviceId'	=> $serviceId
			);

			$ret[$levelId] = $pricePoint;
		}

		return $ret;
	}

	/**
	 * Converts an Akeeba Subscriptions level to a numeric ID
	 *
	 * @param $title string The level's name to be converted to an ID
	 *
	 * @return int The subscription level's ID or -1 if no match is found
	 */
	private function ASLevelToId($title)
	{
		static $levels = null;

		// Don't process invalid titles
		if(empty($title)) return -1;

		// Fetch a list of subscription levels if we haven't done so already
		if(is_null($levels)) {
			$levels = array();
			$list = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->getList();
			if(count($list)) foreach($list as $level) {
				$thisTitle = strtoupper($level->title);
				$levels[$thisTitle] = $level->akeebasubs_level_id;
			}
		}

		$title = strtoupper($title);
		if(array_key_exists($title, $levels)) {
			// Mapping found
			return($levels[$title]);
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}
}