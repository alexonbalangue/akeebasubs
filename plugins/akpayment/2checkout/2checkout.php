<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
		
		// Load the language files
		$jlang =& JFactory::getLanguage();
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
		return (object)$ret;
	}
	
	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * "paymentForm" and a visible submit button.
	 * 
	 * @param string $paymentmethod
	 * @param JUser $user
	 * @param KDatabaseRow $level
	 * @param KDatabaseRow $subscription
	 * @return string
	 */
	public function onAKPaymentNew($paymentmethod, $user, $level, $subscription)
	{
		if($paymentmethod != $this->ppName) return false;
		
		$slug = KFactory::tmp('admin::com.akeebasubs.model.levels')
				->id($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		$data = (object)array(
			'url'			=> 'https://www.2checkout.com/checkout/purchase',
			'sid'			=> $this->params->get('sid',''),
			'x_receipt_link_url'	=> rtrim(JURI::base(),'/').'/index.php?option=com_akeebasubs&view=callback&paymentmethod=2checkout',
			'params'		=> $this->params,
			'name'			=> $user->name,
			'email'			=> $user->email
		);
		
		$kuser = KFactory::tmp('admin::com.akeebasubs.model.users')
			->user_id($user->id)
			->getItem();

		@ob_start();
		include dirname(__FILE__).DS.'2checkout'.DS.'form.php';
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
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Transaction MD5 signature is invalid. Fraudulent transaction or testing mode enabled.';
		
		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('cart_order_id', $data) ? (int)$data['cart_order_id'] : -1;
			$subscription = null;
			if($id > 0) {
				$subscription = KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
					->id($id)
					->getItem();
				if( ($subscription->id <= 0) || ($subscription->id != $id) ) {
					$subscription = null;
					$isValid = false;
				}
			} else {
				$isValid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("cart_order_id" field) is invalid';
		}
		
		// Check that order_number has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->processor_key == $data['order_number']) {
				if($subscription->state == 'C') {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same order_number twice";
				}
			}
		}
		
		// Check that total is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['total']);
			$gross = $subscription->gross_amount;
			if($mc_gross > 0) {
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			} else {
				$valid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}
		
		// Log the IPN data
		$this->logIPN($data, $isValid);
		
		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;
		
		// Load the subscription level and get its slug
		$slug = KFactory::tmp('admin::com.akeebasubs.model.levels')
				->id($subscription->akeebasubs_level_id)
				->getItem()
				->slug;

		// Check the payment_status
		switch($data['credit_card_processed'])
		{
			case 'Y':
				$newStatus = 'C';
				$returnURL = rtrim(JURI::base(),'/').str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order'));
				break;

			default:
				$newStatus = 'X';
				$returnURL = rtrim(JURI::base(),'/').str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel'));
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'id'				=> $id,
			'processor_key'		=> $data['order_number'],
			'state'				=> $newStatus,
			'enabled'			=> 0
		);
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
			
			// Also update the user, enabling him
			KFactory::tmp('admin::com.akeebasubs.model.jusers')
				->id($subscription->user_id)
				->getItem()
				->setData(array(
					'block'		=> 0
				))->save();
		}
		$subscription->setData($updates)->save();
		
		// Finally, redirect the user
		$app = JFactory::getApplication();
		$app->redirect($returnURL);
		
		return true;
	}
	
	/**
	 * Validates the incoming data against the md5 posted by 2Checkout to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN($data)
	{
		$incoming_md5 = strtoupper($data['key']);
		
		$calculated_md5 = md5(
			$this->params->get('secret','').
			$this->params->get('sid','').
			$data['order_number'].
			$data['total']
		);
		$calculated_md5 = strtoupper($calculated_md5);
		
		return $calulated_md5 == $incoming_md5;
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
				$altLog = $logpath.DS.'akpayment_2checkout_ipn-1.php';
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