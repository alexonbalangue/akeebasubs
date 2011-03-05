<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

/**
 * Implements notifications for subscriptions status changes. What this behaviour
 * does is to monitor new and existing subscriptions. If the payment or enabled
 * status is toggled, it will launch plugin events. Plugins can then do what they
 * need to do, e.g. send emails or perform integration actions with other third
 * party software.
 *
 * @author nicholas
 */
class ComAkeebasubsDatabaseBehaviorSubnotify extends KDatabaseBehaviorAbstract
{
	protected $_cache = array();

	public function getMixableMethods(KObject $mixer = null)
	{
		$methods = array();
		
		if(isset($mixer->publish_up) || isset($mixer->publish_down)) {
			$methods = parent::getMixableMethods($mixer);
		}
		
		return $methods;
	}
	
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
				'state'			=> $row->state,
				'publish_up'	=> $row->publish_up,
				'publish_down'	=> $row->publish_down
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
		
		// Load the "akeebasubs" plugins
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		
		foreach($rows as $row) {
			if( !($row instanceof KDatabaseRowAbstract) ) continue;
			
			if(array_key_exists($row->id, $this->_cache)) {
				$cache = $this->_cache[$row->id];
			} else {
				$cache = array(
					'enabled'		=> null,
					'state'			=> null,
					'publish_up'	=> '0000-00-00 00:00:00',
					'publish_down'	=> '0000-00-00 00:00:00'
				);
			}
			
			$modified = false;
			foreach($cache as $key => $value) {
				if($row->$key != $value) {
					$modified = true;
					break;
				}
			}
			
			if($modified) {
				// Fire plugins (onAKSubscriptionChange) passing the row as a parameter
				$jResponse = $app->triggerEvent('onAKSubscriptionChange', array($row));
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