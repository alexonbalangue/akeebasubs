<?php

/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2015 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */
defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

// PHP version check
if (defined('PHP_VERSION'))
{
	$version = PHP_VERSION;
}
elseif (function_exists('phpversion'))
{
	$version = phpversion();
}
else
{
	// No version info. I'll lie and hope for the best.
	$version = '5.0.0';
}

// Old PHP version detected. EJECT! EJECT! EJECT!
if (!version_compare($version, '5.3.0', '>='))
	return;

// Make sure F0F is loaded, otherwise do not run
if (!defined('F0F_INCLUDED'))
{
	include_once JPATH_LIBRARIES . '/f0f/include.php';
}

if (!defined('F0F_INCLUDED') || !class_exists('F0FLess', true))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
JLoader::import('joomla.application.component.helper');

if (!JComponentHelper::isEnabled('com_akeebasubs', true))
{
	return;
}

// Require to send the correct emails in the Professional release
require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/version.php';

class plgUserAslogoutuser extends JPlugin
{

	/**
	 * Public constructor. Overridden to load the language strings.
	 */
	public function __construct(& $subject, $config = array())
	{
		if (!is_object($config['params']))
		{
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);

		// Timezone fix; avoids errors printed out by PHP 5.3.3+ (thanks Yannick!)
		if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set'))
		{
			if (function_exists('error_reporting'))
			{
				$oldLevel = error_reporting(0);
			}

			$serverTimezone	 = @date_default_timezone_get();

			if (empty($serverTimezone) || !is_string($serverTimezone))
			{
				$serverTimezone	 = 'UTC';
			}

			if (function_exists('error_reporting'))
			{
				error_reporting($oldLevel);
			}

			@date_default_timezone_set($serverTimezone);
		}

	}

	/**
	 * Removes the flag for logging out/in inside Akeeba Subscription user table
	 */
	public function onUserLogin($response, $options)
	{
        $userid = JUserHelper::getUserId($response['username']);
        $juser  = JFactory::getUser($userid);

        $user = F0FModel::getTmpInstance('Users', 'AkeebasubsModel')->getTable();
        $user->load(array('user_id' => $juser->id));

        // Mhm... the user was not found inside Akeeba Subscription, better stop here
        if(!$user->akeebasubs_user_id)
        {
            return true;
        }

        $bind['needs_logout'] = 0;
        $user->save($bind);

        return true;
	}
}