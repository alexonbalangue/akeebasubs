<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentRobokassa extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'robokassa',
			'ppKey'			=> 'PLG_AKPAYMENT_ROBOKASSA_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/robokassa.jpg',
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
		
		// Language settings en or ru)
		$lang = strtolower(substr(JFactory::getLanguage()->getTag(), 0, 2));
		if($lang != 'en' && $lang != 'ru') {
			$lang = 'en';
		}
		
		$data = (object)array(
			'url'				=> $this->getURL(),
			'MrchLogin'			=> $this->params->get('merchant',''),
			'InvId'				=> $subscription->akeebasubs_subscription_id,
			'OutSum'			=> sprintf('%.2f',$subscription->gross_amount),
			'IncCurrLabel'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'Desc'				=> $level->title,
			'Culture'			=> $lang,
		);
		
		$data->SignatureValue = md5(
				$data->MrchLogin
				 . ':' . $data->OutSum
				 . ':' . $data->InvId
				 . ':' . $this->params->get('pass1','')); 

		@ob_start();
		include dirname(__FILE__).'/robokassa/form.php';
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
			$id = $data['InvId'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The reference is invalid';
		}
		
		if($isValid && isset($data['mode'])) {
			$mode = $data['mode'];
			$app = JFactory::getApplication();
			$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
					->setId($subscription->akeebasubs_level_id)
					->getItem()
					->slug;
			$rootURL = rtrim(JURI::base(),'/');
			$subpathURL = JURI::base(true);
			if(!empty($subpathURL) && ($subpathURL != '/')) {
				$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
			}
			if($mode == 'cancel') {
				$cancelUrl = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id));
				$app->redirect($cancelUrl);
				return true;
			} else if($mode == 'success') {
				$successUrl = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
				$app->redirect($successUrl);
				return true;
			}
		}
		
		if($isValid) {
			// Check IPN data for validity (i.e. protect against fraud attempt)
			$isValid = $this->isValidIPN($data);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';
		}
        
		// Check that bank_id has not been previously processed
		if($isValid) {
			if($data['SignatureValue'] == $subscription->processor_key && $subscription->state == 'C') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not processed this payment twice";
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['OutSum']);
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
			return false;
		}
		
		// Payment status
		$newStatus = 'C';

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['SignatureValue'],
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
		// Callback is valid - respond with OK
		@ob_end_clean();
		echo 'OK' . $subscription->akeebasubs_subscription_id;
		$app->close();
		
		return true;
	}
	
	
	/**
	 * Gets the form action URL
	 */
	private function getURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'http://test.robokassa.ru/Index.aspx';
		} else {
			return 'https://merchant.roboxchange.com/Index.aspx';
		}
	}
	
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$checksum = strtoupper(md5(
				$data['OutSum']
				 . ':' . $data['InvId']
				 . ':' . $this->params->get('pass2','')));
		return $checksum == strtoupper($data['SignatureValue']);
	}
}