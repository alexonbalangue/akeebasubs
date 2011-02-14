<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * Subscription levels table adapter
 *   
 * @author   	Nicholas K. Dionysopoulos <nicholas@akeebabackup.com>
 * @package		akeebasubs
 */
class ComAkeebasubsDatabaseTableLevels extends KDatabaseTableAbstract
{
	public function __construct(KConfig $config)
	{
		// Uncomment this if I add a view to handle this...
		// $config->name = 'akeebasubs_view_levels';
		$config->base = 'akeebasubs_levels';
  
		parent::__construct($config);
    }
    
	protected function _initialize(KConfig $config)
    {
    	$config->behaviors = array('lockable', 'creatable', 'modifiable', 'sluggable', 'orderable', 'identifiable');
		parent::_initialize($config);
    }
}