<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
			$db->execute();

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
			$db->execute();
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
					$db->qn('title'),
					$db->qn('id'),
				))->from($db->qn('#__usergroups'));
			$db->setQuery($query);
			$res = $db->loadObjectList();

			if(!empty($res)) {
				foreach($res as $item) {
					$t = trim($item->title);
					$groups[$t] = $item->id;
				}
			}
		}
	}

	protected function getSelectField($level, $type)
	{
		if($type == 'add') {
			if (isset($level->params->joomla_addgroups))
			{
				$addgroups = $level->params->joomla_addgroups;
			}
			else
			{
				$addgroups = array();
			}
			return JHtml::_('access.usergroup', 'params[joomla_addgroups][]', $addgroups, array('multiple' => 'multiple', 'size' => 8, 'class' => 'input-large'), false);
		}
		if($type == 'remove') {
			if (isset($level->params->joomla_removegroups))
			{
				$removegroups = $level->params->joomla_removegroups;
			}
			else
			{
				$removegroups = array();
			}
			return JHtml::_('access.usergroup', 'params[joomla_removegroups][]', $removegroups, array('multiple' => 'multiple', 'size' => 8, 'class' => 'input-large'), false);
		}
		return '';
	}
}