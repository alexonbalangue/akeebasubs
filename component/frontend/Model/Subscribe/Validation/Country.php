<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\Select;

class Country extends Base
{
	/**
	 * Validates the Country field
	 *
	 * @return  bool
	 */
	protected function getValidationResult()
	{
		// Should I collect personal information? -1: only country, 0: none, 1: all
		$personalInfo = $this->container->params->get('personalinfo', 1);

		// I am told to not collect any personal information, the field is always valid
		if ($personalInfo == 0)
		{
			return true;
		}

		// Get the country
		$country = trim($this->state->country);

		// No country specified means it's invalid
		if (empty($country))
		{
			return false;
		}

		// Otherwise make sure it's one of the allowed countries
		$countries = Select::getFilteredCountries();
		return array_key_exists($country, $countries);
	}

}