<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

class AkeebasubsHelperCparams
{
	public static function getParam($key, $default = null)
	{
		static $params = null;
		
		if(!is_object($params)) {
			jimport('joomla.application.component.helper');
			$component = JComponentHelper::getComponent('com_akeebasubs');
			$params = $component->params;
			if(!($params instanceof JRegistry)) {
				jimport('joomla.registry.registry');
				$params = new JRegistry($params);
			}
		}
		
		if(version_compare(JVERSION, '3.0.0', 'ge')) {
			return $params->get($key, $default);
		} else {
			return $params->getValue($key, $default);
		}
	}
}