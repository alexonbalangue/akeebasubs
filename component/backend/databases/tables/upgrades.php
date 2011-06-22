<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * Upgrades (auto rules) table adapter
 *   
 * @author   	Nicholas K. Dionysopoulos <nicholas@akeebabackup.com>
 * @package		akeebasubs
 */
class ComAkeebasubsDatabaseTableUpgrades extends KDatabaseTableAbstract
{
	public function __construct(KConfig $config)
	{
		$config->base = 'akeebasubs_upgrades';
  
		parent::__construct($config);
    }
    
	protected function _initialize(KConfig $config)
	{
		$config->behaviors = array(
			'orderable','creatable','modifiable','lockable','hittable'
		);
		parent::_initialize($config);
	}
    
}