<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

defined('_JEXEC') or die;

class BestUpgradeDiscount extends Base
{
	/**
	 * Get the maximum discount from applying upgrade rules for active and expired subscriptions
	 *
	 * Uses:
	 * 		UpgradeDiscount
	 * 		UpgradeExpiredDiscount
	 *
	 * @return  array  upgrade_id, value
	 */
	protected function getValidationResult()
	{
		// Get the automatic discount based on upgrade rules for active and expired subscriptions
		$upgradeRule = $this->factory->getValidator('UpgradeDiscount')->execute();
		$upgradeExpiredRule = $this->factory->getValidator('UpgradeExpiredDiscount')->execute();

		if ($upgradeRule['value'] > $upgradeExpiredRule['value'])
		{
			return $upgradeRule;
		}

		return $upgradeExpiredRule;
	}

}