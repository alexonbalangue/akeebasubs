<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Model\Model;
use JFactory;
use JLoader;
use JPluginHelper;

class PaymentMethods extends Model
{
    /**
     * Gets a list of payment plugins and their titles
     *
     * @param   string  $country    Additional filter about the country
     *
     * @return  array
     */
	public function getPaymentPlugins($country = '')
	{
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akpayment');

		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKPaymentGetIdentity');

		$ret = array();

		foreach ($jResponse as $item)
		{
			if (is_object($item))
			{
				$ret[] = $item;
			}
			elseif (is_array($item))
			{
				if (array_key_exists('name', $item))
				{
					$ret[] = (object)$item;
				}
				else
				{
					foreach ($item as $anItem)
					{
						if (is_object($anItem))
						{
							$ret[] = $anItem;
						}
						else
						{
							$ret[] = (object)$anItem;
						}
					}
				}
			}
		}

        // No country? Good, there's no need to double check anything
        if(!$country)
        {
            return $ret;
        }

        $temp = array();

        // Let's double check if I have to remove any plugin due GeoIP restrictions
        foreach($ret as $plugin)
        {
            // These two if statements are split so we can better understand what's going on
            // Inclusion list and the country is in the list
            if($plugin->activeCountries['type'] == 1 && in_array($country, $plugin->activeCountries['list']))
            {
                $temp[] = $plugin;
            }
            // Exclusion list and the country is NOT in the list
            elseif($plugin->activeCountries['type'] == 2 && !in_array($country, $plugin->activeCountries['list']))
            {
                $temp[] = $plugin;
            }

            // In any other case, ignore the plugin...
        }

        $ret = $temp;

        // Good, I have the full list, now let's try to order it by country priority
        $temp = array();
        $i    = 0;

        foreach($ret as $plugin)
        {
            $i++;
            $idx = $i;

            // If I have a match in the priority list, let's bump the index of this plugin
            if(in_array($country, $plugin->activeCountries['priority']))
            {
                $idx += 10;
            }

            $temp[$idx] = $plugin;
        }

        krsort($temp);
        reset($temp);

        $ret = $temp;

		return $ret; // name, title
	}
}