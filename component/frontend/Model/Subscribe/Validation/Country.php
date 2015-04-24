<?php
/**
 * Created by PhpStorm.
 * User: nikosdion
 * Date: 24/4/15
 * Time: 13:04
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;


use Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use Akeeba\Subscriptions\Admin\Helper\Select;

class Country extends Base
{
	/**
	 * Get the validation result.
	 *
	 * @return  bool
	 */
	protected function getValidationResult()
	{
		// Should I collect personal information? -1: only country, 0: none, 1: all
		$personalInfo = ComponentParams::getParam('personalinfo', 1);

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
		return array_key_exists($country, Select::$countries);
	}

}