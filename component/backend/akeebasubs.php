<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */
defined('_JEXEC') or die();

// Check if Koowa is active
if(!defined('KOOWA')) {
    JError::raiseWarning(0, JText::_("Koowa wasn't found. Please install the Koowa plugin and enable it."));
    return;
}

// Load live Update translation files
$jlang =& JFactory::getLanguage();
$jlang->load('liveupdate', JPATH_COMPONENT_ADMINISTRATOR.DS.'liveupdate', 'en-GB', true);
$jlang->load('liveupdate', JPATH_COMPONENT_ADMINISTRATOR.DS.'liveupdate', $jlang->getDefault(), true);
$jlang->load('liveupdate', JPATH_COMPONENT_ADMINISTRATOR.DS.'liveupdate', null, true);
// Handle Live Update requests
require_once JPATH_COMPONENT_ADMINISTRATOR.DS.'liveupdate'.DS.'liveupdate.php';
if(JRequest::getCmd('view','') == 'liveupdate') {
	LiveUpdate::handleRequest();
	return;
}

echo KFactory::get('com://admin/akeebasubs.dispatcher')
	->dispatch();