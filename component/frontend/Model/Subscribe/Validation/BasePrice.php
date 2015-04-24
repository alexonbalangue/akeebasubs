<?php
/**
 * Created by PhpStorm.
 * User: nikosdion
 * Date: 24/4/15
 * Time: 13:04
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;


use Akeeba\Subscriptions\Site\Model\Levels;
use Akeeba\Subscriptions\Site\Model\Subscriptions;

class BasePrice extends Base
{
	/**
	 * Get the base price including the sign-up fee and any price modifiers set by the plugins
	 *
	 * @return  float
	 */
	protected function getValidationResult()
	{
		// Get the subscription level and the default sign-up fee
		/** @var Levels $level */
		$level = $this->container->factory->model('Levels')->tmpInstance();
		$level->find($this->state->id);
		$signup_fee = $level->signupfee;

		// If the user is already a subscriber to this level do not charge a sign-up fee
		$subIDs = array();
		$user = \JFactory::getUser();

		if ($user->id)
		{
			/** @var Subscriptions $subscriptionsModel */
			$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();
			$mysubs = $subscriptionsModel
				->user_id($user->id)
				->paystate('C')
				->get(true);

			if ($mysubs->count())
			{
				foreach ($mysubs as $sub)
				{
					$subIDs[] = $sub->akeebasubs_level_id;
				}
			}

			$subIDs = array_unique($subIDs);

			if (in_array($level->akeebasubs_level_id, $subIDs))
			{
				$signup_fee = 0;
			}
		}

		// Get the default price value
		$netPrice = (float)$level->price + (float)$signup_fee;

		// Net price modifiers (via plugins)
		$price_modifier = 0;

		$this->container->platform->importPlugin('akeebasubs');
		$this->container->platform->importPlugin('akpayment');

		$priceValidationData = array_merge(
			(array)$this->state, array(
				'level' => $level,
				'netprice' => $netPrice
			)
		);

		$jResponse = $this->container->platform->runPlugins('onValidateSubscriptionPrice', array(
				(object)$priceValidationData)
		);

		if (is_array($jResponse) && !empty($jResponse))
		{
			foreach ($jResponse as $pluginResponse)
			{
				if (empty($pluginResponse))
				{
					continue;
				}

				$price_modifier += $pluginResponse;
			}
		}

		$netPrice += $price_modifier;

		return $netPrice;
	}

}