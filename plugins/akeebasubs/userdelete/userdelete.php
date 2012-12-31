<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsUserdelete extends JPlugin
{
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;
		if(!array_key_exists('enabled', (array)$info['modified'])) return;
		
		// Only handle expired subscriptions
		if( (($row->state == "C") || ($row->state == "X")) && ($row->enabled == 0) ) {
			$this->onAKUserRefresh($row->user_id);
		}
	}
	
	/**
	 * Called whenever the administrator asks to refresh integration status.
	 * 
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList(true);
			
		// Make sure there are subscriptions set for the user
		if(!count($subscriptions)) return;
		
		// Check if the user has active subscriptions
		$active = 0;
		foreach($subscriptions as $sub) {
			if($sub->enabled) {
				$active++;
			} elseif( $sub->state == 'P' ) {
				// Pending payments don't mean that the subscription is expired or invalid (yet)
				$active++;
			}
		}
		
		if(!$active) {
			$this->removeJ16($user_id);
		}
	}
	
	/**
	 * Removes a user using the Joomla! 1.6+ method
	 * @param int $id The user ID to delete
	 */
	private function removeJ16($id)
	{
		$table	= JTable::getInstance('User', 'JTable', array());
		
		// Trigger the onUserBeforeSave event.
		JPluginHelper::importPlugin('user');
		$dispatcher = JDispatcher::getInstance();
		
		// Will not delete Super Administrators
		if(JAccess::check($id, 'core.admin')) return;
		
		if($table->load($id)) {
			$user_to_delete = JFactory::getUser($id);
			$dispatcher->trigger('onUserBeforeDelete', array($table->getProperties()));
			if($table->delete($id)) {
				$dispatcher->trigger('onUserAfterDelete', array($user_to_delete->getProperties(), true, $this->getError()));
			}
		}
		
	}

}