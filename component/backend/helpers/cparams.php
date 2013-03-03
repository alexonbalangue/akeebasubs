<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

class AkeebasubsHelperCparams
{
	private static $params = null;
	
	public static function getParam($key, $default = null)
	{
		if(!is_object(self::$params)) {
			JLoader::import('joomla.application.component.helper');
			self::$params = JComponentHelper::getParams('com_akeebasubs');
		}
		return self::$params->get($key, $default);
	}
	
	public static function setParam($key, $value)
	{
		if(!is_object(self::$params)) {
			JLoader::import('joomla.application.component.helper');
			self::$params = JComponentHelper::getParams('com_akeebasubs');
		}
		
		self::$params->set($key, $value);
		
		$db = JFactory::getDBO();
		$data = self::$params->toString();
		$sql = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params').' = '.$db->q($data))
			->where($db->qn('element').' = '.$db->q('com_akeebasubs'))
			->where($db->qn('type').' = '.$db->q('component'));
		$db->setQuery($sql);
		$db->execute();
	}
}