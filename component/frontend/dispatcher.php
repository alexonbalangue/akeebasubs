<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsDispatcher extends ComDefaultDispatcher
{
    protected function _initialize(KConfig $config)
    {
    	include_once JPATH_ADMINISTRATOR.DS.'components'.DS.'com_akeebasubs'.DS.'version.php';
    	
		// Timezone fix; avoids errors printed out by PHP 5.3.3+ (thanks Yannick!)
		if(function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
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
		
    	// Magic: merge the default translation with the current translation
    	$jlang =& JFactory::getLanguage();
    	$jlang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
    	$jlang->load('com_akeebasubs', JPATH_SITE, $jlang->getDefault(), true);
    	$jlang->load('com_akeebasubs', JPATH_SITE, null, true);
    	$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
    	$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
    	$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);
    	
    	// Joomla! 1.7? Force load mooTools!
    	if(version_compare(JVERSION, '1.7.0', 'ge')) {
    		JHTML::_('behavior.framework');
    	}
    	
    	// We like code reuse, so we map some of the frontend models to the backend models
    	KFactory::map('site::com.default.controllers.behaviors.executable',	'admin::com.default.controllers.behaviors.executable');
    	KFactory::map('site::com.akeebasubs.model.subscriptions',	'admin::com.akeebasubs.model.subscriptions');
    	KFactory::map('site::com.akeebasubs.model.levels',			'admin::com.akeebasubs.model.levels');
    	KFactory::map('site::com.akeebasubs.model.configs',			'admin::com.akeebasubs.model.configs');
    	KFactory::map('site::com.akeebasubs.model.jusers',			'admin::com.akeebasubs.model.jusers');
    	KFactory::map('site::com.akeebasubs.model.taxrules',		'admin::com.akeebasubs.model.taxrules');
    	KFactory::map('site::com.akeebasubs.model.users',			'admin::com.akeebasubs.model.users');
    	KFactory::map('site::com.akeebasubs.model.messages',		'site::com.akeebasubs.model.levels');
    	
    	// I hate myself for doing this... but using map() didn't work for me!
    	$view = JRequest::getCmd('view','');
    	if(empty($view) || ($view == 'akeebasubs')) {
    		$_GET['view'] = 'levels';
    	}
    	
    	KFactory::get('site::com.akeebasubs.model.configs');
    	
        $config->append(array(
                'controller_default' => 'levels'
        ));
        parent::_initialize($config);
    }
}