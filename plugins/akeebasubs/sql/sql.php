<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2015 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\PluginAbstracts\AkeebasubsBase;

class plgAkeebasubsSql extends AkeebasubsBase
{
	public function __construct(& $subject, $config = array())
	{
		$config['templatePath'] = dirname(__FILE__);
		$config['name'] = 'sql';

		parent::__construct($subject, $config);
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param   int  $user_id  The Joomla! user ID to refresh information for.
	 *
	 * @return  void
	 */
	public function onAKUserRefresh($user_id)
	{
		if (empty($this->addGroups) && empty($this->removeGroups))
		{
			return;
		}

		// Get DB connection
		$db = JFactory::getDBO();

		// Get the user data
		$user = JFactory::getUser($user_id);

		/** @var \Akeeba\Subscriptions\Admin\Model\Users $usersModel */
		$usersModel = $this->container->factory->model('Users')->tmpInstance();

		$mergedData = $usersModel->getMergedData($user_id);
		$mergedData = (array)$mergedData;

		// Run the deactivation and activation SQL
		foreach ([$this->removeGroups, $this->addGroups] as $sqlList)
		{
			if (!empty($sqlList))
			{
				foreach ($sqlList as $sql)
				{
					$sql = $this->replaceVariables($sql, $user, $mergedData);
					$db->setQuery($sql);

					try
					{
						$db->execute();
					}
					catch (\Exception $e)
					{
						// Do not halt on SQL error
					}
				}
			}
		}
	}

	/**
	 * Replace variables in the SQL command
	 *
	 * @param   mixed   $sql         The raw SQL command (array or string)
	 * @param   \JUser  $user        The user object
	 * @param   array   $mergedData  User profile data
	 *
	 * @return  string  The SQL command with all variables replaced
	 */
	protected function replaceVariables($sql, JUser $user, array $mergedData)
	{
		if (is_array($sql))
		{
			$sql = implode('; ', $sql);
		}

		foreach ($mergedData as $k => $v)
		{
			if (!is_string($v))
			{
				continue;
			}

			$variableName = '[' . strtoupper($k) . ']';
			$sql = str_replace($variableName, $v, $sql);
		}

		$sql = str_replace('[USERID]', $user->id, $sql);
		$sql = str_replace('[USER_ID]', $user->id, $sql);
		$sql = str_replace('[USERNAME]', $user->username, $sql);
		$sql = str_replace('[NAME]', $user->name, $sql);
		$sql = str_replace('[EMAIL]', $user->email, $sql);

		return $sql;
	}

	/**
	 * Load the add / remove group to level ID map from the subscription level options
	 *
	 * @return  void
	 */
	protected function loadGroupAssignments()
	{
		// First call the parent
		parent::loadGroupAssignments();

		// And now convert newline-separated strings into an array.
		if (!empty($this->addGroups))
		{
			foreach ($this->addGroups as $levelId => &$addGroups)
			{
				$addSqlCommands = explode("\n", $addGroups);

				foreach ($addSqlCommands as &$sqlCmd)
				{
					$sqlCmd = preg_replace('/^[\s]+|[\s;]+$/', '', $sqlCmd);
				}

				$addGroups = array_filter($addSqlCommands);
			}
		}

		if (!empty($this->removeGroups))
		{
			foreach ($this->removeGroups as $levelId => &$removeGroups)
			{
				$removeSqlCommands = explode("\n", $removeGroups);

				foreach ($removeSqlCommands as &$sqlCmd)
				{
					$sqlCmd = preg_replace('/^[\s]+|[\s;]+$/', '', $sqlCmd);
				}

				$removeGroups = array_filter($removeSqlCommands);
			}
		}
	}
}