<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentSkrill extends JPlugin
{
	private $ppName = 'skrill';
	private $ppKey = 'PLG_AKPAYMENT_SKRILL_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		
		// Load the language files
		$jlang =& JFactory::getLanguage();
		$jlang->load('plg_akpayment_skrill', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_skrill', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_skrill', JPATH_ADMINISTRATOR, null, true);
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

		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
				
		$data = (object)array(
			'url'			=> $this->getPaymentURL(),
			'merchant'		=> $this->params->get('merchant',''),
			'postback'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=paypal',
			'success'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'firstname'		=> $firstName,
			'lastname'		=> $lastName,
			'country'		=> $this->translateCountry($kuser->country)
		);
		
		@ob_start();
		include dirname(__FILE__).'/skrill/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	private function translateCountry($country)
	{
		$countryMap = array(
			'AX' => 'ALA', 'AF' => 'AFG', 'AL' => 'ALB', 'DZ' => 'DZA', 'AS' => 'ASM',
			'AD' => 'AND', 'AO' => 'AGO', 'AI' => 'AIA', 'AQ' => 'ATA', 'AG' => 'ATG',
			'AR' => 'ARG', 'AM' => 'ARM', 'AW' => 'ABW', 'AU' => 'AUS', 'AT' => 'AUT',
			'AZ' => 'AZE', 'BS' => 'BHS', 'BH' => 'BHR', 'BD' => 'BGD', 'BB' => 'BRB',
			'BY' => 'BLR', 'BE' => 'BEL', 'BZ' => 'BLZ', 'BJ' => 'BEN', 'BM' => 'BMU',
			'BT' => 'BTN', 'BO' => 'BOL', 'BA' => 'BIH', 'BW' => 'BWA', 'BV' => 'BVT',
			'BR' => 'BRA', 'IO' => 'IOT', 'BN' => 'BRN', 'BG' => 'BGR', 'BF' => 'BFA',
			'BI' => 'BDI', 'KH' => 'KHM', 'CM' => 'CMR', 'CA' => 'CAN', 'CV' => 'CPV',
			'KY' => 'CYM', 'CF' => 'CAF', 'TD' => 'TCD', 'CL' => 'CHL', 'CN' => 'CHN',
			'CX' => 'CXR', 'CC' => 'CCK', 'CO' => 'COL', 'KM' => 'COM', 'CD' => 'COD',
			'CG' => 'COG', 'CK' => 'COK', 'CR' => 'CRI', 'CI' => 'CIV', 'HR' => 'HRV',
			'CU' => 'CUB', 'CY' => 'CYP', 'CZ' => 'CZE', 'DK' => 'DNK', 'DJ' => 'DJI',
			'DM' => 'DMA', 'DO' => 'DOM', 'EC' => 'ECU', 'EG' => 'EGY', 'SV' => 'SLV',
			'GQ' => 'GNQ', 'ER' => 'ERI', 'EE' => 'EST', 'ET' => 'ETH', 'FK' => 'FLK',
			'FO' => 'FRO', 'FJ' => 'FJI', 'FI' => 'FIN', 'FR' => 'FRA', 'GF' => 'GUF',
			'PF' => 'PYF', 'TF' => 'ATF', 'GA' => 'GAB', 'GM' => 'GMB', 'GE' => 'GEO',
			'DE' => 'DEU', 'GH' => 'GHA', 'GI' => 'GIB', 'GR' => 'GRC', 'GL' => 'GRL',
			'GD' => 'GRD', 'GP' => 'GLP', 'GU' => 'GUM', 'GT' => 'GTM', 'GN' => 'GIN',
			'GW' => 'GNB', 'GY' => 'GUY', 'HT' => 'HTI', 'HM' => 'HMD', 'HN' => 'HND',
			'HK' => 'HKG', 'HU' => 'HUN', 'IS' => 'ISL', 'IN' => 'IND', 'ID' => 'IDN',
			'IR' => 'IRN', 'IQ' => 'IRQ', 'IE' => 'IRL', 'IL' => 'ISR', 'IT' => 'ITA',
			'JM' => 'JAM', 'JP' => 'JPN', 'JO' => 'JOR', 'KZ' => 'KAZ', 'KE' => 'KEN',
			'KI' => 'KIR', 'KP' => 'PRK', 'KR' => 'KOR', 'KW' => 'KWT', 'KG' => 'KGZ',
			'LA' => 'LAO', 'LV' => 'LVA', 'LB' => 'LBN', 'LS' => 'LSO', 'LR' => 'LBR',
			'LY' => 'LBY', 'LI' => 'LIE', 'LT' => 'LTU', 'LU' => 'LUX', 'MO' => 'MAC',
			'MK' => 'MKD', 'MG' => 'MDG', 'MW' => 'MWI', 'MY' => 'MYS', 'MV' => 'MDV',
			'ML' => 'MLI', 'MT' => 'MLT', 'MH' => 'MHL', 'MQ' => 'MTQ', 'MR' => 'MRT',
			'MU' => 'MUS', 'YT' => 'MYT', 'MX' => 'MEX', 'FM' => 'FSM', 'MD' => 'MDA',
			'MC' => 'MCO', 'MN' => 'MNG', 'MS' => 'MSR', 'MA' => 'MAR',	'MZ' => 'MOZ',
			'MM' => 'MMR', 'NA' => 'NAM', 'NR' => 'NRU', 'NP' => 'NPL', 'NL' => 'NLD',
			'AN' => 'ANT', 'NC' => 'NCL', 'NZ' => 'NZL', 'NI' => 'NIC',	'NE' => 'NER',
			'NG' => 'NGA', 'NU' => 'NIU','NF' => 'NFK',	'MP' => 'MNP',	'NO' => 'NOR',
			'OM' => 'OMN','PK' => 'PAK','PW' => 'PLW',	'PS' => 'PSE',	'PA' => 'PAN',
			'PG' => 'PNG','PY' => 'PRY','PE' => 'PER','PH' => 'PHL','PN' => 'PCN',
			'PL' => 'POL','PT' => 'PRT','PR' => 'PRI','QA' => 'QAT','RE' => 'REU',
			'RO' => 'ROU','RU' => 'RUS','RW' => 'RWA','SH' => 'SHN','KN' => 'KNA',
			'LC' => 'LCA','PM' => 'SPM','VC' => 'VCT','WS' => 'WSM','SM' => 'SMR',
			'ST' => 'STP','SA' => 'SAU','SN' => 'SEN','CS' => 'SCG','SC' => 'SYC',
			'SL' => 'SLE','SG' => 'SGP','SK' => 'SVK','SI' => 'SVN','SB' => 'SLB',
			'SO' => 'SOM','ZA' => 'ZAF','GS' => 'SGS','ES' => 'ESP','LK' => 'LKA',
			'SD' => 'SDN','SR' => 'SUR','SJ' => 'SJM','SZ' => 'SWZ','SE' => 'SWE',
			'CH' => 'CHE','SY' => 'SYR','TW' => 'TWN','TJ' => 'TJK','TZ' => 'TZA',
			'TH' => 'THA','TL' => 'TLS','TG' => 'TGO','TK' => 'TKL','TO' => 'TON',
			'TT' => 'TTO','TN' => 'TUN','TR' => 'TUR','TM' => 'TKM','TC' => 'TCA',
			'TV' => 'TUV','UG' => 'UGA','UA' => 'UKR','AE' => 'ARE','GB' => 'GBR',
			'US' => 'USA','UM' => 'UMI','UY' => 'URY','UZ' => 'UZB','VU' => 'VUT',
			'VA' => 'VAT','VE' => 'VEN','VN' => 'VNM','VG' => 'VGB','VI' => 'VIR',
			'WF' => 'WLF','EH' => 'ESH','YE' => 'YEM','ZM' => 'ZMB','ZW' => 'ZWE'
		);
		
		if(array_key_exists($country, $countryMap)) {
			return $countryMap[$country];
		} else {
			return '';
		}
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		jimport('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'MD5 hash does not validate';
		
		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('transaction_id', $data) ? (int)$data['custom'] : -1;
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("transaction_id" field) is invalid';
		}
		
		// Check that receiver is what the site owner has configured
		if($isValid) {
			$receiver_email = $data['pay_to_email'];
			$valid_id = $this->params->get('merchant','');
			$isValid =
				($receiver_email == $valid_id)
				|| (strtolower($receiver_email) == strtolower($receiver_email));
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Merchant ID does not match pay_to_email';
		}
		
		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['amount']);
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
		
		// Check that mb_transaction_id has not been previously processed
		if($isValid && !is_null($subscription) && !$isPartialRefund) {
			if($subscription->processor_key == $data['mb_transaction_id']) {
				if($subscription->state == 'C') {
					$isValid = false;
					$data['akeebasubs_failure_reason'] = "I will not process the same mb_transaction_id twice";
				}
			}
		}
		
		// Check that currency is correct
		if($isValid && !is_null($subscription)) {
			$mc_currency = strtoupper($data['currency']);
			$currency = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
			if($mc_currency != $currency) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Invalid currency; expected $currency, got $mc_currency";
			}
		}
		
		// Log the IPN data
		$this->logIPN($data, $isValid);
		
		// Fraud attempt? Do nothing more!
		if(!$isValid) return false;

		// Check the payment_status
		switch($data['status'])
		{
			case '2':
				$newStatus = 'C';
				break;
			
			case '0':
				$newStatus = 'P';
				break;

			case '-3':
				if($isPartialRefund) {
					$newStatus = 'C';
				} else {
					$newStatus = 'X';
				}
				break;
			
			case '-1':
			case '-2':
			default:
				$newStatus = 'X';
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'		=> $data['txn_id'],
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
	 * Validates the incoming data against PayPal's IPN to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN($data)
	{
		$secret = $this->params->get('secret','');
		$secret_md5 = strtoupper(md5($secret));
		
		$signature_string = $data['merchant_id'] . $data['transaction_id'] .
			$secret_md5 . $data['mb_amount'] . $data['mb_currency'] .
			$data['status'];
			
		$calculated = strtoupper(md5($signature_string));
		$incoming = strtoupper($data['md5sig']);
		
		return ($calculated == $incoming);
	}	
	
	/**
	 * Gets the form action URL for the payment
	 */
	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'http://www.moneybookers.com/app/test_payment.pl';
		} else {
			return 'https://www.moneybookers.com/app/payment.pl';
		}
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		$logpath = $config->getValue('log_path');
		$logFile = $logpath.'/akpayment_skrill_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_skrill_ipn-1.php';
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
		$logData .= $isValid ? 'VALID SKRILL IPN' : 'INVALID SKRILL IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}