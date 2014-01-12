<?php
	/**
	 * @package		akeebasubs
	 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
	 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
	 */

	defined('_JEXEC') or die();

	$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
	if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

	class plgAkpaymentBe2Bill extends plgAkpaymentAbstract
	{
		public function __construct(&$subject, $config = array())
		{
			$config = array_merge($config, array(
				'ppName'		=> 'be2bill',
				'ppKey'			=> 'PLG_AKPAYMENT_BE2BILL_TITLE',
				'ppImage'		=> JURI::root().'plugins/akpayment/be2bill/be2bill/logo.png'
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
         *
         * @throws  Exception|JError
         *
		 * @return string
		 */
		public function onAKPaymentNew($paymentmethod, $user, $level, $subscription)
		{
			if($paymentmethod != $this->ppName) return false;

            if(!$this->params->get('client_id') || !$this->params->get('client_password'))
            {
                if(version_compare(JVERSION, '3.0', 'ge'))
                {
                    throw new Exception('Be2Bill Payment Processor: Please supply a ClientID and a Client Password info before continuing', 500);
                }
                else
                {
                    JError::raiseError(500, 'Be2Bill Payment Processor: Please supply a ClientID and a Client Password info before continuing');
                }
            }

            if(!$this->params->get('server2server_main') || !$this->params->get('payment_url_main'))
            {
                if(version_compare(JVERSION, '3.0', 'ge'))
                {
                    throw new Exception('Be2Bill Payment Processor: Please supply the main url for server to server connection and the main url for payments before continuing', 500);
                }
                else
                {
                    JError::raiseError(500, 'Be2Bill Payment Processor: Please supply the main url for server to server connection and the main url for payments before continuing');
                }
            }

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

			$rootURL    = rtrim(JURI::base(),'/');
			$subpathURL = JURI::base(true);

			if(!empty($subpathURL) && ($subpathURL != '/'))
			{
				$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
			}

            $password = $this->params->get('client_password');
            $b2bparams = array(
                'identifier'        => $this->params->get('client_id'),
                'operationtype'     => 'payment',
                'description'       => $level->title . ' - [ ' . $user->username . ' ]',
                'orderid'           => $subscription->akeebasubs_subscription_id,
                'version'           => '2.0',
                'amount'            => intval($subscription->gross_amount * 100),
                'clientreferrer'    => JURI::base(true),
                'CLIENTUSERAGENT'   => JBrowser::getInstance()->getBrowser(),
                'CLIENTIP'          => $_SERVER['REMOTE_ADDR'],
                'CLIENTEMAIL'       => $user->email,
                'CLIENTIDENT'       => $user->id,
                'ALIAS'             => '',
                'ALIASMODE'         => '',
            );

            ksort($b2bparams);

            $plain = $password;

            foreach($b2bparams as $key => $value)
            {
                $plain .= strtoupper($key).'='.$value.$password;
            }

            $hash = hash('sha256', $plain);

			$data = (object)array(
				'url'			=> $this->getPaymentURL(),
                'identifier'    => $this->params->get('client_id'),
                'hash'          => $hash,
                'clientident'   => $user->id,
                'orderid'       => $subscription->akeebasubs_subscription_id,
                'amount'        => intval($subscription->gross_amount * 100)
			);

			@ob_start();
			include dirname(__FILE__).'/be2bill/form.php';
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
				return 'https://secure-test.be2bill.com/';
			} else {
                // WARNING!!!! IT MUST BE CONFIRMED!!! THERE ARE NO HINTS INSIDE THE DOCS!!!
				return 'https://secure.be2bill.com/';
			}
		}

        private function processTransaction($data)
		{

		}

		private function echoXML($string)
		{
			header('Content-type: application/xml');
			echo $string;
			JFactory::getApplication()->close();
		}
	}