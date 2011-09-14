<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * Implements hits-limitable content, i.e. content which will be auto-disabled
 * if it receives more hits than specified
 *
 * @author nicholas
 */
class ComAkeebasubsDatabaseBehaviorHitlimitable extends KDatabasebehaviorAbstract
{
	public function getMixableMethods(KObject $mixer = null)
	{
		$methods = array();
		
		if(isset($mixer->hitslimit)) {
			$methods = parent::getMixableMethods($mixer);
		}
		
		return $methods;
	}
	
	public function _afterTableSelect(KCommandContext $context)
	{
		$contextRows = $context->data;
		if($contextRows instanceof KDatabaseRowAbstract) {
			$rows = array($contextRows);
		} else {
			if(!($contextRows instanceof KDatabaseRowsetAbstract)) return true;
			$rows = $contextRows;
		}
		
		foreach($rows as $row)
		{
			if($row->hitslimit <= 0) continue;
		
			$triggered = false;
			
			if($row->hits >= $row->hitslimit) {
				$row->enabled = 0;
				$triggered = true;
			}
			
			if($triggered) {
				$row->save();
			}		
		}
		
		return true;
	}	
}