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
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/ClickandBuy_2010_logo.png';
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
		
		// 1. Build the payment request
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		try {
			$lang = strtolower(substr(JFactory::getLanguage()->getTag(), 0, 2));
			if($lang == 'de') $language = 'DE';
			else if($lang == 'fr') $language = 'FR';
			else $language = 'EN';
		} catch(Exception $e) {
			// Shouldn't happend. But setting the language is optional... so do nothing here.
			$language = 'EN';
		}
		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=clickandbuy&sid=' . $subscription->akeebasubs_subscription_id;
		
		$soap = new SoapClient($this->getWebserviceUrl(), array('encoding' => 'UTF-8'));
		try {
			$paymentRequest = $soap->payRequest(
				array(
					'authentication'	=> array(
						'merchantID'	=> $this->getMerchantId(),
						'projectID'		=> $this->getProjectId(),
						'token'			=> $this->generateToken()						
					),
					'details'			=> array(
						'amount'		=> array(
							'amount'	=> sprintf('%.2f', $subscription->gross_amount),
							'currency'	=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'))
						),
						'orderDetails'	=> array(
							'text'		=> $level->title
						),
						'successURL'		=> $callbackUrl . '&mode=success',
						'failureURL'		=> $callbackUrl . '&mode=failure',
						'externalID'		=> $subscription->akeebasubs_subscription_id,
						'consumerLanguage'	=> $language
					)
				)
			);
		} catch(Exception $e) {
			return JError::raiseError(500, 'Cannot proceed with the payment. You have an error in your setup of ClickandBuy: ' . $e->getMessage());
		}
		
		// 2. Check response
		$transactionResponse = $paymentRequest->transaction;
		$transactionID = $transactionResponse->transactionID;
		if(empty($transactionID)
				|| $transactionResponse->externalID != $subscription->akeebasubs_subscription_id
				|| $transactionResponse->transactionStatus != 'CREATED'
				|| $transactionResponse->transactionType != 'PAY') {
			return JError::raiseError(500, 'Cannot proceed with the payment. You have an error in your setup of ClickandBuy.');
		}
		
		// 3. Use redirect-URL for the form and save transaction-ID for the callback
		$data->url = $transactionResponse->redirectURL;
		$subscription->save(array(
			'processor_key'		=> $transactionResponse->transactionID
		));

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
				$data['akeebasubs_failure_reason'] = 'No valid subscription found.';
				$isValid = false;
			} else {
				$transactionID = $subscription->processor_key;
			}
		} else {
			$data['akeebasubs_failure_reason'] = 'No subscription found.';
			$isValid = false;
		}
		
		// Check mode
		if($isValid) {
			if($data['mode'] != 'success') {
				$data['akeebasubs_failure_reason'] = 'ClickandBuy returned failure.';
				$isValid = false;
			}
		}
        
		// Check if payment was already processed
		if($isValid) {
			if($subscription->state == 'C' || $subscription->state == 'X') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "This payment was already processed.";
			}
		}
		// Check status of payment
		if($isValid) {
			$soap = new SoapClient($this->getWebserviceUrl(), array('encoding' => 'UTF-8'));
			try {
				$statusRequest = $soap->statusRequest(
					array(
						'authentication'	=> array(
							'merchantID'	=> $this->getMerchantId(),
							'projectID'		=> $this->getProjectId(),
							'token'			=> $this->generateToken()						
						),
						'details'			=> array(
							'transactionIDList'	=> array(
								'transactionID'		=> $transactionID
							)
						)
					)
				);
				$status = $statusRequest->transactionList->transaction;
			} catch(Exception $e) {
				$data['akeebasubs_failure_reason'] = 'Cannot perform status request: ' . $e;
				$isValid = false;
			}
		}
		
		// Check error response
		if($isValid) {
			if(isset($status->errorDetails)) {
				$data['akeebasubs_failure_reason'] = 'Code: ' . $status->errorDetails->code
						. ', DetailCode: ' . $status->errorDetails->detailcode
						. ', Description: ' . $status->errorDetails->description;
				$isValid = false;
			}
		}
		
		// Check transaction response
		if($isValid) {
			if($status->transactionID != $transactionID
					|| $status->externalID != $subscription->akeebasubs_subscription_id
					|| $status->transactionStatus != 'SUCCESS'
					|| $status->transactionType != 'PAY') {
				$data['akeebasubs_failure_reason'] = 'Payment status did\'t return the expected values.';
				$isValid = false;
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Set payment status
		if($isValid) {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $transactionID,
				'state'							=> $newStatus,
				'enabled'						=> 0
		);
		jimport('joomla.utilities.date');
		if($newStatus == 'C') {
			// Fix the starting date if the payment was accepted after the subscription's start date. This
			// works around the case where someone pays by e-Check on January 1st and the check is cleared
			// on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
			// the user would have paid us and we'd never given him a subscription!
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
			if(!preg_match($regex, $subscription->publish_up)) {
				$subscription->publish_up = '2001-01-01';
			}
			if(!preg_match($regex, $subscription->publish_down)) {
				$subscription->publish_down = '2037-01-01';
			}
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
		if($newStatus == 'C') {
			$redirectUrl = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
		} else {
			$redirectUrl = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id));
		}
		$app->redirect($redirectUrl);
		return true;
	}
	
	
	private function getMerchantId()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return trim($this->params->get('sb_merchant_id',''));
		} else {
			return trim($this->params->get('merchant_id',''));
		}
	}
	
	private function getProjectId()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return trim($this->params->get('sb_project_id',''));
		} else {
			return trim($this->params->get('project_id',''));
		}
	}
	
	private function getKey()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return trim($this->params->get('sb_key',''));
		} else {
			return trim($this->params->get('key',''));
		}
	}
	
	private function getWebserviceUrl()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://api.clickandbuy-s1.com/webservices/pay_1_1_0.wsdl';
		} else {
			return 'https://api.clickandbuy.com/webservices/pay_1_1_0.wsdl';
		}
	}
	
	private function generateToken()
	{
		$timestamp = gmdate("YmdHis", time());
		return $timestamp . '::' . strtoupper(sha1(
				$this->getProjectId() .
				'::' . $this->getKey() .
				'::' . $timestamp
				));
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