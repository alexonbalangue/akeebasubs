<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsJoomla extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'joomla';
		
		parent::__construct($subject, $templatePath, $name, $config);
	}

	protected function MyGroupToId($title)
	{
		static $groups = null;
		
		if(empty($title)) return -1;
		
		if(is_null($groups)) {
			$groups = array();
			
			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->select(array(
					$db->qn('title'),
					$db->qn('id'),
				))->from($db->qn('#__usergroups'));
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
					if(!in_array($group, $addGroups) && ($group > 0)) {
						$addGroups[] = $group;
					}
				}
			} else {
				// Disabled subscription, remove groups
				if(empty($this->removeGroups)) continue;
				if(!array_key_exists($level, $this->removeGroups)) continue;
				$groups = $this->removeGroups[$level];
				
				foreach($groups as $group) {
					if(!in_array($group, $removeGroups) && ($group > 0)) {
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
		
		// Add to Joomla! 1.6 groups
		if(!empty($addGroups)) {
			// 1. Delete existing assignments
			$groupSet = array();
			foreach($addGroups as $group) {
				$groupSet[] = $db->q($group);
			}
			$query = $db->getQuery(true)
				->delete($db->qn('#__user_usergroup_map'))
				->where($db->qn('user_id').' = '.$user_id)
				->where($db->qn('group_id').' IN ('.implode(', ', $groupSet).')');
			$db->setQuery($query);
			$db->query();
			
			// 2. Add new assignments
			$query = $db->getQuery(true)
				->insert($db->qn('#__user_usergroup_map'))
				->columns(array(
					$db->qn('user_id'),
					$db->qn('group_id'),
				));
			
			foreach($addGroups as $group) {
				$query->values($db->q($user_id).', '.$db->q($group));
			}
			
			$db->setQuery($query);
			$db->query();
		}
		
		// Remove from Joomla! 1.6 groups
		if(!empty($removeGroups)) {
			$query = $db->getQuery(true)
				->delete($db->qn('#__user_usergroup_map'))
				->where($db->qn('user_id').' = '.$db->q($user_id));
			$groupSet = array();
			foreach($removeGroups as $group) {
				$groupSet[] = $db->q($group);
			}
			$query->where($db->qn('group_id').' IN ('.implode(', ', $groupSet).')');
			$db->setQuery($query);
			$db->query();
		}
	}
}