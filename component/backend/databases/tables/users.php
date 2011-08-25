<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * Users table adapter
 *   
 * @author   	Nicholas K. Dionysopoulos <nicholas@akeebabackup.com>
 * @package		akeebasubs
 */
class ComAkeebasubsDatabaseTableUsers extends KDatabaseTableAbstract
{
	protected function _initialize(KConfig $config)
	{
		$config->behaviors = array(
			'admin::com.akeebasubs.database.behavior.usernotifiable'
		);
		parent::_initialize($config);
	}
}