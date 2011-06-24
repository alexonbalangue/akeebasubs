<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsViewHtml extends ComDefaultViewHtml
{
	
	public function __construct(KConfig $config)
	{
		$config->views = array(
			'dashboard' 		=> 'COM_AKEEBASUBS_DASHBOARD',
			'levels' 			=> 'COM_AKEEBASUBS_LEVELS_TITLE',
			'subscriptions'		=> 'COM_AKEEBASUBS_SUBSCRIPTIONS_TITLE',
			'coupons'			=> 'COM_AKEEBASUBS_COUPONS_TITLE',
			'upgrades'			=> 'COM_AKEEBASUBS_UPGRADES_TITLE',
			'taxrules'			=> 'COM_AKEEBASUBS_TAXRULES_TITLE',
			'users'				=> 'COM_AKEEBASUBS_USERS_TITLE',
			'config'			=> 'COM_AKEEBASUBS_CONFIG_TITLE'
        );
		$config->append(array(
			'behaviors'  =>  array(
				'admin::com.default.controller.behavior.commandable',
				'admin::com.default.controller.behavior.executable'
		)));
		
		parent::__construct($config);
	}
}