<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
		
		parent::__construct($subject, $name, $config, $templatePath);
	}

	public function onAKUserRefresh($user_id)
	{
		// Load groups
		$addGroups = array();
		$removeGroups = array();
		$this->loadUserGroups($user_id, $addGroups, $removeGroups);
		if(empty($addGroups) && empty($removeGroups)) return;
		
		// Get DB connection
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__k2_users'))
			->where($db->qn('userID').' = '.$db->q($user_id));
		$db->setQuery($query);
		$numRecords = $db->loadResult();
		
		if(!empty($addGroups)) {
			// Case 1: Add to groups
			$group = array_pop($addGroups);
			if($numRecords) {
				// Case 1a. Update an existing record
				$query = $db->getQuery(true)
					->update($db->qn('#__k2_users'))
					->set($db->qn('group').' = '.$db->q($group))
					->where($db->qn('userID').' = '.$db->q($user_id));
				$db->setQuery($query);
				$db->execute();
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
				$db->execute();
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
					$db->execute();
				}
			} else {
				
			}
		}
	}
	
	protected function getGroups()
	{
		static $groups = null;
		
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
					$t = trim($item->title);
					$groups[$t] = $item->id;
				}
			}
		}
		
		return $groups;
	}
}