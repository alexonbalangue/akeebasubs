<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsHelperImage
{
	public static function getURL($filename)
	{
		// Get the base site URL
		$url = JURI::base();
		$url = rtrim($url, '/');
		
		// Take into account relative URL for administrator
		list($isCLI, $isAdmin) = FOFDispatcher::isCliAdmin();
		if($isAdmin) {
			$url .= '/..';
		}
		
		if(!class_exists('AkeebasubsHelperCparams')) {
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		}
		$imagePath = trim(AkeebasubsHelperCparams::getParam('imagedir', 'images/'),'/');
		
		if(version_compare(JVERSION, '3.0', 'le')) {
			// Joomla! 2.5 : Pretty much straightforward
			return $url.'/'.$imagePath.'/'.$filename;
		} else {
			// Joomla! 3.0+ : Where the heck is the image?
			$testJ25 = JPATH_SITE.'/'.$imagePath.'/'.$filename;
			$testJ30 = JPATH_SITE.'/'.$filename;
			
			if(file_exists($testJ30)) {
				return $url.'/'.$filename;
			} else {
				return $url.'/'.$imagePath.'/'.$filename;
			}
		}
	}
}