<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentGooglecheckout extends JPlugin
{
	private $ppName = 'googlecheckout';
	private $ppKey = 'PLG_AKPAYMENT_GOOGLECHECKOUT_TITLE';

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
		$jlang->load('plg_akpayment_googlecheckout', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_googlecheckout', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_googlecheckout', JPATH_ADMINISTRATOR, null, true);
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
			$ret['image'] = 'https://checkout.google.com/buttons/checkout.gif?merchant_id=&w=180&h=46&style=white&variant=text&loc=en_US';
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
		jimport('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Include Google Checkout library files
		require_once dirname(__FILE__) . "/googlecheckout/library/googleresponse.php";
		require_once dirname(__FILE__) . "/googlecheckout/library/googleresult.php";
		require_once dirname(__FILE__) . "/googlecheckout/library/googlerequest.php";

		
		$response = new GoogleResponse($this->getMerchantID(), $this->getMerchantKey());
		
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
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

					$updates['publish_up'] = $jStart->toMySQL();
					$updates['publish_down'] = $jEnd->toMySQL();
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
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
		$logFile = $logpath.'/akpayment_googlecheckout_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_googlecheckout_ipn-1.php';
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
		$logData .= $isValid ? 'VALID GOOGLE CHECKOUT IPN' : 'INVALID GOOGLE CHECKOUT IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}