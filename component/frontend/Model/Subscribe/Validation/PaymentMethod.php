<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Site\Model\PaymentMethods;

defined('_JEXEC') or die;

class PaymentMethod extends Base
{
	/**
	 * Validate the Payment method
	 *
	 * @return  bool
	 */
	protected function getValidationResult()
	{
		$paymentmethod = trim($this->state->paymentmethod);

		if (empty($paymentmethod))
		{
			return false;
		}

		// I have to access to the plugin params, so I have to load them all and pick the correct one
		/** @var PaymentMethods $pluginsModel */
		$pluginsModel = $this->container->factory->model('PaymentMethods')->tmpInstance();

		//First of all, let's get the whole list of plugins
		$plugins = $pluginsModel->getPaymentPlugins();
		$current = false;

		foreach($plugins as $plugin)
		{
			if($plugin->name == $paymentmethod)
			{
				$current = $plugin;
				break;
			}
		}

		// I wasn't able to find the payment method? Let's stop here
		if(!$current)
		{
			return false;
		}

		$country = $this->state->country;

		// These two if statements are split so we can better understand what's going on
		// Inclusion list and the country is in the list
		if($current->activeCountries['type'] == 1 && in_array($country, $current->activeCountries['list']))
		{
			return true;
		}
		// Exclusion list and the country is NOT in the list
		elseif($current->activeCountries['type'] == 2 && !in_array($country, $current->activeCountries['list']))
		{
			return true;
		}

		return false;
	}
}