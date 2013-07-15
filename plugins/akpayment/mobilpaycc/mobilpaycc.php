<?php
	/**
	 * @package		akeebasubs
	 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
	 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
	 */

	defined('_JEXEC') or die();

	$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
	if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

	class plgAkpaymentMobilpaycc extends plgAkpaymentAbstract
	{
		public function __construct(&$subject, $config = array())
		{
			$config = array_merge($config, array(
				'ppName'		=> 'mobilpaycc',
				'ppKey'			=> 'PLG_AKPAYMENT_MOBILPROCC_TITLE',
				'ppImage'		=> JURI::root().'plugins/akpayment/mobilpaycc/mobilpaycc/logo.png'
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
			if(count($nameParts) > 1)
			{
				$lastName = $nameParts[1];
			}
			else
			{
				$lastName = '';
			}

			$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;

			$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
				->user_id($user->id)
				->getFirstItem();

			$rootURL    = rtrim(JURI::base(),'/');
			$subpathURL = JURI::base(true);
			if(!empty($subpathURL) && ($subpathURL != '/'))
			{
				$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
			}

			// Let's get all the data and process them using MobiPro API
			require_once 'mobilpaycc/library/Request/Abstract.php';
			require_once 'mobilpaycc/library/Request/Card.php';
			require_once 'mobilpaycc/library/Invoice.php';
			require_once 'mobilpaycc/library/Address.php';

			$x509FilePath = JPATH_ROOT.'/plugins/akpayment/mobilpaycc/mobilpaycc/private/public.cer';

			srand((double) microtime() * 1000000);

			try{
				$objPmReqCard 						= new Mobilpay_Payment_Request_Card();
				$objPmReqCard->signature 			= $this->params->get('signature', '');
				$objPmReqCard->orderId 				= md5(uniqid(rand()));
				$objPmReqCard->returnUrl 			= $rootURL.JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id, false);
				$objPmReqCard->confirmUrl 			= JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=mobilpaycc';

				$objPmReqCard->invoice              = new Mobilpay_Payment_Invoice();
				// Only RON currency is supported by this payment method...
				$objPmReqCard->invoice->currency	= 'RON';
				$objPmReqCard->invoice->amount		= $subscription->net_amount;
				$objPmReqCard->invoice->details		= $level->title . ' - [ ' . $user->username . ' ]';

				$billingAddress 				= new Mobilpay_Payment_Address();
				if($kuser->isbusiness)
				{
					$billingAddress->type			= 'company';
					$billingAddress->firstName		= $firstName;
					$billingAddress->lastName		= $lastName;
					$billingAddress->fiscalNumber	= $kuser->vatnumber;
					//Docs says to put here the "registration number" (which we don't have)
					$billingAddress->identityNumber	= '';
				}
				else
				{
					$billingAddress->type			= 'person';
					$billingAddress->firstName		= $firstName;
					$billingAddress->lastName		= $lastName;
					//Docs says to put here the "personal number" and "identity card number" (which we don't have)
					$billingAddress->fiscalNumber	= '';
					$billingAddress->identityNumber	= '';
				}

				$billingAddress->country		= $kuser->country;
				$billingAddress->city			= $kuser->city;
				$billingAddress->zipCode		= $kuser->zip;
				$billingAddress->address		= $kuser->address1.' '.$kuser->address2;
				$billingAddress->email			= $user->email;
				$objPmReqCard->invoice->setBillingAddress($billingAddress);

				$objPmReqCard->encrypt($x509FilePath);
			}
			catch(Exception $e)
			{
				return false;
			}

			$data = (object)array(
				'url'			=> $this->getPaymentURL()
			);

			@ob_start();
			include dirname(__FILE__).'/mobilpaycc/form.php';
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

			if(!$isValid) $data['akeebasubs_failure_reason'] = 'MobilPro reports transaction as invalid';

			// Load the relevant subscription row
			if($isValid) {
				$id = array_key_exists('custom', $data) ? (int)$data['custom'] : -1;
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

			// Log the IPN data
			$this->logIPN($data, $isValid);

			// Fraud attempt? Do nothing more!
			if(!$isValid)
			{
				//Let's inform MobilPro we've done
				//$this->echoXML($transInfo['xml']);
				return false;
			}

			// Update subscription status (this also automatically calls the plugins)
			$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['orderId'],
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
			if($transInfo['state'] == 'C') {
				$this->fixDates($subscription, $updates);
			}

			// Save the changes
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
			require_once 'mobilpaycc/library/Request/Abstract.php';
			require_once 'mobilpaycc/library/Request/Card.php';
			require_once 'mobilpaycc/library/Request/Notify.php';
			require_once 'mobilpaycc/library/Invoice.php';
			require_once 'mobilpaycc/library/Address.php';

			$return 		= array();

			$errorCode 		= 0;
			$errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
			$message		= '';

			if(isset($data['env_key']) && isset($data['data']))
			{
				$privateKeyFilePath = JPATH_ROOT.'/plugins/akpayment/mobilpaycc/mobilpaycc/private/public.cer';

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

					$result['valid'] = true;
				}
				catch(Exception $e)
				{
					$errorType 	= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY;
					$errorCode	= $e->getCode();
					$message 	= $e->getMessage();

					$result['valid'] = false;
				}
			}
			else
			{
				$errorType 	= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
				$errorCode	= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS;
				$message 	= 'mobilpay.ro posted invalid parameters';

				$result['valid'] = false;
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
	}