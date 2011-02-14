<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * Joomla #__users table adapter
 *   
 * @author   	Nicholas K. Dionysopoulos <nicholas@akeebabackup.com>
 * @package		akeebasubs
 */
class ComAkeebasubsDatabaseTableJusers extends KDatabaseTableAbstract
{
	public function __construct(KConfig $config)
	{
		$config->name = 'users';
		$config->base = 'users';
		$config->identity_column = 'id';
  
		parent::__construct($config);
    }    
}