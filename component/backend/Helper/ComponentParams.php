<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Helper;

use JComponentHelper;
use JFactory;
use JLoader;

defined('_JEXEC') or die;

/**
 * A helper class to quickly get the component parameters
 */
abstract class ComponentParams
{

	/**
	 * Cached component parameters
	 *
	 * @var \Joomla\Registry\Registry
	 */
	private static $params = null;

	/**
	 * Returns the value of a component configuration parameter
	 *
	 * @param   string $key     The parameter to get
	 * @param   mixed  $default Default value
	 *
	 * @return  mixed
	 */
	public static function getParam($key, $default = null)
	{
		if (!is_object(self::$params))
		{
			JLoader::import('joomla.application.component.helper');

			self::$params = JComponentHelper::getParams('com_akeebasubs');
		}

		return self::$params->get($key, $default);
	}

	/**
	 * Sets the value of a component configuration parameter
	 *
	 * @param   string $key    The parameter to set
	 * @param   mixed  $value  The value to set
	 *
	 * @return  void
	 */
	public static function setParam($key, $value)
	{
		if (!is_object(self::$params))
		{
			JLoader::import('joomla.application.component.helper');
			self::$params = JComponentHelper::getParams('com_akeebasubs');
		}

		self::$params->set($key, $value);

		$db   = JFactory::getDBO();
		$data = self::$params->toString();

		$sql  = $db->getQuery(true)
		           ->update($db->qn('#__extensions'))
		           ->set($db->qn('params') . ' = ' . $db->q($data))
		           ->where($db->qn('element') . ' = ' . $db->q('com_akeebasubs'))
		           ->where($db->qn('type') . ' = ' . $db->q('component'));

		$db->setQuery($sql);

		try
		{
			$db->execute();
		}
		catch (\Exception $e)
		{
			// Don't sweat if it fails
		}
	}
}