<?php
/**
 * @package        mod_akeebasubs
 * @copyright      Copyright (c) 2011-2016 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU General Public License version 3 or later
 *
 * Originally written by Sander Potjer for Akeeba Subscriptions 1.0. Refactored
 * for Akeeba Subscriptions 2.0 by Nicholas K. Dionysopoulos
 */

defined('_JEXEC') or die();

if (!defined('FOF30_INCLUDED') && !@include_once(JPATH_LIBRARIES . '/fof30/include.php'))
{
	return;
}

// Get the Akeeba Subscriptions container. Also includes the autoloader.
$container = FOF30\Container\Container::getInstance('com_akeebasubs');

// Load the language files
$lang = JFactory::getLanguage();
$lang->load('mod_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
$lang->load('mod_akeebasubs', JPATH_ADMINISTRATOR, null, true);
$lang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
$lang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);

/** @var \Akeeba\Subscriptions\Admin\Model\Subscriptions $subscriptionsModel */
$subscriptionsModel = $container->factory->model('Subscriptions')->tmpInstance();

$items = $subscriptionsModel
	->filter_order('created_on')
	->filter_order_Dir('desc')
	->limitstart(0)
	->limit(10)
	->with(['level', 'user'])
	->get();

require_once JModuleHelper::getLayoutPath($module->module);