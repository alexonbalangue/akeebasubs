<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * Unblocks the associated user account when a subscription is activated
 *
 * @author nicholas
 */
class ComAkeebasubsDatabaseBehaviorUserunblockable extends KDatabaseBehaviorAbstract
{
	public function _afterTableInsert(KCommandContext $context)
	{
		return $this->_afterTableUpdate($context);
	}
	
	/**
	 * Fires after updating a table. If data was changed, it will trigger
	 * the necessary events.
	 */	
	public function _afterTableUpdate(KCommandContext $context)
	{
		$contextRows = $context->data;
		if($contextRows instanceof KDatabaseRowAbstract) {
			$rows = array($contextRows);
		} else {
			if(!($contextRows instanceof KDatabaseRowsetAbstract)) return true;
			$rows = $contextRows;
		}
		
		foreach($rows as $row) {
			if( !($row instanceof KDatabaseRowAbstract) ) continue;
			if($row->enabled) {
				// Is this a paid subscription?
				if($row->state != 'C') continue;
				// Cool! Let's unblock the user
				KFactory::tmp('admin::com.akeebasubs.model.jusers')
					->id($row->user_id)
					->getItem()
					->setData(array(
						'block'		=> 0
					))->save();
			}
		}
		
		return true;
	}
}