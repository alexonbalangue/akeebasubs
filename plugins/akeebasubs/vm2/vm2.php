<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsVm2 extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'vm2';
		
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
		
		// Add to VM
		if(!empty($addGroups)) {
			// 1. Delete existing assignments; required to prevent a failure from
			//   the INSERT command in the second step
			$groupSet = array();
			foreach($addGroups as $group) {
				$groupSet[] = $db->q($group);
			}
			$query = $db->getQuery(true)
				->delete($db->qn('#__virtuemart_vmuser_shoppergroups'))
				->where($db->qn('virtuemart_user_id').' = '.$db->q($user_id))
				->where($db->qn('virtuemart_shoppergroup_id').' IN ('.implode(', ', $groupSet).')');
			$db->setQuery($query);
			$db->execute();
			
			// 2. Add new assignments with a big INSERT query
			$query = $db->getQuery(true)
				->insert($db->qn('#__virtuemart_vmuser_shoppergroups'))
				->columns(array(
					$db->qn('virtuemart_user_id'),
					$db->qn('virtuemart_shoppergroup_id'),
				));
			
			foreach($addGroups as $group) {
				$query->values($db->q($user_id).', '.$db->q($group));
			}
			
			$db->setQuery($query);
			$db->execute();
		}
		
		// Remove from VM shopper groups
		if(!empty($removeGroups)) {
			$query = $db->getQuery(true)
				->delete($db->qn('#__virtuemart_vmuser_shoppergroups'))
				->where($db->qn('virtuemart_user_id').' = '.$db->q($user_id));
			$groupSet = array();
			foreach($removeGroups as $group) {
				$groupSet[] = $db->q($group);
			}
			
			$query->where($db->qn('virtuemart_shoppergroup_id').' IN ('.implode(', ', $groupSet).')');
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
					$db->qn('shopper_group_name').' AS '.$db->qn('title'),
					$db->qn('virtuemart_shoppergroup_id').' AS '.$db->qn('id'),
				))->from($db->qn('#__virtuemart_shoppergroups'))
				->where($db->qn('virtuemart_vendor_id').' = '.$db->q('1'));
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