<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c) 2012 Roland Dalmulder / csvimproved.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsPhocadownload extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'phocadownload';
		
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
		
		// For each of the add groups, load them and make sure the user is added to them
		foreach($addGroups as $gid) {
			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__phocadownload_categories'))
				->where($db->qn('id').' = '.$db->q($gid));
			$db->setQuery($query);
			$groupData = $db->loadObject();
			if(empty($groupData)) continue;
			$mustAdd = false;
			$userList = array();
			if(empty($groupData->accessuserid)) {
				$mustAdd = true;
			} else {
				$userList = explode(',',$groupData->accessuserid);
				$mustAdd = !in_array($user_id, $userList);
			}
			if($mustAdd) {
				$userList[] = $user_id;
				$users = implode(',', $userList);
				$query = $db->getQuery(true)
					->update($db->qn('#__phocadownload_categories'))
					->set($db->qn('accessuserid').' = '.$db->q($users))
					->where($db->qn('id').' = '.$db->q($gid));
				$db->setQuery($query);
				$db->query();
			}
		}

		// For each of the remove groups, load them and make sure the user is not in them
		foreach($removeGroups as $gid) {
			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__phocadownload_categories'))
				->where($db->qn('id').' = '.$db->q($gid));
			$db->setQuery($query);
			$groupData = $db->loadObject();
			if(empty($groupData)) continue;
			$mustRemove = false;
			$userList = array();
			if(empty($groupData->accessuserid)) {
				$mustRemove = false;
			} else {
				$temp = explode(',',$groupData->accessuserid);
				foreach($temp as $t) {
					$userList[] = $t;
				}
				$mustRemove = in_array($user_id, $userList);
			}
			if($mustRemove) {
				$key = array_search($user_id, $userList);
				if($key !== false) {
					unset($userList[$key]);
				}
				$users = empty($userList) ? '' : implode(',', $userList);
				$query = $db->getQuery(true)
					->update($db->qn('#__phocadownload_categories'))
					->set($db->qn('accessuserid').' = '.$db->q($users))
					->where($db->qn('id').' = '.$db->q($gid));
				$db->setQuery($query);
				$db->query();
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
					$db->qn('title'),
					$db->qn('id'),
				))->from($db->qn('#__phocadownload_categories'));
			$db->setQuery($query);
			$res = $db->loadObjectList();
			
			if(!empty($res)) {
				foreach($res as $item) {
					$t = strtoupper(trim($item->title));
					$groups[$t] = $item->id;
				}
			}
		}
		
		return $groups;
	}
}