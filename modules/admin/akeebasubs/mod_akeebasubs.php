<?php
/**
 * @package		mod_akeebasubs
 * @copyright 	Copyright (c) 2011-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license 	GNU General Public License version 3 or later
 * 
 * Originally written by Sander Potjer for Akeeba Subscriptions 1.0. Refactored
 * for Akeeba Subscriptions 2.0 by Nicholas K. Dionysopoulos
 */

defined('_JEXEC') or die();

// PHP version check
if(defined('PHP_VERSION')) {
	$version = PHP_VERSION;
} elseif(function_exists('phpversion')) {
	$version = phpversion();
} else {
	// No version info. I'll lie and hope for the best.
	$version = '5.0.0';
}
// Old PHP version detected. EJECT! EJECT! EJECT!
if(!version_compare($version, '5.3.0', '>=')) return;

include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/version.php';
include_once JPATH_LIBRARIES.'/fof/include.php';
if(!defined('FOF_INCLUDED') || !class_exists('FOFForm', true))
{
	return;
}
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/format.php';
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';

$jlang = JFactory::getLanguage();
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);

$items = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
	->filter_order('ordering')
	->filter_order_Dir('desc')
	->limit(10)
	->getList();

require_once JModuleHelper::getLayoutPath($module->module);