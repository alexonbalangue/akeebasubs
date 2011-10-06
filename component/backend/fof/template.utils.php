<?php
/**
 *  @package FrameworkOnFramework
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class FOFTemplateUtils
{
	public static function addCSS($path)
	{
		$url = self::parsePath($path);
		JFactory::getDocument()->addStyleSheet($url);
	}
	
	public static function addJS($path)
	{
		$url = self::parsePath($path);
		JFactory::getDocument()->addScript($url);
	}
	
	public static function parsePath($path)
	{
		$protoAndPath = explode('://', $path, 2);
		if(count($protoAndPath) < 2) {
			$protocol = 'media';
		} else {
			$protocol = $protoAndPath[0];
			$path = $protoAndPath[1];
		}
		
		$url = JURI::root();
		
		switch($protocol) {
			case 'media':
				$url .= 'media/';
				break;
			
			case 'admin':
				$url .= 'administrator/';
				break;
			
			default:
			case 'site':
				break;
		}
		
		$url .= $path;
		
		return $url;
	}
	
	public static function loadPosition($position, $style = -2)
	{
		$document	= &JFactory::getDocument();
		$renderer	= $document->loadRenderer('module');
		$params		= array('style'=>$style);
		
		$contents = '';
		foreach (JModuleHelper::getModules($position) as $mod)  {
			$contents .= $renderer->render($mod, $params);
		}
		return $contents;
	}
}