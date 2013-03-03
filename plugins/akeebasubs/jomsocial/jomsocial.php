<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsJomsocial extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'jomsocial';
		
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
		
		// Add to JomSocial groups
		if(!empty($addGroups)) {
			foreach($addGroups as $group) {
				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from($db->qn('#__community_groups_members'))
					->where($db->qn('memberid').' = '.$db->q($user_id))
					->where($db->qn('groupid').' = '.$db->q($group));
				$db->setQuery($query);
				$count = $db->loadResult();

				if($count) {
					// Update record
					$query = $db->getQuery(true)
						->update($db->qn('#__community_groups_members'))
						->set(array(
							$db->qn('approved').' = '.$db->q('1'),
							$db->qn('permissions').' = '.$db->q('0')
						))
						->where($db->qn('memberid').' = '.$db->q($user_id))
						->where($db->qn('groupid').' = '.$db->q($group));
				} else {
					// Insert record
					$query = $db->getQuery(true)
						->insert($db->qn('#__community_groups_members'))
						->columns(array(
							$db->qn('memberid'),
							$db->qn('groupid'),
							$db->qn('approved'),
							$db->qn('permissions'),
						))->values(
							$db->q($user_id).', '.$db->q($group).', '.$db->q('1').', '.$db->q('0')
						);
				}
				
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		// Remove from JomSocial groups
		if(!empty($removeGroups)) {
			$protoQuery = $db->getQuery(true)
				->delete($db->qn('#__community_groups_members'))
				->where($db->qn('memberid').' = '.$db->q($user_id));
			//$protoSQL = 'DELETE FROM `#__community_groups_members` WHERE `memberid` = ' . $db->q($user_id) . ' AND `groupid` = ';
			foreach($removeGroups as $group) {
				$query = clone $protoQuery;
				$query->where($db->qn('groupid').' = '.$db->q($group));
				$db->setQuery($query);
				$db->execute();
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
				))->from($db->qn('#__community_groups'));
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