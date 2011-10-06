<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
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
				$params = new JParameter($params);
			}
		}
		
		return $params->getValue($key, $default);
	}
}