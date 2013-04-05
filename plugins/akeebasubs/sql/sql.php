<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsSql extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'sql';

		parent::__construct($subject, $name, $config, $templatePath);
	}

	protected function loadUserGroups($user_id, &$addGroups, &$removeGroups)
	{
		// Make sure we're configured
		if(empty($this->addGroups) && empty($this->removeGroups)) return;

		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();

		// Make sure there are subscriptions set for the user
		if(!count($subscriptions)) return;

		// Get the initial list of sql commands to run
		$addGroups = array();
		$removeGroups = array();
		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				// Enabled subscription, add groups
				if(empty($this->addGroups)) continue;
				if(!array_key_exists($level, $this->addGroups)) continue;
				$addGroups[] = $this->addGroups[$level];
			} else {
				// Disabled subscription, sql commands to run on deactivation
				if(empty($this->removeGroups)) continue;
				if(!array_key_exists($level, $this->removeGroups)) continue;
				$removeGroups[] = $this->removeGroups[$level];
			}
		}
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

		// Get the username
		$username = JFactory::getUser($user_id)->username;

		// Deactivation SQL
		if(!empty($removeGroups)) {
			foreach($removeGroups as $sql) {
				$sql = implode('; ', $sql);
				$sql = str_replace('[USERID]', $user_id, $sql);
				$sql = str_replace('[USER_ID]', $user_id, $sql);
				$sql = str_replace('[USERNAME]', $username, $sql);
				$db->setQuery($sql);
				if(version_compare(JVERSION, '3.0', 'ge')) {
					// Joomla! 3.0... Whatever!!!!
					$db->execute();
				} else {
					$db->queryBatch(false);
				}
			}
		}

		// Activation SQL
		if(!empty($addGroups)) {
			foreach($addGroups as $sql) {
				$sql = implode('; ', $sql);
				$sql = str_replace('[USERID]', $user_id, $sql);
				$sql = str_replace('[USER_ID]', $user_id, $sql);
				$sql = str_replace('[USERNAME]', $username, $sql);
				$db->setQuery($sql);
				if(version_compare(JVERSION, '3.0', 'ge')) {
					// Joomla! 3.0... Whatever!!!!
					$db->execute();
				} else {
					$db->queryBatch(false);
				}
			}
		}
	}
	protected function loadGroupAssignments()
	{
		$this->addGroups = array();
		$this->removeGroups = array();

		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$addgroupsKey = strtolower($this->name).'_addgroups';
		$removegroupsKey = strtolower($this->name).'_removegroups';
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					continue;
				}
				if(property_exists($level->params, $addgroupsKey))
				{
					$addSqlCommands = explode("\n", $level->params->$addgroupsKey);
					foreach($addSqlCommands as &$sqlCmd) {
						$sqlCmd = preg_replace('/^[\s]+|[\s;]+$/', '', $sqlCmd);
					}
					$this->addGroups[$level->akeebasubs_level_id] = array_filter($addSqlCommands);
				}
				if(property_exists($level->params, $removegroupsKey))
				{
					$removeSqlCommands = explode("\n", $level->params->$removegroupsKey);
					foreach($removeSqlCommands as &$sqlCmd) {
						$sqlCmd = preg_replace('/^[\s]+|[\s;]+$/', '', $sqlCmd);
					}
					$this->removeGroups[$level->akeebasubs_level_id] = array_filter($removeSqlCommands);
				}
			}
		}
	}

	protected function parseGroups($rawData)
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

			$groups = explode(';', $rawGroups);
			if(empty($groups)) continue;
			if(!is_array($groups)) $groups = array($groups);
			foreach($groups as &$sqlCmd) {
				$sqlCmd = trim($sqlCmd);
			}
			$levelId = $this->ASLevelToId($level);
			$ret[$levelId] = $groups;
		}

		return $ret;
	}

	protected function groupToId($title)
	{
		return -1;
	}

	protected function getGroups()
	{
		// No groups
		return array();
	}
}