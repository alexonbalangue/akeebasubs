<?php
/**
 *  @package AkeebaSubs
 *  @subpackage FrameworkOnFramework
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
	
	private static function parsePath($path)
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
}