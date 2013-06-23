<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');
JLoader::import('joomla.html.parameter');

/**
 * Akeeba Subscriptions payment plugin abstract class
 */
abstract class plgAkpaymentAbstract extends JPlugin
{
	/** @var string Name of the plugin, returned to the component */
	protected $ppName = 'abstract';

	/** @var string Translation key of the plugin's title, returned to the component */
	protected $ppKey = 'PLG_AKPAYMENT_ABSTRACT_TITLE';

	/** @var string Image path, returned to the component */
	protected $ppImage = '';

	public function __construct(&$subject, $config = array())
	{
		if(!is_object($config['params'])) {
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);

		if(array_key_exists('ppName', $config)) {
			$this->ppName = $config['ppName'];
		}

		if(array_key_exists('ppImage', $config)) {
			$this->ppImage = $config['ppImage'];
		}

		$name = $this->ppName;

		if(array_key_exists('ppKey', $config)) {
			$this->ppKey = $config['ppKey'];
		} else {
			$this->ppKey = "PLG_AKPAYMENT_{$name}_TITLE";
		}

		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';

		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_'.$name, JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_'.$name, JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_'.$name, JPATH_ADMINISTRATOR, null, true);
	}

	public final function onAKPaymentGetIdentity()
	{
		$title = $this->params->get('title','');
		if(empty($title)) $title = JText::_($this->ppKey);

		$image = trim($this->params->get('ppimage',''));
		if(empty($image)) {
			$image = $this->ppImage;
		}

		$ret = array(
			(object)array(
				'name'		=> $this->ppName,
				'title'		=> $title,
				'image'		=> $image
			)
		);

		return $ret;
	}

	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * "paymentForm" and a visible submit button.
	 *
	 * @param string $paymentmethod Check it against $this->ppName
	 * @param JUser $user
	 * @param AkeebasubsTableLevel $level
	 * @param AkeebasubsTableSubscription $subscription
	 * @return string
	 */
	abstract public function onAKPaymentNew($paymentmethod, $user, $level, $subscription);

	/**
	 * Processes a callback from the payment processor
	 *
	 * @param string $paymentmethod Check it against $this->ppName
	 * @param array $data Input data
	 */
	abstract public function onAKPaymentCallback($paymentmethod, $data);

	/**
	 * Fixes the starting and end dates when a payment is accepted after the
	 * subscription's start date. This works around the case where someone pays
	 * by e-Check on January 1st and the check is cleared on January 5th. He'd
	 * lose those 4 days without this trick. Or, worse, if it was a one-day pass
	 * the user would have paid us and we'd never given him a subscription!
	 *
	 * @param AkeebasubsTableSubscription $subscription
	 * @param array $updates
	 */
	protected function fixDates($subscription, &$updates)
	{
		// Take into account the params->fixdates data to determine when
		// the new subscription should start and/or expire the old subscription
		$subcustom = $subscription->params;
		if (is_string($subcustom))
		{
			$subcustom = json_decode($subcustom, true);
		}
		elseif (is_object($subcustom))
		{
			$subcustom = (array)$subcustom;
		}
		$oldsub = isset($subcustom['fixdates']['oldsub']) ? $subcustom['fixdates']['oldsub'] : null;
		$expiration = isset($subcustom['fixdates']['expiration']) ? $subcustom['fixdates']['expiration'] : 'overlap';
		$allsubs = isset($subcustom['fixdates']['allsubs']) ? $subcustom['fixdates']['allsubs'] : array();
		if (isset($subcustom['fixdates']))
		{
			unset($subcustom['fixdates']);
		}

		$mastertable = FOFTable::getAnInstance('Subscriptions', 'AkeebasubsTable');

		if (is_numeric($oldsub))
		{
			$sub = clone $mastertable;
			$sub->load($oldsub, true);
			if($sub->akeebasubs_subscription_id == $oldsub)
			{
				$oldsub = $sub;
			}
			else
			{
				$oldsub = null;
				$expiration = 'overlap';
			}
		}
		else
		{
			$oldsub = null;
			$expiration = 'overlap';
		}

		// Fix the starting date if the payment was accepted after the subscription's start date. This
		// works around the case where someone pays by e-Check on January 1st and the check is cleared
		// on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
		// the user would have paid us and we'd never given him a subscription!
		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
		if(!preg_match($regex, $subscription->publish_up)) {
			$subscription->publish_up = '2001-01-01';
		}
		if(!preg_match($regex, $subscription->publish_down)) {
			$subscription->publish_down = '2038-01-01';
		}
		$jNow = new JDate();
		$jStart = new JDate($subscription->publish_up);
		$jEnd = new JDate($subscription->publish_down);
		$now = $jNow->toUnix();
		$start = $jStart->toUnix();
		$end = $jEnd->toUnix();
		if (is_null($oldsub))
		{
			$oldsubstart = $now;
		}
		else
		{
			if(!preg_match($regex, $oldsub->publish_down))
			{
				$oldsubstart = $now;
			}
			else
			{
				$jOldsubstart = new JDate($oldsub->publish_down);
				$oldsubstart = $jOldsubstart->toUnix();
			}
		}

		if($start < $now) {
			if($end >= 2145916800) {
				// End date after 2038-01-01; forever subscription
				$start = $now;
			} else {
				// Get the subscription level and determine if this is a Fixed
				// Expiration subscription
				$nullDate = JFactory::getDbo()->getNullDate();
				$level = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
					->getItem($subscription->akeebasubs_level_id);
				$fixed_date = $level->fixed_date;

				if(!is_null($fixed_date) && !($fixed_date == $nullDate))
				{
					// Is the fixed date in the future?
					$jFixedDate = JFactory::getDate($fixed_date);
					if($now > $jFixedDate->toUnix())
					{
						// If the fixed date is in the past handle it as a regular subscription
						$fixed_date = null;
					}
				}

				if(is_null($fixed_date) || ($fixed_date == $nullDate))
				{
					// Regular subscription
					$duration = $end - $start;
					// Expiration = after => start date = end date of old sub
					if ($expiration == 'after')
					{
						$start = $oldsubstart;
					}
					// Expiration != after => start date = now
					else
					{
						$start = $now;
					}

					$end = $start + $duration;
				}
				else
				{
					// Fixed date subscription
					$start = $now;
					$end = $jFixedDate->toUnix();
				}
			}
			$jStart = new JDate($start);
			$jEnd = new JDate($end);
		}

		// Expiration = replace => expire old subscription
		if ($expiration == 'replace')
		{
			// Disable the primary subscription used to determine the subscription date
			$data = $oldsub->getData();
			$newdata = array_merge($data, array(
				'publish_down'	=> $jNow->toSql(),
				'enabled'		=> 0,
				'contact_flag'	=> 3,
				'notes'			=> $oldsub->notes . "\n\n" . "SYSTEM MESSAGE: This subscription was upgraded and replaced with {$subscription->akeeabsubs_subscription_id}\n"
			));
			$table = clone $mastertable;
			$table->reset();
			$table->save($newdata);

			// Disable all old subscriptions
			if (!empty($allsubs))
			{
				foreach($allsubs as $sub_id)
				{
					$table = clone $mastertable;
					$table->load($sub_id);

					if ($table->akeebasubs_level_id == $oldsub->akeebasubs_level_id)
					{
						// Don't try to disable the same subscription twice
						continue;
					}

					$data = $table->getData();
					$newdata = array_merge($data, array(
						'publish_down'	=> $jNow->toSql(),
						'enabled'		=> 0,
						'contact_flag'	=> 3,
						'notes'			=> $oldsub->notes . "\n\n" . "SYSTEM MESSAGE: This subscription was upgraded and replaced with {$subscription->akeeabsubs_subscription_id}\n"
					));
					$table->save($newdata);
				}
			}
		}

		$updates['publish_up'] = $jStart->toSql();
		$updates['publish_down'] = $jEnd->toSql();
		$updates['enabled'] = 1;
		$updates['params'] = json_encode($subcustom);
	}

	/**
	 * Logs the received IPN information to file
	 *
	 * @param array $data
	 * @param bool $isValid
	 */
	protected final function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}

		$logFilenameBase = $logpath.'/akpayment_'.strtolower($this->ppName).'_ipn';

		$logFile = $logFilenameBase.'.php';
		JLoader::import('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logFilenameBase.'-1.php';
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
		$pluginName = strtoupper($this->ppName);
		$logData .= $isValid ? 'VALID '.$pluginName.' IPN' : 'INVALID '.$pluginName.' IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
	
	/**
	 * Translates the given 2-digit country code into the 3-digit country code.
	 *
	 * @param string $country
	 */
	protected function translateCountry($country)
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
}