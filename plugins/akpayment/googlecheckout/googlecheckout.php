<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentGooglecheckout extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'googlecheckout',
			'ppKey'			=> 'PLG_AKPAYMENT_GOOGLECHECKOUT_TITLE',
			'ppImage'		=> 'https://checkout.google.com/buttons/checkout.gif?merchant_id=&w=180&h=46&style=white&variant=text&loc=en_US'
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
		
		$nameParts = explode(' ', $user->name, 2);
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
		
		$data = (object)array(
			'url'			=> $this->getPaymentURL(true).$this->getMerchantID(),
			'merchant'		=> $this->getMerchantID(),
			'merchant_key'	=> $this->getMerchantKey(),
			'servertype'	=> $this->getServerType(),
			//'postback'		=> rtrim(JURI::base(),'/').str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=callback&paymentmethod=googlecheckout')),
			'postback'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=googlecheckout',
			'success'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'firstname'		=> $firstName,
			'lastname'		=> $lastName,
			'buttonurl'		=> $this->getPaymentURL(false),
		);
		
		// Include Google Checkout library files
		require_once dirname(__FILE__) . "/googlecheckout/library/googlecart.php";
		require_once dirname(__FILE__) . "/googlecheckout/library/googleitem.php";
		require_once dirname(__FILE__) . "/googlecheckout/library/googleshipping.php";
		require_once dirname(__FILE__) . "/googlecheckout/library/googletax.php";
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		// Create Google cart
		$cart = new GoogleCart($data->merchant, $data->merchant_key, $data->servertype, $data->currency);
		$totalTax=0;
		$totalPrice = 0;
		
		// Add item to the cart
		$item = new GoogleItem(
			$level->title,
			$level->title . ' - [ ' . $user->username . ' ]',
			1,
			$subscription->net_amount
		);
		$cart->AddItem($item);
		$totalPrice = $subscription->net_amount;
		$totalTax = $subscription->tax_amount;
		
		// Apply tax rules
		$tax_rule = new GoogleDefaultTaxRule($subscription->tax_percent / 100);
	    $tax_rule->SetWorldArea(true);
	    $cart->AddDefaultTaxRules($tax_rule);
		
		// Set return URL
		$cart->SetContinueShoppingUrl($data->success);
		
		// set order ID and data
		$mcprivatedata= new MerchantPrivateData();
		$mcprivatedata->data= array("akeebasubs_subscription_id"=>$subscription->akeebasubs_subscription_id);
		$cart->SetMerchantPrivateData($mcprivatedata);
		$data->cart=$cart;
		
		@ob_start();
		include dirname(__FILE__).'/googlecheckout/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Include Google Checkout library files
		require_once dirname(__FILE__) . "/googlecheckout/library/googleresponse.php";
		require_once dirname(__FILE__) . "/googlecheckout/library/googleresult.php";
		require_once dirname(__FILE__) . "/googlecheckout/library/googlerequest.php";

		
		$response = new GoogleResponse($this->getMerchantID(), $this->getMerchantKey());
		
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}
		$response->SetLogFiles($logpath . '/google_error.log', $logpath . '/google_message.log', L_ALL);
		
		// Get the XML postback
		$xml_response = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');
		if(empty($xml_response)) return false;
		// De-slash the response on servers using magic_quotes_gpc
		if (get_magic_quotes_gpc()) {
			$xml_response = stripslashes($xml_response);
		}
		
		list($root, $data) = $response->GetParsedXML($xml_response);
		
		// prepare the payment data
		$data = $data[$root];
		$payment_details = $this->reformatData($xml_response);
		
		switch($root)
		{
			// New order (payment processing began at Google Checkout side)
			case 'new-order-notification':
				// Update the payment processor key with the Google order ID
				$id = $data['shopping-cart']['merchant-private-data']['akeebasubs_subscription_id']['VALUE'];
				
				$isValid = true;
				
				// Load the subscription row
				if($isValid) {
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
				
				$this->logIPN($data, $isValid);
				
				if($isValid) {
					$updates = array(
						'akeebasubs_subscription_id' => $id,
						'state'				=> 'P', // Pending
						'enabled'			=> 0, // Not yet enabled (it's pending!)
						'processor_key'		=> $data['google-order-number']['VALUE'],
					);
					$subscription->save($updates);
				} else {
					return false;
				}
				break;
			
			// Process a payment change notification
			case 'order-state-change-notification':
				$processor_key = $data['google-order-number']['VALUE'];
				
				$isValid = true;
				
				if($isValid) {
					$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
							->paykey($processor_key)
							->getFirstItem();
					if( (!$subscription->processor_key) || ($subscription->processor_key != $processor_key) ) {
						$subscription = null;
						$isValid = false;
					}
					
					if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid Google order ID';
				}
				
				// Check the total amount paid
				if($isValid) {
					$totalPaid = (float)$data['total-charge-amount']['VALUE'];
					$gross = $subscription->gross_amount;
					
					if(abs($totalPaid - $gross) > 1) $isValid = false;
					
					if(!$isValid) $data['akeebasubs_failure_reason'] = "Invalid amount paid: $totalPaid does not match expected $gross";
				}
				
				// Log the IPN data
				$this->logIPN($data, $isValid);
				
				// Fraud attempt? Do nothing more!
				if(!$isValid) return false;

				// Send ACK
				$serial = $data[$root]['serial-number'];
				$response->SendAck($serial);
				
				// Check the payment_status
				switch($data ['new-financial-order-state']['VALUE'])
				{
					case 'CHARGED':
						$newStatus = 'C';
						break;

					case 'REVIEWING':
					case 'CHARGEABLE':
					case 'CHARGING':
						$newStatus = 'P';
						break;

					case 'PAYMENT_DECLINED':
					case 'CANCELLED':
					case 'CANCELLED_BY_GOOGLE':
					default:
						$newStatus = 'X';
						break;
				}

				// Update subscription status (this also automatically calls the plugins)
				$updates = array(
					'akeebasubs_subscription_id' => $subscription->akeebasubs_subscription_id,
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
				break;
			
			default:
				return false;
				break;
		}
		
		return true;
	}
	
	/**
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL($full = true)
	{
		$sandbox = $this->params->get('sandbox',0);
		if($full) {
			if($sandbox) {
				return 'https://sandbox.google.com/checkout/api/checkout/v2/checkoutForm/Merchant/';
			} else {
				return 'https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/';
			}
		} else {
			if($sandbox) {
				return 'https://sandbox.google.com/checkout';
			} else {
				return 'https://checkout.google.com';
			}
		}
	}
	
	/**
	 * Get the server type for the Google Checkout API
	 * @return type 
	 */
	private function getServerType()
	{
		$sandbox = $this->params->get('sandbox',0);
		return $sandbox ? 'sandbox' : 'production';
	}
	
	/**
	 * Gets the Google Checkout Merchant Key
	 */
	private function getMerchantKey()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return $this->params->get('sandbox_merchant_key','');
		} else {
			return $this->params->get('merchant_key','');
		}
	}
	
	/**
	 * Gets the Google Checkout Merchant ID (usually the email address)
	 */
	private function getMerchantID()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return $this->params->get('sandbox_merchant','');
		} else {
			return $this->params->get('merchant','');
		}
	}
	
	/**
	 * Reformats the XML response for logging
	 */
	private function reformatData($data)
	{
		$data = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $data);
		$data = str_replace(array('<', '>'), array('[', ']'), $data);

		return $data;
	}
}