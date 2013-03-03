<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentSuomenVerkkomaksut extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'suomenverkkomaksut',
			'ppKey'			=> 'PLG_AKPAYMENT_SUOMENVERKKOMAKSUT_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/logo_suomen_verkkomaksut.png'
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
		
		$nameParts = explode(' ', trim($user->name), 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		$sandbox = $this->params->get('sandbox',0);
		
		$data = (object)array(
			'url'					=> 'https://payment.verkkomaksut.fi',
			'merchant_hash'			=> $sandbox ? '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ' : trim($this->params->get('merchant_hash','')),
			'merchant_id'			=> $sandbox ? '13466' : trim($this->params->get('merchant_id','')),
			'order_number'			=> str_replace(' ', '', $level->title) . '-' . $subscription->akeebasubs_subscription_id,
			'order_description'		=> $level->title . ' - [ ' . $user->username . ' ]',
			'currency'				=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			// successfull payment
			'return_address'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			// failed payment
			'cancel_address'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'notify_address'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=suomenverkkomaksut',
			'type'					=> 'E1',
			'culture'				=> trim($this->params->get('culture','')),
			'contact_email'			=> trim($user->email),
			'contact_firstname'		=> $firstName,
			'contact_lastname'		=> $lastName,
			'contact_addr_street'	=> trim($kuser->address1),
			'contact_addr_zip'		=> trim($kuser->zip),
			'contact_addr_city'		=> trim($kuser->city),
			'contact_addr_country'	=> strtoupper(trim($kuser->country)),
			// VAT is included in the price further below (item_price)
			'include_vat'			=> '1',
			'items'					=> '1',
			'item_title_0'			=> $level->title,
			'item_amount_0'			=> '1',
			'item_price_0'			=> sprintf('%.2f',$subscription->gross_amount),
			'item_tax_0'			=> $subscription->tax_percent
		);
		
		if($kuser->isbusiness) {
			$data->contact_company = trim($kuser->businessname);
		}
		
		$data->authcode = strtoupper(md5(
				$data->merchant_hash . '|' . $data->merchant_id .
				'|' . $data->order_number . '||' . $data->order_description .
				'|' . $data->currency . '|' . $data->return_address .
				'|' . $data->cancel_address . '||' . $data->notify_address .
				'|' . $data->type . '|' . $data->culture .
				'|||||||' . $data->contact_email . '|' . $data->contact_firstname .
				'|' . $data->contact_lastname . '|' . $data->contact_company .
				'|' . $data->contact_addr_street . '|' . $data->contact_addr_zip .
				'|' . $data->contact_addr_city . '|' . $data->contact_addr_country .
				'|' . $data->include_vat . '|' . $data->items .
				'|' . $data->item_title_0 . '||' . $data->item_amount_0 .
				'|' . $data->item_price_0 . '|' . $data->item_tax_0 . '||'
				));

		@ob_start();
		include dirname(__FILE__).'/suomenverkkomaksut/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
        
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$orderId = $data['ORDER_NUMBER'];
			$id = substr(strrchr($orderId, '-'), 1);
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
        
		// Check that Transcation ID has not been previously processed
		if($isValid && isset($data['PAID']) && !is_null($subscription)) {
			if($subscription->processor_key == $data['PAID']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same Paid Transcation ID twice";
			}
		}
			
		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;
		
		// Payment status
		if(empty($data['PAID']) || empty($data['METHOD'])) {
			$newStatus = 'P';
		} else {
			$newStatus = 'C';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['PAID'],
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
    
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$hashCode = strtoupper(md5(
				$data['ORDER_NUMBER'] . '|' . $data['TIMESTAMP'] .
				'|' . $data['PAID'] . '|' . $data['METHOD'] .
				'|' . trim($this->params->get('merchant_hash',''))));
		return $hashCode == $data['RETURN_AUTHCODE'];
	}
}