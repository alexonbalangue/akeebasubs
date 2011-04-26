<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

// Check if Koowa is active
if(!defined('KOOWA')) {
    JError::raiseWarning(0, JText::_("Koowa wasn't found. Please install the Koowa plugin and enable it."));
    return;
}

include_once JPATH_COMPONENT_ADMINISTRATOR.DS.'version.php';

// Magic: merge the default translation with the current translation
$jlang =& JFactory::getLanguage();
$jlang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
$jlang->load('com_akeebasubs', JPATH_SITE, $jlang->getDefault(), true);
$jlang->load('com_akeebasubs', JPATH_SITE, null, true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);

// Hacks to be compatible with multiple versions of Koowa without breaking badly
if(!class_exists('ComDefaultControllerView', true)) {
	class ComDefaultControllerView extends ComDefaultControllerPage {}
}

if(!class_exists('ComDefaultCommandAuthorize', true)) {
	class ComDefaultCommandAuthorize extends ComDefaultControllerCommandAuthorize {}
}

// I hate myself for doing this... but using map() didn't work for me!
$view = JRequest::getCmd('view','');
if(empty($view) || ($view == 'akeebasubs')) {
	$_GET['view'] = 'dashboard';
}

// And finally, clean code :p
echo KFactory::get('admin::com.akeebasubs.dispatcher')
	->dispatch();