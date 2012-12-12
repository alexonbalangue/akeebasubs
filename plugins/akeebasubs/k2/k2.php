<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsK2 extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'k2';
		
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
					$db->qn('name').' AS '.$db->qn('title'),
					$db->qn('id'),
				))->from($db->qn('#__k2_user_groups'));
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
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__k2_users'))
			->where($db->qn('userID').' = '.$db->q($user_id));
		$db->setQuery($query);
		$numRecords = $db->loadResult();
		
		if(empty($addGroups) && empty($removeGroups)) {
			// Case 1: Don't add to groups, don't remove from groups
			return;
		} elseif(!empty($addGroups)) {
			// Case 1: Add to groups
			if($numRecords) {
				// Case 1a. Update an existing record
				$group = array_pop($addGroups);
				
				$query = $db->getQuery(true)
					->update($db->qn('#__k2_users'))
					->set($db->qn('group').' = '.$db->q($group))
					->where($db->qn('userID').' = '.$db->q($user_id));
				$db->setQuery($query);
				$db->query();
			} else {
				// Case 1b. Add a new record
				$user = JFactory::getUser($user_id);
				
				$query = $db->getQuery(true)
					->insert($db->qn('#__k2_users'))
					->columns(array(
						$db->qn('userID'),
						$db->qn('group'),
						$db->qn('userName'),
						$db->qn('description'),
					))->values(
						$db->q($user_id).', '.$db->q($group).', '.$db->q($user->name).', '.$db->q('')
					);
				$db->setQuery($query);
				$db->query();
			}
		} elseif(!empty($removeGroups)) {
			// Case 2: Don't add to groups, remove from groups
			if($numRecords) {
				// Case 2a. Update an existing record
				$query = $db->getQuery(true)
					->select($db->qn('group'))
					->from($db->qn('#__k2_users'))
					->where($db->qn('userID').' = '.$db->q($user_id));
				$db->setQuery($query);
				$group = $db->loadResult();
				if(in_array($group, $removeGroups)) {
					$query = $db->getQuery(true)
						->update($db->qn('#__k2_users'))
						->set($db->qn('group').' = '.$db->q('0'))
						->where($db->qn('userID').' = '.$db->q($user_id));
					$db->setQuery($query);
					$db->query();
				}
			} else {
				
			}
		}
	}
}