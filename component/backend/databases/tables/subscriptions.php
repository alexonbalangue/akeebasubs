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
		// TODO Instead of the expirable behaviour, use an onlyexpirable behaviour which won't
		// disable a subscription before its publish_up date.
		// $config->behaviors = array('admin::com.akeebasubs.database.behavior.expirable');
		parent::_initialize($config);
	}
    
}