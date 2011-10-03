<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

// Timezone fix; avoids errors printed out by PHP 5.3.3+
if( !version_compare(JVERSION, '1.6.0', 'ge') && function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
	if(function_exists('error_reporting')) {
		$oldLevel = error_reporting(0);
	}
	$serverTimezone = @date_default_timezone_get();
	if(empty($serverTimezone) || !is_string($serverTimezone)) $serverTimezone = 'UTC';
	if(function_exists('error_reporting')) {
		error_reporting($oldLevel);
	}
	@date_default_timezone_set( $serverTimezone);
}

// Master access check
if(version_compare(JVERSION, '1.6.0', 'ge')) {
	// Access check, Joomla! 1.6 style.
	$user = JFactory::getUser();
	if (!$user->authorise('core.manage', 'com_akeebasubs') && !$user->authorise('core.admin', 'com_akeebasubs')) {
		return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
	}
}

// Handle Live Update requests
require_once JPATH_COMPONENT_ADMINISTRATOR.'/liveupdate/liveupdate.php';
if(JRequest::getCmd('view','') == 'liveupdate') {
	LiveUpdate::handleRequest();
	return;
}

// Merge English and local translations
$jlang =& JFactory::getLanguage();
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);

// Get and execute the controller
require_once JPATH_COMPONENT_ADMINISTRATOR.'/fof/include.php';
JRequest::setVar('view', JRequest::getCmd('view','cpanel'));
$controller = FOFController::getInstance('com_akeebasubs', $view);
$controller->execute(JRequest::getCmd('task','display'));
$controller->redirect();