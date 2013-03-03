<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c) 2012 Roland Dalmulder / csvimproved.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsKunena extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'kunena';
		
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
		
		foreach($addGroups as $gid) {
			$query = $db->getQuery(true)
				->update($db->qn('#__kunena_users'))
				->set($db->qn('rank').' = '.$db->q($gid))
				->where($db->qn('userid').' = '.$db->q($user_id));
			$db->setQuery($query);
			$db->execute();
		}

		foreach($removeGroups as $gid) {
			$query = $db->getQuery(true)
				->update($db->qn('#__kunena_users'))
				->set($db->qn('rank').' = '.$db->q($gid))
				->where($db->qn('userid').' = '.$db->q($user_id));
			$db->setQuery($query);
			$db->execute();
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
					$db->qn('rank_title'),
					$db->qn('rank_id'),
				))
				->from($db->qn('#__kunena_ranks'));
			$db->setQuery($query);
			$res = $db->loadObjectList();
			
			if(!empty($res)) {
				foreach($res as $item) {
					$t = strtoupper(trim($item->rank_title));
					$groups[$t] = $item->rank_id;
				}
			}
		}
		
		return $groups;
	}
}