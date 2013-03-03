<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentScnet extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'scnet',
			'ppKey'			=> 'PLG_AKPAYMENT_SCNET_TITLE',
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
                        
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		$data = (object)array(
			'url'					=> $this->getPaymentURL(),
			'gate'					=> $this->getMerchantID(),
			'adminemail'			=> trim($this->params->get('adminemail','')),
			'process'				=> 'process',
			'amount'				=> sprintf('%.2f',$subscription->gross_amount),
			'returl'				=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=scnet',
			'invoice_id'			=> $subscription->akeebasubs_subscription_id,
			'items'					=> $level->title,
			'cust'					=> trim($user->name),
			'street'				=> trim($kuser->address1),
			'city'					=> trim($kuser->city),
			'country'				=> AkeebasubsHelperSelect::decodeCountry(trim($kuser->country)),
			'postcode'				=> trim($kuser->zip),
			'currency'				=> strtoupper(AkeebasubsHelperCparams::getParam('currency','AUD')),
			'image'					=> trim($this->params->get('image','')),
			'backgroundimage'		=> trim($this->params->get('backgroundimage','')),
			'tablebgcolour'			=> ltrim(trim($this->params->get('tablebgcolour','')),'#')
		);

		@ob_start();
		include dirname(__FILE__).'/scnet/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Prepare success- & cancel-URL
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
		$successUrl = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
		$cancelUrl = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id));
		
		$isValid = true;

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['invoice_id'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The invoice_id is invalid';
		}
        
		// Check that bank_id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['bank_id']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same bank_id twice";
			}
		}
			
		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) {
			$app->redirect($cancelUrl);
			return false;
		}
		
		// Payment status
		if($data['summarycode'] == '0' && $data['responsecode'] == '00') {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['bank_id'],
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
        
		$app->redirect($successUrl);
		return true;
	}
	
	
	/**
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://www.scnet.com.au/ipayby/hostedccbuy';
		} else {
			return 'https://secure.ipayby.com.au/ipayby/hostedccbuy';
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
}