<?php
/**
 * @package		mod_akeebasubs
 * @copyright 	Copyright (c) 2011-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license 	GNU General Public License version 3 or later
 * 
 * Originally written by Sander Potjer for Akeeba Subscriptions 1.0. Refactored
 * for Akeeba Subscriptions 2.0 by Nicholas K. Dionysopoulos
 */

defined('_JEXEC') or die();

include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/version.php';
include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/fof/include.php';
if(!defined('FOF_INCLUDED')) return;
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