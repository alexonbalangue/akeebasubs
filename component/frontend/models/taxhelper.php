<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/helpers/euvatinfo.php';
JLoader::import('joomla.cms.model.legacy');

class AkeebasubsModelTaxhelper extends F0FModel
{
	protected static $cachedTaxRates = array();

	/**
	 * Returns a set of parameters used to determine the applicable tax amount
	 *
	 * @return  array
	 */
	public function getTaxDefiningParameters()
	{
		$result = array(
			'country'	=> 'XX',
			'state'		=> '',
			'city'		=> '',
			'vies'		=> 0,
		);

		// Get country from GeoIP
		$result['country'] = $this->getCountryFromGeoIP();

		// Get information from Akeeba Subscriptions user record
		$user = JFactory::getUser();

		if (!$user->guest && $user->id)
		{
			$userModel = F0FModel::getTmpInstance('Users', 'AkeebasubsModel');
			$userparams = $userModel
				->user_id($user->id)
				->getMergedData($user->id);

			if ($userparams->country)
			{
				$result['country'] = $userparams->country;
			}

			if ($userparams->state)
			{
				$result['state'] = $userparams->state;
			}

			if ($userparams->viesregistered && $userparams->isbusiness)
			{
				$result['vies'] = 1;
			}
		}

		// Get information from the VAT dropdown module
		$session = JFactory::getSession();
		$moduleCountry = $session->get('country', null, 'mod_akeebasubscountry');

		if (!empty($moduleCountry))
		{
			$result['country'] = $moduleCountry;
		}

		return $result;
	}

	/**
	 * Finds the applicable tax rule for a given set of parameters
	 *
	 * @param   int     $akeebasubs_level_id  The subscription level to get the rule for. 0 looks for "All levels" rules.
	 * @param   string  $country              The country code, e.g. 'DE' for Germany
	 * @param   string  $state                The state of the client
	 * @param   string  $city                 The city of the client
	 * @param   int     $vies                 Is the user VIES-registered?
	 *
	 * @return  object  Information object. The taxrate property has the effective tax rate in percentage points.
	 */
	public function getTaxRule($akeebasubs_level_id = 0, $country = 'XX', $state = '', $city = '', $vies = 0)
	{
		$hash = (int) $akeebasubs_level_id . '_' . strtoupper($country) . '_' . strtoupper($state) . '_' .
			strtoupper($city) . '_' . (int) $vies;

		if (!array_key_exists($hash, self::$cachedTaxRates))
		{
			// First try loading the rules for this level
			$taxrules = F0FModel::getTmpInstance('Taxrules', 'AkeebasubsModel')
				->savestate(0)
				->enabled(1)
				->akeebasubs_level_id($akeebasubs_level_id)
				->filter_order('ordering')
				->filter_order_Dir('ASC')
				->limit(0)
				->limitstart(0)
				->getList();

			// If this level has no rules use the "All levels" rules
			if (empty($taxrules) && ($akeebasubs_level_id != 0))
			{
				self::$cachedTaxRates[$hash] = $this->getTaxRule(0, $country, $state, $city, $vies);

				return self::$cachedTaxRates[$hash];
			}

			$bestTaxRule = (object)array(
				'match'   => 0,	// How many parameters matched exactly
				'fuzzy'   => 0,	// How many parameters matched fuzzily
				'taxrate' => 0, // Tax rate in percentage points (e.g. 12.3 means 12.3% tax)
				'id'	  => 0, // The ID of the tax rule in effect
			);

			if (empty($taxrules))
			{
				return $bestTaxRule;
			}

			foreach ($taxrules as $rule)
			{
				// For each rule, get the match and fuzziness rating. The best, least fuzzy and last match wins.
				$match = 0;
				$fuzzy = 0;

				if (empty($rule->country))
				{
					$match++;
					$fuzzy++;
				}
				elseif ($rule->country == $country)
				{
					$match++;
				}

				if (empty($rule->state))
				{
					$match++;
					$fuzzy++;
				}
				elseif ($rule->state == $state)
				{
					$match++;
				}

				if (empty($rule->city))
				{
					$match++;
					$fuzzy++;
				}
				elseif (strtolower(trim($rule->city)) == strtolower(trim($city)))
				{
					$match++;
				}

				if (($rule->vies && $vies) || (!$rule->vies && !$vies))
				{
					$match++;
				}

				if (
					($match > $bestTaxRule->match) ||
					(($bestTaxRule->match == $match) && ($fuzzy < $bestTaxRule->fuzzy))
				)
				{
					if ($match == 0)
					{
						continue;
					}

					$bestTaxRule->match = $match;
					$bestTaxRule->fuzzy = $fuzzy;
					$bestTaxRule->taxrate = $rule->taxrate;
					$bestTaxRule->id = $rule->akeebasubs_taxrule_id;
				}
			}

			self::$cachedTaxRates[$hash] = $bestTaxRule;
		}

		return self::$cachedTaxRates[$hash];
	}

	/**
	 * Returns the country of the current user using GeoIP detection, as long as we can get their IP from the server,
	 * the GeoIP Provider plugin is installed and returns results for that IP.
	 *
	 * @return  string  The country code or "XX" when no detection was possible.
	 */
	private function getCountryFromGeoIP()
	{
		$country = 'XX';

		// If the GeoIP provider is not loaded return "XX" (no country detected)
		if (!class_exists('AkeebaGeoipProvider'))
		{
			return $country;
		}

		// Get the IP from the server
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

		// If we have a newer FOF version, use it to get the correct IP address of the client
		if (class_exists('F0FUtilsIp'))
		{
			$ip = F0FUtilsIp::getIp();
		}

		// Use GeoIP to detect the country
		$geoip = new AkeebaGeoipProvider();
		$country = $geoip->getCountryCode($ip);

		// If detection failed, return "XX" (no country detected)
		if (empty($country))
		{
			$country = 'XX';
		}

		return $country;
	}
}