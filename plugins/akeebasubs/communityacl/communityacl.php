<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsCommunityacl extends JPlugin
{
	/** @var array Levels to Groups to Add mapping */
	private $addGroups = array();
	
	/** @var array Levels to Groups to Remove mapping */
	private $removeGroups = array();
	
	/** @var array Community ACL Groups */
	private $caGroups = array();
	
	/** @var array Community ACL Roles per group */
	private $caRoles = array();
	
	/** @var array Community ACL Functions */
	private $caFunctions = array();

	public function __construct(& $subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);

		$this->loadCAFunctions();
		$this->loadCAGroups();
		$this->loadCALevels();
		
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
	public function onAKSubscriptionCreate($row)
	{
		$this->onAKUserRefresh($row->user_id);
	}
	
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row)
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
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
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
					if(!$this->hashInArray($group->hash, $addGroups)) {
						$addGroups[] = $group;
					}
				}
			} else {
				// Disabled subscription, remove groups
				if(empty($this->removeGroups)) continue;
				if(!array_key_exists($level, $this->removeGroups)) continue;
				$groups = $this->removeGroups[$level];
				
				foreach($groups as $group) {
					if(!$this->hashInArray($group->hash, $removeGroups)) {
						$removeGroups[] = $group;
					}
				}
			}
		}
		
		// If no groups are detected, do nothing
		if(empty($addGroups) && empty($removeGroups)) return;
		
		// Clean up the remove groups: if we are asked to both add and remove a user
		// from a group, add wins.
		if(!empty($removeGroups) && !empty($addGroups)) {
			$temp = $removeGroups;
			$removeGroups = array();
			foreach($temp as $group) {
				if(!$this->hashInArray($group->hash, $addGroups)) {
					$removeGroups[] = $group;
				}
			}
		}
		
		// Get DB connection
		$db = JFactory::getDBO();
		
		// Add to JUGA
		if(!empty($addGroups)) {
			$sql = 'REPLACE INTO `#__community_acl_users` (`user_id`,`group_id`, `role_id`, `function_id`) VALUES ';
			
			$values = array();
			foreach($addGroups as $group) {
				$values[] = '('.$db->Quote($user_id).', '.$db->Quote($group->group).', '.$db->Quote($group->role)
					.', '.$db->Quote($group->function).')';
			}
			
			$sql .= implode(', ', $values);
			
			$db->setQuery($sql);
			$db->query();
		}
		
		// Remove from JUGA
		if(!empty($removeGroups)) {
			$protoSQL = 'DELETE FROM `#__community_acl_users` WHERE `user_id` = ' . $db->Quote($user_id) . ' AND `group_id` = ';
			foreach($removeGroups as $group) {
				$sql = $protoSQL . $db->Quote($group->group) . 'AND '.$db->nameQuote('role_id').' = '.$db->Quote($group->role)
					. 'AND '.$db->nameQuote('function_id').' = '.$db->Quote($group->function);
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
			$list = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
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
	
	private function NameToId($title, $array, $parent_key = null)
	{
		static $groups = null;
		
		if(empty($title)) return -1;

		$title = strtoupper(trim($title));
		
		if(is_null($parent_key)) {
			if(array_key_exists($title, $array)) {
				// Mapping found
				return($array[$title]);
			} elseif( (int)$title == $title ) {
				// Numeric ID passed
				return (int)$title;
			} else {
				// No match!
				return -1;
			}
		} else {
			$found = -1;
			// Try searching in selected sub-array
			if(array_key_exists($parent_key, $array)) {
				$newArray = $array[$parent_key];
				$found = $this->NameToId($title, $newArray);
			}
			// Try searching in the default sub-array (0-index)
			if( ($found === -1) && array_key_exists(0, $array) ) {
				$newArray = $array[0];
				$found = $this->NameToId($title, $newArray);
			}
			if( ($found === -1) && array_key_exists($parent_key, $array) ) {
				$newArray = $array[$parent_key];
				$ak = array_keys($newArray);
				$defKey = array_shift($ak);
				$found = $ak[$defKey];
			} elseif( ($found === -1) && array_key_exists(0, $array) ) {
				$newArray = $array[0];
				$ak = array_keys($newArray);
				$defKey = array_shift($ak);
				$found = $ak[$defKey];
			} else {
				$found = -1;
			}
			return $found;
		}
	}

	private function parseCompositeString($string)
	{
		$ret = (object)array(
			'group'		=> 1,
			'role'		=> 1,
			'function'	=> 1,
			'hash'		=> ''
		);
		
		$string = strtoupper(trim($string));
		
		if(empty($string)) return $ret;
		
		$parts = explode('|', $string);
		
		if(isset($parts[0])) {
			$gid = $this->NameToId($parts[0], $this->caGroups);
			if($gid === -1) $gid = 1;
			$ret->group = $gid;
		}
		
		if(isset($parts[1])) {
			$id = $this->NameToId($parts[1], $this->caRoles, $ret->group);
			if($id === -1) $id = 1;
			$ret->role = $id;
		}
		
		if(isset($parts[2])) {
			$id = $this->NameToId($parts[2], $this->caFunctions, $ret->group);
			if($id === -1) $id = 1;
			$ret->function = $id;
		}
		
		$ret->hash = $ret->group.'-'.$ret->role.'-'.$ret->function;
		
		return $ret;
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
				$groupIds[] = $this->parseCompositeString($groupTitle);
			}
			
			$ret[$levelId] = $groupIds;
		}
		
		return $ret;
	}
	
	private function loadCAGroups()
	{
		$db = JFactory::getDBO();
		$db->setQuery('SELECT `id`,`name` FROM `#__community_acl_groups`');
		$temp = $db->loadAssocList();
		$this->caGroups = array();
		if(!empty($temp)) foreach($temp as $record) {
			$key = strtoupper($record['name']);
			$this->caGroups[$key] = $record['id'];
		}
	}
	
	private function loadCALevels()
	{
		$db = JFactory::getDBO();
		$db->setQuery('SELECT `id`,`group_id`,`name` FROM `#__community_acl_roles`');
		$temp = $db->loadAssocList();
		$this->caRoles = array();
		if(!empty($temp)) foreach($temp as $record) {
			$key = strtoupper($record['name']);
			$this->caRoles[$record['group_id']][$key] = $record['id'];
		}
	}
	
	private function loadCAFunctions()
	{
		$db = JFactory::getDBO();
		$db->setQuery('SELECT `id`,`group_id`,`name` FROM `#__community_acl_functions`');
		$temp = $db->loadAssocList();
		$this->caFunctions = array();
		if(!empty($temp)) foreach($temp as $record) {
			$key = strtoupper($record['name']);
			$this->caFunctions[$record['group_id']][$key] = $record['id'];
		}
	}

	private function hashInArray($hash, $array)
	{
		foreach($array as $item) {
			if($item->hash == $hash) return true;
		}
		return false;
	}
}