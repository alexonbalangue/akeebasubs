<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpayment2checkout extends JPlugin
{
	private $ppName = '2checkout';
	private $ppKey = 'PLG_AKPAYMENT_2CHECKOUT_TITLE';

	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_2checkout', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_2checkout', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_2checkout', JPATH_ADMINISTRATOR, null, true);
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
			$ret['image'] = 'http://www.2checkout.com/images/paymentlogoshorizontal.png';
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
		
		$data = (object)array(
			'url'			=> 'https://www.2checkout.com/checkout/purchase',
			'sid'			=> $this->params->get('sid',''),
			'x_receipt_link_url'	=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'params'		=> $this->params,
			'name'			=> $user->name,
			'email'			=> $user->email
		);
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();

		@ob_start();
		include dirname(__FILE__).'/2checkout/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		jimport('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Check if it's one of the message types supported by this plugin
		$message_type = $data['message_type'];
		$isValid = in_array($message_type, array(
			'ORDER_CREATED', 'REFUND_ISSUED', 'RECURRING_INSTALLMENT_SUCCESS'
		));
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'INS message type "'.$message_type.'" is not supported.';
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		if($isValid) {
			$isValid = $this->isValidIPN($data);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Transaction MD5 signature is invalid. Fraudulent transaction or testing mode enabled.';
		}
		
		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('vendor_order_id', $data) ? (int)$data['vendor_order_id'] : -1;
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("vendor_order_id" field) is invalid';
		}
		
		// Check that order_number has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['sale_id'].'/'.$data['invoice_id']) {
				if(($subscription->state == 'C') && ($message_type == 'ORDER_CREATED')) {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same sale_id/invoice_id twice";
				}
			}
		}
		
		// Check that total is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['invoice_list_amount']);
			$gross = $subscription->gross_amount;
			if($mc_gross > 0) {
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			} else {
				$valid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount (invoice_list_amount) does not match the subscription amount';
		}
		
		// Log the IPN data
		$this->logIPN($data, $isValid);
		
		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;
		
		// Load the subscription level and get its slug
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;

		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		switch($message_type) {
			case 'ORDER_CREATED':
				switch($data['invoice_status'])
				{
					case 'approved':
						$newStatus = 'P';
						break;

					case 'pending':
						$newStatus = 'P';
						break;
					
					case 'deposited':
						$newStatus = 'C';
						break;
					
					case 'declined':
					default:
						$newStatus = 'X';
						break;
				}
				break;
			
			case 'REFUND_ISSUED':
				$newStatus = 'X';
				break;
			
			case 'RECURRING_INSTALLMENT_SUCCESS':
				// @todo Handle recurring payments
				$newStatus = 'C';
				break;
			
		}
		
		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'		=> $data['sale_id'].'/'.$data['invoice_id'],
			'state'				=> $newStatus,
			'enabled'			=> 0
		);
		jimport('joomla.utilities.date');
		if($newStatus == 'C') {
			// Fix the starting date if the payment was accepted after the subscription's start date. This
			// works around the case where someone pays by e-Check on January 1st and the check is cleared
			// on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
			// the user would have paid us and we'd never given him a subscription!
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
	 * Validates the incoming data against the md5 posted by 2Checkout to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN($data)
	{
		// This is the MD5 calculations in 2Checkout's INS guide
		$incoming_md5 = strtoupper($data['md5_hash']);
		$calculated_md5 = md5(
			$data['sale_id'].
			$data['vendor_id'].
			$data['invoice_id'].
			$this->params->get('secret','')
		);
		$calculated_md5 = strtoupper($calculated_md5);
		
		return ($calculated_md5 == $incoming_md5);
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
		$logFile = $logpath.'/akpayment_2checkout_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_2checkout_ipn-1.php';
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
		$logData .= $isValid ? 'VALID 2CHEKOUT PAYMENT' : 'INVALID 2CHECKOUT PAYMENT *** FRAUD ATTEMPT OR INVALID TRANSACTION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}