<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsAcymailing extends JPlugin
{
	/** @var array Levels to Groups to Add mapping */
	private $addGroups = array();
	
	/** @var array Levels to Groups to Remove mapping */
	private $removeGroups = array();

	public function __construct(& $subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);

		// Load level to group mapping from plugin parameters		
		$strAddGroups = $this->params->get('addgroups','');
		$this->addGroups = $this->parseGroups($strAddGroups);
		
		$strRemoveGroups = $this->params->get('removegroups','');
		$this->removeGroups = $this->parseGroups($strRemoveGroups);
	}

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;
		if(array_key_exists('enabled', (array)$info['modified'])) {
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
					if(!in_array($group, $addGroups) && ($group > 0) ) {
						$addGroups[] = $group;
					}
				}
			} else {
				// Disabled subscription, remove groups
				if(empty($this->removeGroups)) continue;
				if(!array_key_exists($level, $this->removeGroups)) continue;
				$groups = $this->removeGroups[$level];
				
				foreach($groups as $group) {
					if(!in_array($group, $removeGroups) && ($group > 0) ) {
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
		
		// Does this user have an AcyMailing user record?
		$query = FOFQueryAbstract::getNew($db)
			->select($db->qn('subid'))
			->from('#__acymailing_subscriber')
			->where($db->qn('userid').' = '.$db->q($user_id));
		$db->setQuery($query);
		$amsubid = $db->loadResult();
		
		if(empty($amsubid) || is_null($amsubid)) {
			// Create new AcyMailing subscriber record
			$user = JFactory::getUser($user_id);
			$amsubobject = (object)array(
				'email'		=> $user->email,
				'userid'	=> $user->id,
				'name'		=> $user->name,
				'created'	=> time(),
				'confirmed'	=> 1,
				'enabled'	=> 1,
				'accept'	=> 1,
				'html'		=> 1,
			);
			$db->insertObject('#__acymailing_subscriber', $amsubobject);
			$amsubid = $db->insertid();
		}
		
		// Add to AcyMailing
		if(!empty($addGroups)) {
			foreach($addGroups as $group) {
				// Check for old record
				$query = FOFQueryAbstract::getNew($db)
					->select($db->qn('subid'))
					->from($db->qn('#__acymailing_listsub'))
					->where($db->qn('listid') .'='. $db->q($group))
					->where($db->qn('subid') .'='. $db->q($amsubid));
				$db->setQuery($query);
				$test = $db->loadResult();
				
				// Remove old record
				if(!empty($test) && !is_null($test)) {
					$query = FOFQueryAbstract::getNew($db)
						->delete($db->qn('#__acymailing_listsub'))
						->where($db->qn('listid') .'='. $db->q($group))
						->where($db->qn('subid') .'='. $db->q($amsubid));
					$db->setQuery($query);
					$db->query();
				}
				
				// Insert new record
				$query = FOFQueryAbstract::getNew($db)
					->insert($db->qn('#__acymailing_listsub'))
					->values(
						$db->q($group).', '.$db->q($amsubid).', '.
						$db->q(time()).', '.$db->q(null).', '.
						$db->q(1)
					)->columns(array(
						$db->qn('listid'), $db->qn('subid'),
						$db->qn('subdate'), $db->qn('unsubdate'),
						$db->qn('status'),
					));
				;
				$db->setQuery($query);
				$db->query();
			}
		}
		
		// Remove from AcyMailing
		if(!empty($removeGroups)) {
			foreach($removeGroups as $group) {
				$query = FOFQueryAbstract::getNew($db)
					->delete($db->qn('#__acymailing_listsub'))
					->where($db->qn('listid') .'='. $db->q($group))
					->where($db->qn('subid') .'='. $db->q($amsubid));
				$db->setQuery($query);
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
				$levels[$thisTitle] = $level->akeebasubs_level_id;
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
	
	private function AcymailingListToId($title)
	{
		static $groups = null;
		
		if(empty($title)) return -1;
		
		if(is_null($groups)) {
			$groups = array();
			
			$db = JFactory::getDBO();
			$query = FOFQueryAbstract::getNew($db)
				->select(array(
					$db->qn('listid').' AS '.$db->qn('id'),
					$db->qn('name').' AS '.$db->qn('title'),
				))->from($db->qn('#__acymailing_list'))
			;
			$db->setQuery($query);
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
				$groupIds[] = $this->AcymailingListToId($groupTitle);
			}
			
			$ret[$levelId] = $groupIds;
		}
		
		return $ret;
	}
}