<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 * @author		Janich Rasmussen <janich@gmail.com>
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentEpaydk extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'epaydk',
			'ppKey'			=> 'PLG_AKPAYMENT_EPAYDK_TITLE',
			'ppImage'		=> 'http://tech.epay.dk/kb_upload/image/epay_logos/uk.gif',
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
		if ($paymentmethod != $this->ppName) return false;
		
		$nameParts = explode(' ', $user->name, 2);
		$firstName = $nameParts[0];
		if (count($nameParts) > 1) {
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
		if (!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		// Separate URL variable as it cannot be a part of the md5 checksum
		$url = $this->getPaymentURL();
		
		$data = array(
			'merchant'			=> $this->getMerchantID(),
			'success'			=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel'			=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'postback'			=> JURI::base() . 'index.php?option=com_akeebasubs&view=callback&paymentmethod=epaydk',
			'orderid'			=> $subscription->akeebasubs_subscription_id,
			'currency'			=> strtoupper(AkeebasubsHelperCparams::getParam('currency', 'EUR')),
			'amount'			=> ($subscription->gross_amount * 100),		// Epay calculates in minor amounts, and doesn't support tax differentation
			'cardtypes'			=> implode(',', $this->params->get('cardtypes', array())),
			'instantcapture'	=> '1',
			'instantcallback'	=> '1',
			'language'			=> $this->params->get('language', '0'),
			'ordertext'			=> $level->title . ' - [ ' . $user->username . ' ]',
			'windowstate'		=> '3',
			'ownreceipt'		=> '0',
			'md5'				=> $this->params->get('secret','')										// Will be overriden with md5sum checksum
		);
		
		if ($this->params->get('md5', 1)) {
			// Security hash - must be compiled from ALL inputs sent
			$data['md5'] = md5(implode('', $data));
		}
		else {
			$data['md5'] = '';
		}
		
		// Set array as object for compatability
		$data = (object) $data;
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		@ob_start();
		
		$cardtypes = array();
		include dirname(__FILE__).'/epaydk/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if ($paymentmethod != $this->ppName) return false;
		
		// Check return values for md5 security hash for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidRequest($data);
		if (!$isValid) {
			$data['akeebasubs_failure_reason'] = 'Epay reports transaction as invalid';
		}
		
		/*
		fraud
		payercountry
		issuercountry
		subscriptionid
		*/
		
		// Check paymenttype; we only accept '3' (normal payments) with this plugin
		/*
		if ($isValid) {
			$validTypes = array('3');
			
			$isValid = in_array($data['paymenttype'], $validTypes);
			
			if (!$isValid) {
				$data['akeebasubs_failure_reason'] = "Transaction type ".$data['paymenttype']." can't be processed by this payment plugin.";
			}
		}
		*/
		
		// Load the relevant subscription row
		if ($isValid) {
			$id = array_key_exists('orderid', $data) ? (int) $data['orderid'] : -1;
			$subscription = null;
			if ($id > 0) {
				$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->setId($id)
					->getItem();
				if ( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id) ) {
					$subscription = null;
					$isValid = false;
				}
			}
			else {
				$isValid = false;
			}
			
			if (!$isValid) {
				$data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("orderid" field) is invalid';
			}
		}
		
		// Check that receiver_email / receiver_id is what the site owner has configured
		/*
		if ($isValid) {
			$receiver_email = $data['receiver_email'];
			$receiver_id = $data['receiver_id'];
			$valid_id = $this->getMerchantID();
			$isValid =
				($receiver_email == $valid_id)
				|| (strtolower($receiver_email) == strtolower($receiver_email))
				|| ($receiver_id == $valid_id)
				|| (strtolower($receiver_id) == strtolower($receiver_id))
			;
			if (!$isValid) $data['akeebasubs_failure_reason'] = 'Merchant ID does not match receiver_email or receiver_id';
		}
		*/
		
		// Check that amount is correct
		$isPartialRefund = false;
		if ($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['amount'] / 100);	// Epay uses minor values
			
			$gross = $subscription->gross_amount;
			if ($mc_gross > 0) {
				// A positive value means "payment". The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			}
			else {
				$isPartialRefund = false;
				$temp_mc_gross = -1 * $mc_gross;
				$isPartialRefund = ($gross - $temp_mc_gross) > 0.01;
			}
			
			if (!$isValid) {
				$data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}
		
		// Check that txnid has not been previously processed
		if ($isValid && !is_null($subscription) && !$isPartialRefund) {
			if ($subscription->processor_key == $data['txnid']) {
				if ($subscription->state == 'C') {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same txnid twice";
				}
			}
		}
		
		// Check that currency is correct
		// NOTE: Epay returns a code (int) that represents currency (though they accept a string in the form!)
		if ($isValid && !is_null($subscription)) {
			$epay_currency_codes = array('4'=>'AFA','8'=>'ALL','12'=>'DZD','20'=>'ADP','31'=>'AZM','32'=>'ARS','36'=>'AUD','44'=>'BSD','48'=>'BHD','50'=>'BDT','51'=>'AMD','52'=>'BBD','60'=>'BMD','64'=>'BTN','68'=>'BOB','72'=>'BWP','84'=>'BZD','90'=>'SBD','96'=>'BND','100'=>'BGL','104'=>'MMK','108'=>'BIF','116'=>'KHR','124'=>'CAD','132'=>'CVE','136'=>'KYD','144'=>'LKR','152'=>'CLP','156'=>'CNY','170'=>'COP','174'=>'KMF','188'=>'CRC','191'=>'HRK','192'=>'CUP','196'=>'CYP','203'=>'CZK','208'=>'DKK','214'=>'DOP','218'=>'ECS','222'=>'SVC','230'=>'ETB','232'=>'ERN','233'=>'EEK','238'=>'FKP','242'=>'FJD','262'=>'DJF','270'=>'GMD','288'=>'GHC','292'=>'GIP','320'=>'GTQ','324'=>'GNF','328'=>'GYD','332'=>'HTG','340'=>'HNL','344'=>'HKD','348'=>'HUF','352'=>'ISK','356'=>'INR','360'=>'IDR','364'=>'IRR','368'=>'IQD','376'=>'ILS','388'=>'JMD','392'=>'JPY','398'=>'KZT','400'=>'JOD','404'=>'KES','408'=>'KPW','410'=>'KRW','414'=>'KWD','417'=>'KGS','418'=>'LAK','422'=>'LBP','426'=>'LSL','428'=>'LVL','430'=>'LRD','434'=>'LYD','440'=>'LTL','446'=>'MOP','450'=>'MGF','454'=>'MWK','458'=>'MYR','462'=>'MVR','470'=>'MTL','478'=>'MRO','480'=>'MUR','484'=>'MXN','496'=>'MNT','498'=>'MDL','504'=>'MAD','508'=>'MZM','512'=>'OMR','516'=>'NAD','524'=>'NPR','532'=>'ANG','533'=>'AWG','548'=>'VUV','554'=>'NZD','558'=>'NIO','566'=>'NGN','578'=>'NOK','586'=>'PKR','590'=>'PAB','598'=>'PGK','600'=>'PYG','604'=>'PEN','608'=>'PHP','624'=>'GWP','626'=>'TPE','634'=>'QAR','642'=>'ROL','643'=>'RUB','646'=>'RWF','654'=>'SHP','678'=>'STD','682'=>'SAR','690'=>'SCR','694'=>'SLL','702'=>'SGD','703'=>'SKK','704'=>'VND','705'=>'SIT','706'=>'SOS','710'=>'ZAR','716'=>'ZWD','736'=>'SDD','740'=>'SRG','748'=>'SZL','752'=>'SEK','756'=>'CHF','760'=>'SYP','764'=>'THB','776'=>'TOP','780'=>'TTD','784'=>'AED','788'=>'TND','792'=>'TRL','795'=>'TMM','800'=>'UGX','807'=>'MKD','810'=>'RUR','818'=>'EGP','826'=>'GBP','834'=>'TZS','840'=>'USD','858'=>'UYU','860'=>'UZS','862'=>'VEB','886'=>'YER','891'=>'YUM','894'=>'ZMK','901'=>'TWD','949'=>'TRY','950'=>'XAF','951'=>'XCD','952'=>'XOF','953'=>'XPF','972'=>'TJS','973'=>'AOA','974'=>'BYR','975'=>'BGN','976'=>'CDF','977'=>'BAM','978'=>'EUR','979'=>'MXV','980'=>'UAH','981'=>'GEL','983'=>'ECV','984'=>'BOV','985'=>'PLN','986'=>'BRL','990'=>'CLF');
			
			$mc_currency = $data['currency'];
			$currency = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
			
			if (array_key_exists($mc_currency, $epay_currency_codes)) {
				$mc_currency = strtoupper($epay_currency_codes[$mc_currency]);
				
				if ($mc_currency != $currency) {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "Invalid currency; expected $currency, got $mc_currency";
				}
			}
			else {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invalid currency; expected $currency, got $mc_currency";
			}
		}
		
		// Log the IPN data
		$this->logIPN($data, $isValid);
		
		// Fraud attempt? Do nothing more!
		if (!$isValid) return false;

		/*
if (isset($_GET["error"])) {
	if (strlen($_GET['error']) > 0) {
?>
		<br>
		<font color="red">
			<?php echo $VM_LANG->_('VM_CHECKOUT_EPAY_INTEGRATED_PAYMENT_FAILURE_1'); ?>&nbsp;
			<?php echo $_GET['error']; ?>.&nbsp;
			<?php echo $VM_LANG->_('VM_CHECKOUT_EPAY_INTEGRATED_PAYMENT_FAILURE_2'); ?>
			&nbsp;
			<?php echo htmlentities($_GET['errortext']); ?>.
		</font>
		<br><br>
		<b><?php echo $VM_LANG->_('VM_CHECKOUT_EPAY_INTEGRATED_PAYMENT_FAILURE_3'); ?></b>
		<br><br>
<?php		
	}
}
*/
		// Check the payment_status
		if (isset($data['error']) && strlen($data['error']) > 0) {
			if ($isPartialRefund) {
				$newStatus = 'C';
			}
			else {
				$newStatus = 'X';
			}
		}
		else {
			$newStatus = 'C';
		}
		
		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id'	=> $id,
			'processor_key'					=> $data['txnid'],
			'state'							=> $newStatus,
			'enabled'						=> 0
		);
		
		JLoader::import('joomla.utilities.date');
		if ($newStatus == 'C') {
			$this->fixDates($subscription, $updates);
		}
		
		/*
		// In the case of a successful recurring payment, fetch the old subscription's data
		if (($newStatus == 'C') && ($subscription->state == 'C')) {
			$oldData = $subscription->getData();
			unset($oldData['akeebasubs_subscription_id']);
			$oldData['publish_down'] = $jNow->toMySQL();
			$oldData['enabled'] = 0;
			if (empty($oldData['notes'])) $oldData['notes'] = '';
			$oldData['notes'] .= "\n\nAutomatically renewed subscription on ".$jNow->toMySQL();
		}
		*/
		
		// Save the changes
		$subscription->save($updates);
		
		/*
		// In the case of a successful recurring payment, store the old subscription's data to a new record
		if ($recurring && isset($oldData)) {
			$original = clone $subscription;
			$original->reset();
			$original->bind($oldData);
			$original->store();
		}
		*/
		
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
		$sandbox = $this->params->get('sandbox', 0);
		if ($sandbox) {
			// return different url if Epay ever changes
		}
		
		return 'https://ssl.ditonlinebetalingssystem.dk/integration/ewindow/Default.aspx';
	}
	
	
	/**
	 * Gets the Epay Merchant ID (usually digits only)
	 */
	private function getMerchantID()
	{
		$sandbox = $this->params->get('sandbox', 0);
		if ($sandbox) {
			return $this->params->get('sandbox_merchant', '');
		}
		
		return $this->params->get('merchant', '');
	}
	
	
	/**
	 * Validates the incoming data against Epay's security hash to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidRequest($data)
	{
		if ($this->params->get('md5', 1)) {
			// Temp. replace hash with secret
			$hash = $data['hash'];
			$data['hash'] = $this->params->get('secret', '');
			
			// Calculate checksum
			$checksum = md5(implode('', $data));
			
			// Replace hash with original
			$data['hash'] = $hash;
			
			if ($checksum != $hash) {
				return false;
			}
		}
		
		return true;
	}
	
	private function _toPPDuration($days)
	{
		$ret = (object)array(
			'unit'		=> 'D',
			'value'		=> $days
		);

		// 0-90 => return days
		if ($days < 90) return $ret;

		// Translate to weeks, months and years
		$weeks = (int)($days / 7);
		$months = (int)($days / 30);
		$years = (int)($days / 365);

		// Find which one is the closest match
		$deltaW = abs($days - $weeks*7);
		$deltaM = abs($days - $months*30);
		$deltaY = abs($days - $years*365);
		$minDelta = min($deltaW, $deltaM, $deltaY);

		// Counting weeks gives a better approximation
		if ($minDelta == $deltaW) {
			$ret->unit = 'W';
			$ret->value = $weeks;

			// Make sure we have 1-52 weeks, otherwise go for a months or years
			if (($ret->value > 0) && ($ret->value <= 52)) {
				return $ret;
			} else {
				$minDelta = min($deltaM, $deltaY);
			}
		}

		// Counting months gives a better approximation
		if ($minDelta == $deltaM) {
			$ret->unit = 'M';
			$ret->value = $months;

			// Make sure we have 1-24 month, otherwise go for years
			if (($ret->value > 0) && ($ret->value <= 24)) {
				return $ret;
			} else {
				$minDelta = min($deltaM, $deltaY);
			}
		}

		// If we're here, we're better off translating to years
		$ret->unit = 'Y';
		$ret->value = $years;

		if ($ret->value < 0) {
			// Too short? Make it 1 (should never happen)
			$ret->value = 1;
		} elseif ($ret->value > 5) {
			// One major pitfall. You can't have renewal periods over 5 years.
			$ret->value = 5;
		}

		return $ret;
	}
}