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
	protected $_cache = array();
	
	/**
	 * Fires whenever a record is read.It will cache the status fields
	 * (enabled, state, publish_up, publish_down) so that they can be
	 * checked in _afterTableUpdate.
	 */	
	public function _afterTableSelect(KCommandContext $context)
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
			$this->_cache[$row->id] = array(
				'enabled'		=> $row->enabled,
			);
		}
		
		return true;
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
			
			if(array_key_exists($row->id, $this->_cache)) {
				$cache = $this->_cache[$row->id];
			} else {
				$cache = array(
					'enabled'		=> null,
				);
			}
			
			$modified = false;
			foreach($cache as $key => $value) {
				if($row->$key != $value) {
					$modified = true;
					break;
				}
			}
			
			if($modified && $row->enabled) {
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
	
	/**
	 * Fires after creating a record. It will always trigger the events.
	 */
	public function _afterTableInsert(KCommandContext $context)
	{
		$contextRows = $context->data;
		if($contextRows instanceof KDatabaseRowAbstract) {
			$rows = array($contextRows);
		} else {
			if(!($contextRows instanceof KDatabaseRowsetAbstract)) return true;
			$rows = $contextRows;
		}
		
		// Load the "akeebasubs" plugins
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
	
		foreach($rows as $row) {
			if( !($row instanceof KDatabaseRowAbstract) ) continue;
			
			// Fire plugins (onAKSubscriptionCreate)
			$jResponse = $app->triggerEvent('onAKSubscriptionCreate', array($row));
		}
	
		return true;
	}
	
}