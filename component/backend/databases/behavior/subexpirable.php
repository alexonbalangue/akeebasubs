<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * Implements expirable content, i.e. content which will be auto-enabled on a
 * publish_up date and auto-disabled on a publish_down date.
 *
 * @author nicholas
 */
class ComAkeebasubsDatabaseBehaviorSubexpirable extends KDatabasebehaviorAbstract
{
	public function getMixableMethods(KObject $mixer = null)
	{
		$methods = array();
		
		if(isset($mixer->publish_down)) {
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
		
		jimport('joomla.utilities.date');
		$jNow = new JDate();
		$uNow = $jNow->toUnix();
		
		foreach($rows as $row)
		{
			$triggered = false;
			
			if($row->publish_down && ($row->publish_down != '0000-00-00 00:00:00')) {
				$jDown = new JDate($row->publish_down);
				if( ($uNow >= $jDown->toUnix()) && $row->enabled ) {
					$row->enabled = 0;
					$triggered = true;
				}
			}
			
			if($triggered) {
				$row->save();
			}		
		}
		
		return true;
	}	
}