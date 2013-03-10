<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentMoneris extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'moneris',
			'ppKey'			=> 'PLG_AKPAYMENT_MOIP_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/pp_eSp_small.gif'
		));
		
		parent::__construct($subject, $config);
		
		require_once dirname(__FILE__).'/moneris/api.php';
	}

	public function onAKPaymentNew($paymentmethod, $user, $level, $subscription)
	{
		if($paymentmethod != $this->ppName) return false;
		
		@ob_start();
		include dirname(__FILE__).'/moneris/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Load the relevant subscription row
		$isValid = true;
		if($isValid) {
			$id = array_key_exists('ak_moneris_id', $data) ? (int)$data['ak_moneris_id'] : -1;
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID is invalid';
		}
		
		// Figure out the error redirection URL
		if($isValid) {
			$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem();
			$error_url = 'index.php?option='.JRequest::getCmd('option').
				'&view=level&slug='.$level->slug.
				'&layout='.JRequest::getCmd('layout','default');
			$Itemid = JRequest::getInt('Itemid',0);
			if($Itemid) $error_url.='&Itemid='.$Itemid;
		} else {
			$error_url = 'index.php?option='.JRequest::getCmd('option').
				'&view=levels'.
				'&layout='.JRequest::getCmd('layout','default');
		}
		$error_url = JRoute::_($error_url,false);
		
		// Do we have a CC number?
		if($isValid) {
			$pan = JRequest::getCmd('ak_moneris_ccnumber','');
			$pan = str_replace('-', '', $pan);
			$pan = str_replace(' ', '', $pan);
			if(empty($pan)) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_MONERIS_ERR_NOCCNUMBER');
			}
		}
		
		// Do we have an expiration month?
		if($isValid) {
			$ccmonth = JRequest::getInt('ak_moneris_month',0);
			if( ($ccmonth <= 0) || ($ccmonth > 12) ) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_MONERIS_ERR_NOEXPMONTH');
			}
		}
		
		// Do we have an expiration year?
		if($isValid) {
			$ccyear = JRequest::getInt('ak_moneris_year',0);
			$curyear = gmdate('Y');
			if( ($ccyear < $curyear) || ($ccyear > ($curyear+10)) ) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_MONERIS_ERR_NOEXPYEAR');
			}
		}
		
		// Do we have a valid-looking CVV?
		if($isValid) {
			$cvv = trim(JRequest::getCmd('ak_moneris_cvv',''));
			if( empty($cvv) || (strlen($cvv) < 3) || (strlen($cvv) > 4) ) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = JText::_('PLG_AKPAYMENT_MONERIS_ERR_NOCVV');
			}
		}
		
		if(!$isValid) {
			JFactory::getApplication()->redirect($error_url,$data['akeebasubs_failure_reason'],'error');
		}
		
		$g = mpgGlobals::getInstance();
		$g->setSandbox($this->params->get('sandbox',0));
		
		/************************ Request Variables ***************************/
		$store_id = $this->params->get('storeid','');
		$api_token = $this->params->get('apikey','');
		/********************* Transactional Variables ************************/
		$type = 'purchase';
		$order_id = $subscription->akeebasubs_subscription_id;
		$cust_id = $subscription->user_id;
		$amount = sprintf('%.2f', $subscription->gross_amount);
		$expiry_date = sprintf('%02u',substr($ccyear,-2)).sprintf('%02u',$ccmonth);
		$crypt = '7'; // SSL-enabled merchant
		if( !JURI::getInstance()->isSSL() ) $crypt = 8; // Non-SSL merchant
		/************************** CVD Variables *****************************/
		$cvd_indicator = '1';
		$cvd_value = $cvv;
		/********************** CVD Associative Array *************************/
		$cvdTemplate = array('cvd_indicator' => $cvd_indicator, 'cvd_value' => $cvd_value);
		/************************** CVD Object ********************************/
		$mpgCvdInfo = new mpgCvdInfo ($cvdTemplate);
		/***************** Transactional Associative Array ********************/
		$txnArray=array(
			'type'=>$type,
			'order_id'=>$order_id,
			'cust_id'=>$cust_id,
			'amount'=>$amount,
			'pan'=>$pan,
			'expdate'=>$expiry_date,
			'crypt_type'=>$crypt
		);
		/********************** Transaction Object ****************************/
		$mpgTxn = new mpgTransaction($txnArray);
		/************************ Set CVD *****************************/
		$mpgTxn->setCvdInfo($mpgCvdInfo);
		/************************ Request Object ******************************/
		$mpgRequest = new mpgRequest($mpgTxn);
		/*********************** HTTPS Post Object ****************************/
		$mpgHttpPost =new mpgHttpsPost($store_id,$api_token,$mpgRequest);
		/*************************** Response *********************************/
		$mpgResponse=$mpgHttpPost->getMpgResponse();
		
		// Log the IPN data
		$isValid = true;
		$data['akeebasubs_failure_reason'] = '';
		$data = $mpgResponse->getMpgResponseData();
		if(!$mpgResponse->getComplete()) {
			$data['akeebasubs_failure_reason'] = $mpgResponse->getMessage();
		}
		$this->logIPN($data, $isValid);
		
		if(!empty($data['akeebasubs_failure_reason'])) {
			JFactory::getApplication()->redirect($error_url,$data['akeebasubs_failure_reason'],'error');
			return false;
		}
		
		// Check the payment_status
		if($data['ReponseCode'] <= 49) {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id' => $subscription->akeebasubs_subscription_id,
			'processor_key'		=> $data['ReferenceNum'],
			'state'				=> $newStatus,
			'enabled'			=> 0
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
		$url = JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$subscription->slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
		JFactory::getApplication()->redirect($url);

		return true;
	}
	
	public function selectMonth()
	{
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 1; $i <= 12; $i++) {
			$options[] = JHTML::_('select.option',$i,sprintf('%02u', $i));
		}
		
		return JHTML::_('select.genericlist', $options, 'ak_moneris_month', '', 'value', 'text', '', 'ak_moneris_month');
	}
	
	public function selectYear()
	{
		$year = gmdate('Y');
		
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 0; $i <= 10; $i++) {
			$options[] = JHTML::_('select.option',$i+$year,sprintf('%04u', $i+$year));
		}
		
		return JHTML::_('select.genericlist', $options, 'ak_moneris_year', '', 'value', 'text', '', 'ak_moneris_year');
	}
}