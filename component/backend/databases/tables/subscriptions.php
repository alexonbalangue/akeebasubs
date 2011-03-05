<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * Subscriptions table adapter
 *   
 * @author   	Nicholas K. Dionysopoulos <nicholas@akeebabackup.com>
 * @package		akeebasubs
 */
class ComAkeebasubsDatabaseTableSubscriptions extends KDatabaseTableAbstract
{
	public function __construct(KConfig $config)
	{
		$config->name = 'akeebasubs_view_subscriptions';
		$config->base = 'akeebasubs_subscriptions';
  
		parent::__construct($config);
    }
    
	protected function _initialize(KConfig $config)
	{
		// WARNING: ORDER MATTERS! $config->behaviors is a LIFO queue. The last behavior to
		// be added fires first. We want the subnotify to always fire last.
		$config->behaviors = array(
			'admin::com.akeebasubs.database.behavior.subnotify',
			'admin::com.akeebasubs.database.behavior.subexpirable',
		);
		parent::_initialize($config);
	}
    
}