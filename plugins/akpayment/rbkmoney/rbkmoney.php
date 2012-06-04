<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentRBKMoney extends JPlugin
{
	private $ppName = 'rbkmoney';
	private $ppKey = 'PLG_AKPAYMENT_RBKMONEY_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!is_object($config['params'])) {
			$config['params'] = new JParameter($config['params']);
		}

		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_rbkmoney', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_rbkmoney', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_rbkmoney', JPATH_ADMINISTRATOR, null, true);
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
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/rbkmoney.gif';
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
		
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		$successURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
		$failureURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id));
		
		$data = (object)array(
			'url'               => 'https://rbkmoney.ru/acceptpurchase.aspx',
			'eshopId'           => trim($this->params->get('eshopid','')),
			'orderId'           => $subscription->akeebasubs_subscription_id,
			'serviceName'       => $level->title . ' - [ ' . $user->username . ' ]',
			'recipientAmount'   => sprintf('%.2f',$subscription->gross_amount),
			// Currency: USD, RUR, EUR, UAH or GBP
			'recipientCurrency' => strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'language'			=> $this->params->get('language','en'),
			'user_email'        => trim($user->email),
			'successUrl'        => $successURL,
			'failUrl'           => $failureURL
		);

		@ob_start();
		include dirname(__FILE__).'/rbkmoney/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		jimport('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
        
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['orderId'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The orderId is invalid';
		}
		
		// Check eshopId
		if($isValid) {
			if($data['eshopId'] != trim($this->params->get('eshopid',''))) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invalid eshopId: Doesn't match with the one used in the request";
			}
		}
        
		// Check that paymentId has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['paymentId']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same paymentId twice";
			}
		}

		// Check that recipientCurrency is correct
		if($isValid && !is_null($subscription)) {
			$mc_currency = strtoupper($data['recipientCurrency']);
			$currency = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
			if($mc_currency != $currency) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invalid currency; expected $currency, got $mc_currency";
			}
		}

		// Check that recipientAmount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['recipientAmount']);
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
		if(!$isValid) return false;

		// Check the payment_status
		switch($data['paymentStatus'])
		{
			case 3:
				$newStatus = 'P';
				break;
			case 5:
				$newStatus = 'C';
				break;
			default:
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['paymentId'],
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
   
		return true;
	}
    
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$isValid = true;

		// Check required data
		$secretKey = $this->params->get('key','');
		if(empty($secretKey)) $isValid = false;
		if(empty($data['eshopId'])) $isValid = false;
		if(empty($data['orderId'])) $isValid = false;
		if(empty($data['serviceName'])) $isValid = false;
		if(empty($data['eshopAccount'])) $isValid = false;
		if(empty($data['recipientAmount'])) $isValid = false;
		if(empty($data['recipientCurrency'])) $isValid = false;
		if(empty($data['paymentStatus'])) $isValid = false;
		if(empty($data['userName'])) $isValid = false;
		if(empty($data['userEmail'])) $isValid = false;
		if(empty($data['hash'])) $isValid = false;
		
		// SecretKey can be empty if the callback URL is unsecure (http)
		if($isValid) {
			if(! empty($data['secretKey'])) {
				$isValid = $data['secretKey'] == $secretKey; 
			}
		}

		// Check the hash
		if($isValid) {
			$hash = md5($data['eshopId'] .
				"::" . $data['orderId'] .
				"::" . $data['serviceName'] .
				"::" . $data['eshopAccount'] .
				"::" . $data['recipientAmount'] .
				"::" . $data['recipientCurrency'] .
				"::" . $data['paymentStatus'] .
				"::" . $data['userName'] .
				"::" . $data['userEmail'] .
				"::" . $secretKey);

			$isValid = $data['hash'] == $hash;   
		}

		return $isValid;
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
		$logFile = $logpath.'/akpayment_rbkmoney_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_rbkmoney_ipn-1.php';
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
		$logData .= $isValid ? 'VALID RBKMONEY IPN' : 'INVALID RBKMONEY IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}