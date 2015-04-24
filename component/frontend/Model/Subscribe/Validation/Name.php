<?php
/**
 * Created by PhpStorm.
 * User: nikosdion
 * Date: 24/4/15
 * Time: 13:04
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;


use Akeeba\Subscriptions\Site\Model\Coupons;
use Akeeba\Subscriptions\Site\Model\Subscriptions;

class Name extends Base
{
	/**
	 * Get the validation result.
	 *
	 * @return  bool
	 */
	protected function getValidationResult()
	{
		$name = trim($this->state->name);

		if (empty($name))
		{
			return false;
		}

		// The name must contain AT LEAST two parts (name/surname) separated by a space
		$nameParts = explode(" ", $name);

		return count($nameParts) >= 2;
	}

}