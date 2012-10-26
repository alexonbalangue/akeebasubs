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
			$params = JComponentHelper::getParams('com_akeebasubs');
		}
		return $params->get($key, $default);
	}
}