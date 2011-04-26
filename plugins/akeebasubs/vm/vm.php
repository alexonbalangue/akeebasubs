<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsVm extends JPlugin
{
	/** @var array Levels to Groups to Add mapping */
	private $addGroups = array();
	
	/** @var array Levels to Groups to Remove mapping */
	private $removeGroups = array();

	public function __construct(& $subject, $config = array())
	{
		parent::__construct($subject, $config);

		// Load level to group mapping from plugin parameters		
		$strAddGroups = $this->params->get('addgroups','');
		$this->addGroups = $this->parseGroups($strAddGroups);
		
		$strRemoveGroups = $this->params->get('removegroups','');
		$this->removeGroups = $this->parseGroups($strRemoveGroups);
	}
	
	/**
	 * Called when a new subscription is created, either manually or through
	 * the front-end interface
	 */
	public function onAKSubscriptionCreate(KDatabaseRowDefault $row)
	{
		$this->onAKUserRefresh($row->user_id);
	}
	
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange(KDatabaseRowDefault $row)
	{
		$this->onAKUserRefresh($row->user_id);
	}
	
	/**
	 * Called whenever the administrator asks to refresh integration status.
	 * 
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Make sure we're configured
		if(empty($this->addGroups) && empty($this->removeGroups)) return;
	
		// Get all of the user's subscriptions
		$subscriptions = KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
			->user_id($user_id)
			->getList();
			
		// Make sure there are subscriptions set for the user
		if(!count($subscriptions)) return;
		
		// Get the initial list of groups to add/remove from
		$addGroups = array();
		$removeGroups = array();
		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				// Enabled subscription, add groups
				if(empty($this->addGroups)) continue;
				if(!array_key_exists($level, $this->addGroups)) continue;
				$groups = $this->addGroups[$level];
				foreach($groups as $group) {
					if(!in_array($group, $addGroups)) {
						$addGroups[] = $group;
					}
				}
			} else {
				// Disabled subscription, remove groups
				if(empty($this->removeGroups)) continue;
				if(!array_key_exists($level, $this->removeGroups)) continue;
				$groups = $this->removeGroups[$level];
				
				foreach($groups as $group) {
					if(!in_array($group, $removeGroups)) {
						$removeGroups[] = $group;
					}
				}
			}
		}
		
		// If no groups are detected, do nothing
		if(empty($addGroups) && empty($removeGroups)) return;
		
		// Sort the lists
		asort($addGroups);
		asort($removeGroups);
		
		// Clean up the remove groups: if we are asked to both add and remove a user
		// from a group, add wins.
		if(!empty($removeGroups) && !empty($addGroups)) {
			$temp = $removeGroups;
			$removeGroups = array();
			foreach($temp as $group) {
				if(!in_array($group, $addGroups)) {
					$removeGroups[] = $group;
				}
			}
		}
		
		// Get DB connection
		$db = JFactory::getDBO();
		
		// Add to VM
		if(!empty($addGroups)) {
			$sql = 'DELETE FROM `#__vm_shopper_vendor_xref` WHERE `user_id` = ' . $db->Quote($user_id);
			$db->setQuery($sql);
			$db->query();
			
			$group = array_pop($addGroups);

			$sql = 'REPLACE INTO `#__vm_shopper_vendor_xref` (`user_id`,`shopper_group_id`,`vendor_id`) VALUES ('.$db->Quote($user_id).', '.$db->Quote($group).', 1)';
			$db->setQuery($sql);
			$db->query();
		}
		
		// Remove from VM
		if(!empty($removeGroups)) {
			$protoSQL = 'DELETE FROM `#__vm_shopper_vendor_xref` WHERE `user_id` = ' . $db->Quote($user_id) . ' AND `shopper_group_id` = ';
			foreach($removeGroups as $group) {
				$sql = $protoSQL . $db->Quote($group);
				$db->setQuery($sql);
				$db->query();
			}
		}
	}
	
	/**
	 * Converts an Akeeba Subscriptions level to a numeric ID
	 * 
	 * @param $title string The level's name to be converted to an ID
	 *
	 * @return int The subscription level's ID or -1 if no match is found
	 */
	private function ASLevelToId($title)
	{
		static $levels = null;
		
		// Don't process invalid titles
		if(empty($title)) return -1;
		
		// Fetch a list of subscription levels if we haven't done so already
		if(is_null($levels)) {
			$levels = array();
			$list = KFactory::tmp('admin::com.akeebasubs.model.levels')
				->getList();
			if(count($list)) foreach($list as $level) {
				$thisTitle = strtoupper($level->title);
				$levels[$thisTitle] = $level->id;
			}
		}
		
		$title = strtoupper($title);
		if(array_key_exists($title, $levels)) {
			// Mapping found
			return($levels[$title]);
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}
	
	private function VMGroupToId($title)
	{
		static $groups = null;
		
		if(empty($title)) return -1;
		
		if(is_null($groups)) {
			$groups = array();
			
			$db = JFactory::getDBO();
			$sql = 'SELECT `shopper_group_name` AS `title`, `shopper_group_id` AS `id` FROM `#__vm_shopper_group` WHERE `vendor_id` = 1';
			$db->setQuery($sql);
			$res = $db->loadObjectList();
			
			if(!empty($res)) {
				foreach($res as $item) {
					$t = strtoupper(trim($item->title));
					$groups[$t] = $item->id;
				}
			}
		}

		$title = strtoupper(trim($title));
		if(array_key_exists($title, $groups)) {
			// Mapping found
			return($groups[$title]);
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}

	private function parseGroups($rawData)
	{
		if(empty($rawData)) return array();
		
		$ret = array();
		
		// Just in case something funky happened...
		$rawData = str_replace("\\n", "\n", $rawData);
		$rawData = str_replace("\r", "\n", $rawData);
		$rawData = str_replace("\n\n", "\n", $rawData);
		
		$lines = explode("\n", $rawData);
		
		foreach($lines as $line) {
			$line = trim($line);
			$parts = explode('=', $line, 2);
			if(count($parts) != 2) continue;
			
			$level = $parts[0];
			$rawGroups = $parts[1];
			
			$groups = explode(',', $rawGroups);
			if(empty($groups)) continue;
			if(!is_array($groups)) $groups = array($groups);
			
			$levelId = $this->ASLevelToId($level);
			$groupIds = array();
			foreach($groups as $groupTitle) {
				$groupIds[] = $this->VMGroupToId($groupTitle);
			}
			
			$ret[$levelId] = $groupIds;
		}
		
		return $ret;
	}
}