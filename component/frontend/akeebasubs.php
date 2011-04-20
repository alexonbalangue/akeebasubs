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

include_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_akeebasubs'.DS.'version.php';

// Magic: merge the default translation with the current translation
$jlang =& JFactory::getLanguage();
$jlang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
$jlang->load('com_akeebasubs', JPATH_SITE, $jlang->getDefault(), true);
$jlang->load('com_akeebasubs', JPATH_SITE, null, true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);

// We like code reuse, so we map some of the frontend models to the backend models
KFactory::map('site::com.akeebasubs.model.subscriptions',	'admin::com.akeebasubs.model.subscriptions');
KFactory::map('site::com.akeebasubs.model.levels',			'admin::com.akeebasubs.model.levels');
KFactory::map('site::com.akeebasubs.model.configs',			'admin::com.akeebasubs.model.configs');
KFactory::map('site::com.akeebasubs.model.jusers',			'admin::com.akeebasubs.model.jusers');
KFactory::map('site::com.akeebasubs.model.taxrules',		'admin::com.akeebasubs.model.taxrules');
KFactory::map('site::com.akeebasubs.model.users',			'admin::com.akeebasubs.model.users');
KFactory::map('site::com.akeebasubs.model.messages',		'site::com.akeebasubs.model.levels');

echo KFactory::get('site::com.akeebasubs.dispatcher')->dispatch();