<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentVerotel extends JPlugin
{
	private $ppName = 'verotel';
	private $ppKey = 'PLG_AKPAYMENT_VEROTEL_TITLE';

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
		$jlang->load('plg_akpayment_verotel', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_verotel', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_verotel', JPATH_ADMINISTRATOR, null, true);
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
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/logoSmall_verotel.png';
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
		
		$data = (object)array(
			'url'				=> 'https://secure.verotel.com/order/purchase',
			'shopID'			=> trim($this->params->get('shopid','')),
			'priceAmount'		=> sprintf('%.2f',$subscription->gross_amount),
			// Currency must be one of these: USD, EUR, GBP, NOK, SEK, DKK, CAD or CHF
			'priceCurrency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'description'		=> $level->title . ' - [ ' . $user->username . ' ]',
			'referenceID'		=> $subscription->akeebasubs_subscription_id
		);
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
        
		$signatureKey = $this->params->get('key','');
		$data->signature = sha1($signatureKey . ":description=" . $data->description .
			':priceAmount=' . $data->priceAmount .
			':priceCurrency=' .$data->priceCurrency .
			':referenceID=' .$data->referenceID .
			':shopID=' . $data->shopID.
			':version=1'
		);

		@ob_start();
		include dirname(__FILE__).'/verotel/form.php';
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
			$id = $data['referenceID'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenceID is invalid';
		}
        
		// Check that saleID has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['saleID']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not process the same saleID twice";
			}
		}
        
		// Check that priceCurrency is correct
		if($isValid && !is_null($subscription)) {
			$mc_currency = strtoupper($data['priceCurrency']);
			$currency = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
			if($mc_currency != $currency) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invalid currency; expected $currency, got $mc_currency";
			}
		}
		
		// Check that priceAmount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['priceAmount']);
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
		
		// Payment status always complete if this point is reached
		$newStatus = 'C';

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $data['referenceID'],
				'processor_key'					=> $data['saleID'],
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

		// Callback is valid - respond with OK
		@ob_end_clean();
		echo "OK";
		$app->close();
        
		return true;
	}
    
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$isValid = true;

		// Check the required data
		$signatureKey = $this->params->get('key','');
		if(empty($signatureKey)) $isValid = false;
		if(empty($data['priceAmount'])) $isValid = false;
		if(empty($data['priceCurrency'])) $isValid = false;
		if(empty($data['referenceID'])) $isValid = false;
		if(empty($data['saleID'])) $isValid = false;
		if(empty($data['shopID'])) $isValid = false;
		if(empty($data['signature'])) $isValid = false;

		// Check the signature
		if($isValid) {
			$signature = sha1($signatureKey .
				":priceAmount=" . $data['priceAmount'] .
				":priceCurrency=" . $data['priceCurrency'] .
				":referenceID=" . $data['referenceID'] .
				":saleID=" . $data['saleID'] .
				":shopID=" . $data['shopID']);

			$isValid = $data['signature'] == $signature;
		}

		return $isValid;
	}
 
	private function getResponseValue($purchaseResponse, $parameter)
	{
		preg_match("/^$parameter: ([^\r\n\t\f]+)/m", $purchaseResponse, $matches);
		if(! empty($matches[1])) {
			return trim($matches[1]);
		}
		return "";
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}
		$logFile = $logpath.'/akpayment_verotel_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_verotel_ipn-1.php';
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
		$logData .= $isValid ? 'VALID VEROTEL IPN' : 'INVALID VEROTEL IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}