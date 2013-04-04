<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentDeltapay extends plgAkpaymentAbstract
{
	/**
	 * Maps currency three letter codes to number, as per ISO 4217
	 */
	private $currencyMap = array(
		'AED' => 784, 'AFN' => 971, 'ALL' => 8, 'AMD' => 51, 'ANG' => 532, 'AOA' => 973,
		'ARS' => 32, 'AUD' => 36, 'AWG' => 533, 'AZN' => 944, 'BAM' => 977, 'BBD' => 52,
		'BDT' => 50, 'BGN' => 975, 'BHD' => 48, 'BIF' => 108, 'BMD' => 60, 'BND' => 96,
		'BOB' => 68, 'BOV' => 984, 'BRL' => 986, 'BSD' => 44, 'BTN' => 64, 'BWP' => 72,
		'BYR' => 974, 'BZD' => 84, 'CAD' => 124, 'CDF' => 976, 'CHE' => 947, 'CHF' => 756,
		'CHW' => 948, 'CLF' => 990, 'CLP' => 152, 'CNY' => 156, 'COP' => 170, 'COU' => 970,
		'CRC' => 188, 'CUC' => 931, 'CUP' => 192, 'CVE' => 132, 'CZK' => 203, 'DJF' => 262,
		'DKK' => 208, 'DOP' => 214, 'DZD' => 12, 'EGP' => 818, 'ERN' => 232, 'ETB' => 230,
		'EUR' => 978, 'FJD' => 242, 'FKP' => 238, 'GBP' => 826, 'GEL' => 981, 'GHS' => 936,
		'GIP' => 292, 'GMD' => 270, 'GNF' => 324, 'GTQ' => 320, 'GYD' => 328, 'HKD' => 344,
		'HNL' => 340, 'HRK' => 191, 'HTG' => 332, 'HUF' => 348, 'IDR' => 360, 'ILS' => 376,
		'INR' => 356, 'IQD' => 368, 'IRR' => 364, 'ISK' => 352, 'JMD' => 388, 'JOD' => 400,
		'JPY' => 392, 'KES' => 404, 'KGS' => 417, 'KHR' => 116, 'KMF' => 174, 'KPW' => 408,
		'KRW' => 410, 'KWD' => 414, 'KYD' => 136, 'KZT' => 398, 'LAK' => 418, 'LBP' => 422,
		'LKR' => 144, 'LRD' => 430, 'LSL' => 426, 'LTL' => 440, 'LVL' => 428, 'LYD' => 434,
		'MAD' => 504, 'MDL' => 498, 'MGA' => 969, 'MKD' => 807, 'MMK' => 104, 'MNT' => 496,
		'MOP' => 446, 'MRO' => 478, 'MUR' => 480, 'MVR' => 462, 'MWK' => 454, 'MXN' => 484,
		'MXV' => 979, 'MYR' => 458, 'MZN' => 943, 'NAD' => 516, 'NGN' => 566, 'NIO' => 558,
		'NOK' => 578, 'NPR' => 524, 'NZD' => 554, 'OMR' => 512, 'PAB' => 590, 'PEN' => 604,
		'PGK' => 598, 'PHP' => 608, 'PKR' => 586, 'PLN' => 985, 'PYG' => 600, 'QAR' => 634,
		'RON' => 946, 'RSD' => 941, 'RUB' => 643, 'RWF' => 646, 'SAR' => 682, 'SBD' => 90,
		'SCR' => 690, 'SDG' => 938, 'SEK' => 752, 'SGD' => 702, 'SHP' => 654, 'SLL' => 694,
		'SOS' => 706, 'SRD' => 968, 'SSP' => 728, 'STD' => 678, 'SYP' => 760, 'SZL' => 748,
		'THB' => 764, 'TJS' => 972, 'TMT' => 934, 'TND' => 788, 'TOP' => 776, 'TRY' => 949,
		'TTD' => 780, 'TWD' => 901, 'TZS' => 834, 'UAH' => 980, 'UGX' => 800, 'USD' => 840,
		'USN' => 997, 'USS' => 998, 'UYI' => 940, 'UYU' => 858, 'UZS' => 860,  'VEF' => 937,
		'VND' => 704, 'VUV' => 548, 'WST' => 882, 'XXX' => 999, 'YER' => 886, 'ZAR' => 710,
		'ZMK' => 894, 'ZWL' => 932,
	);

	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'deltapay',
			'ppKey'			=> 'PLG_AKPAYMENT_DELTAPAY_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/alphalogo.gif',
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

		$currency = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
		if(array_key_exists($currency, $this->currencyMap)) {
			$currencyCode = $this->currencyMap[$currency];
		} else {
			$currencyCode = 999;
		}

		$data = (object)array(
			'url'			=> 'https://www.deltapay.gr/entry.asp',
			'merchant'		=> $this->params->get('merchant',''),
			//'postback'		=> rtrim(JURI::base(),'/').str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=callback&paymentmethod=deltapay')),
			'postback'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=deltapay',
			'success'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)),
			'cancel'		=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)),
			'currency'		=> $currencyCode,
			'firstname'		=> $firstName,
			'lastname'		=> $lastName,
			'charge'		=> str_replace('.',',',sprintf('%02.2f',$subscription->gross_amount))
		);

		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();

		// Step 1. Call the getGuid page
		$url = JURI::getInstance('https://www.deltapay.gr/getguid.asp');
		$reqData = array(
			'MerchantCode'		=> $data->merchant,
			'Charge'			=> $data->charge,
			'CurrencyCode'		=> $data->currency,
			'Installments'		=> 0,
			'TransactionType'	=> 1,
			'Param1'			=> $subscription->akeebasubs_subscription_id,
			'Param2'			=> $user->id,
		);
		$options = new JRegistry();
		$curl = new JHttpTransportCurl($options);
		$response = $curl->request('POST', $url, $reqData, null, 30);
		$guids = explode('<br>', $response->body);

		if (isset($guids[2]))
		{
			// An error occurred, use the legacy method
			$page = 'form.php';
		}
		else
		{
			// Store the GUIDs in the subscription record
			$subscription->save(array(
				'akeebasubs_subscription_id'	=> $subscription->akeebasubs_subscription_id,
				'processor_key'					=> 'GUID:' . $guids[0] . ':' . $guids[1],
			));
			// Use the newform.php page
			$page = 'newform.php';
			$data->guid = $guids[0];
		}

		@ob_start();
		include dirname(__FILE__).'/deltapay/form.php';
		$html = @ob_get_clean();

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;

		// Wow, there is no IPN check whatsoever! Amazing security level...
		$isValid = true;

		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('Param1', $data) ? (int)$data['Param1'] : -1;
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("Param1" field) is invalid';
		}

		// Check that the amount is correct
		if($isValid && !is_null($subscription)) {
			$charge = str_replace(',','.',$data['Charge']);
			$mc_gross = floatval($charge);
			$gross = $subscription->gross_amount;
			// Important: NEVER, EVER compare two floating point values for equality.
			$isValid = ($gross - $mc_gross) < 0.01;
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount (Charge field) does not match the subscription amount';
		}

		// Check the GUID, if it's used
		if($isValid && !is_null($subscription)) {
			$guidstring = $subscription->processor_key;
			if (!empty($guidstring) && (substr($guidstring,0,5) == 'GUID:'))
			{
				$guids = explode(':', $guidstring);
				$guid2 = $data['Guid2'];
				if ($guid2 != $guids[2])
				{
					$isValid = false;
					$data['akeebasubs_failure_reason'] = 'Unauthorised response (GUID mismatch)';
				}
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) die('Hacking attempt; payment processing refused');

		// Load the subscription level and get its slug
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;

		// Check the payment_status

		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}

		switch($data['Result'])
		{
			case '1': // Success
				$newStatus = 'C';
				$returnURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
				break;

			case '2': // Error
			case '3': // Cancelled
			default:
				$newStatus = 'X';
				$returnURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id));
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id'				=> $id,
			'processor_key'		=> $data['Param1'],
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

		$app = JFactory::getApplication();
		$app->redirect($returnURL);

		return true;
	}
}