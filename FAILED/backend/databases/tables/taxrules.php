<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * Tax tules table adapter
 *   
 * @author   	Nicholas K. Dionysopoulos <nicholas@akeebabackup.com>
 * @package		akeebasubs
 */
class ComAkeebasubsDatabaseTableTaxrules extends KDatabaseTableAbstract
{
	protected function _initialize(KConfig $config)
    {
    	$config->behaviors = array('lockable', 'creatable', 'modifiable', 'sluggable', 'orderable');
		parent::_initialize($config);
    }
}