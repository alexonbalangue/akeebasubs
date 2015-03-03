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
	 */
	public function getPaymentPlugins()
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

		return $ret; // name, title
	}
}