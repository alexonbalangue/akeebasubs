<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * Subscription levels table adapter
 *   
 * @author   	Nicholas K. Dionysopoulos <nicholas@akeebabackup.com>
 * @package		akeebasubs
 */
class ComAkeebasubsDatabaseTableCoupons extends KDatabaseTableAbstract
{
	public function __construct(KConfig $config)
	{
		// Uncomment this if I add a view to handle this...
		// $config->name = 'akeebasubs_view_levels';
		$config->base = 'akeebasubs_coupons';
		$config->name = 'akeebasubs_coupons';
  
		parent::__construct($config);
    }
    
	protected function _initialize(KConfig $config)
    {
    	$config->behaviors = array('lockable', 'creatable', 'modifiable', 'orderable', 'hittable'
			,'admin::com.akeebasubs.database.behavior.hitlimitable'
			,'admin::com.akeebasubs.database.behavior.expirable'
    		);
		parent::_initialize($config);
    }
}