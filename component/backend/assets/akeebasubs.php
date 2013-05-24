<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * Akeeba Subscriptions payment plugin abstract class
 */
abstract class plgAkeebasubsAbstract extends JPlugin
{
	/** @var array Levels to Groups to Add mapping */
	protected $addGroups = array();

	/** @var array Levels to Groups to Remove mapping */
	protected $removeGroups = array();

	protected $templatePath = '';

	protected $name = '';

	public function __construct(&$subject, $name, $config = array(), $templatePath = null)
	{
		if(!array_key_exists('params', $config))
		{
			$config['params'] = new JRegistry('');
		}
		if(!is_object($config['params'])) {
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $name, $config);

		if (is_array($name))
		{
			$config = $name;
			$name = $config['name'];
		}
		$this->templatePath = $templatePath;
		$this->name = $name;

		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akeebasubs_'.$name, JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akeebasubs_'.$name, JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akeebasubs_'.$name, JPATH_ADMINISTRATOR, null, true);

		// Do we have values from the Olden Days?
		if(isset($config['params'])) {
			$configParams = @json_decode($config['params']);
			if(property_exists($configParams, 'addgroups'))
			{
				$strAddGroups = $configParams->addgroups;
			}
			else
			{
				$strAddGroups = null;
			}
			if(property_exists($configParams, 'removegroups'))
			{
				$strRemoveGroups = $configParams->removegroups;
			}
			else
			{
				$strRemoveGroups =  null;
			}
		}

		if(!empty($strAddGroups) || !empty($strRemoveGroups)) {
			// Load level to group mapping from plugin parameters
			$this->addGroups = $this->parseGroups($strAddGroups);
			$this->removeGroups = $this->parseGroups($strRemoveGroups);
			// Do a transparent upgrade
			$this->upgradeSettings($config);
		} else {
			$this->loadGroupAssignments();
		}
	}

	/**
	 * Renders the configuration page in the component's back-end
	 *
	 * @param AkeebasubsTableLevel $level
	 * @return object
	 */
	public function onSubscriptionLevelFormRender(AkeebasubsTableLevel $level)
	{
		JLoader::import('joomla.filesystem.file');
		$filename = $this->templatePath.'/override/default.php';
		if(!JFile::exists($filename)) {
			$filename = $this->templatePath.'/tmpl/default.php';
		}

		$addgroupsKey = strtolower($this->name).'_addgroups';
		$removegroupsKey = strtolower($this->name).'_addgroups';
		if(!property_exists($level->params, $addgroupsKey)) {
			$level->params->$addgroupsKey = array();
		}
		if(!property_exists($level->params, $removegroupsKey)) {
			$level->params->$removegroupsKey = array();
		}

		@ob_start();
		include_once $filename;
		$html = @ob_get_clean();

		$ret = (object)array(
			'title'	=> JText::_('PLG_AKEEBASUBS_'.  strtoupper($this->name).'_TAB_TITLE'),
			'html'	=> $html
		);

		return $ret;
	}

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;
		if(array_key_exists('enabled', (array)$info['modified'])) {
			$this->onAKUserRefresh($row->user_id);
		}
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	abstract public function onAKUserRefresh($user_id);

	protected function loadUserGroups($user_id, &$addGroups, &$removeGroups, $addGroupsVarName = 'addGroups', $removeGroupsVarName = 'removeGroups')
	{
		// Make sure we're configured
		if(empty($this->$addGroupsVarName) && empty($this->$removeGroupsVarName)) return;

		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();

		// Make sure there are subscriptions set for the user
		if(!count($subscriptions)) return;

		// Get the initial list of groups to add/remove from
		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				// Enabled subscription, add groups
				if(empty($this->$addGroupsVarName)) continue;
				if(!array_key_exists($level, $this->$addGroupsVarName)) continue;
				$addGroupsVar = $this->$addGroupsVarName;
				$groups = $addGroupsVar[$level];
				foreach($groups as $group) {
					if(!in_array($group, $addGroups)) {
						if(is_numeric($group) && !($group > 0)) continue;
						$addGroups[] = $group;
					}
				}
			} else {
				// Disabled subscription, remove groups
				if(empty($this->$removeGroupsVarName)) continue;
				if(!array_key_exists($level, $this->$removeGroupsVarName)) continue;
				$removeGroupsVar = $this->$removeGroupsVarName;
				$groups = $removeGroupsVar[$level];

				foreach($groups as $group) {
					if(!in_array($group, $removeGroups)) {
						if(is_numeric($group) && !($group > 0)) continue;
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
					$this->addGroups[$level->akeebasubs_level_id] = array_filter($level->params->$addgroupsKey);
				}
				if(property_exists($level->params, $removegroupsKey))
				{
					$this->removeGroups[$level->akeebasubs_level_id] = array_filter($level->params->$removegroupsKey);
				}
			}
		}
	}

	/**
	 * =========================================================================
	 * !!! CRUFT WARNING !!!
	 * =========================================================================
	 *
	 * The following methods are leftovers from the Olden Days (before 2.4.5).
	 * At some point (most likely 2.6) they will be removed. For now they will
	 * stay here so that we can do a transparent migration.
	 */

	/**
	 * Moves this plugin's settings from the plugin into each subscription
	 * level's configuration parameters.
	 */
	protected function upgradeSettings($config = array())
	{
		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$addgroupsKey = strtolower($this->name).'_addgroups';
		$removegroupsKey = strtolower($this->name).'_removegroups';
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				$save = false;
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					$level->params = new stdClass();
				}
				if(array_key_exists($level->akeebasubs_level_id, $this->addGroups)) {
					if(empty($level->params->$addgroupsKey)) {
						$level->params->$addgroupsKey = $this->addGroups[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				if(array_key_exists($level->akeebasubs_level_id, $this->removeGroups)) {
					if(empty($level->params->$removegroupsKey)) {
						$level->params->$removegroupsKey = $this->removeGroups[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				if($save) {
					$level->params = json_encode($level->params);
					$result = $model->setId($level->akeebasubs_level_id)->save( $level );
				}
			}
		}

		// Remove the plugin parameters
		if(isset($config['params'])) {
			$configParams = @json_decode($config['params']);
			unset($configParams->addgroups);
			unset($configParams->removegroups);
			$param_string = @json_encode($configParams);

			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->update($db->qn('#__extensions'))
				->where($db->qn('type').'='.$db->q('plugin'))
				->where($db->qn('element').'='.$db->q(strtolower($this->name)))
				->where($db->qn('folder').'='.$db->q('akeebasubs'))
				->set($db->qn('params').' = '.$db->q($param_string));
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Converts an Akeeba Subscriptions level to a numeric ID
	 *
	 * @param $title string The level's name to be converted to an ID
	 *
	 * @return int The subscription level's ID or -1 if no match is found
	 */
	protected function ASLevelToId($title)
	{
		static $levels = null;

		// Don't process invalid titles
		if(empty($title)) return -1;

		// Fetch a list of subscription levels if we haven't done so already
		if(is_null($levels)) {
			$levels = array();
			$list = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->getList();
			if(count($list)) foreach($list as $level) {
				$thisTitle = strtoupper($level->title);
				$levels[$thisTitle] = $level->akeebasubs_level_id;
			}
		}

		$title = strtoupper($title);
		if(array_key_exists($title, $levels)) {
			// Mapping found
			return($levels[$title]);
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}

	protected function groupToId($title)
	{
		if(empty($title)) return -1;

		$groups = $this->getGroups();

		$title = strtoupper(trim($title));
		foreach($groups as $key => $val) {
			if(strtoupper($key) == $title) {
				return $val;
			}
		}
		if( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}

	abstract protected function getGroups();

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

			$groups = explode(',', $rawGroups);
			if(empty($groups)) continue;
			if(!is_array($groups)) $groups = array($groups);

			$levelId = $this->ASLevelToId($level);
			$groupIds = array();
			foreach($groups as $groupTitle) {
				$groupIds[] = $this->groupToId($groupTitle);
			}

			$ret[$levelId] = $groupIds;
		}

		return $ret;
	}

	protected function getSelectField($level, $type)
	{
		if(! in_array($type, array('add', 'remove'))) return '';
		// Put groups in select field
		$groups = $this->getGroups();
		$options = array();
		$options[] = JHTML::_('select.option','',JText::_('PLG_AKEEBASUBS_' . strtoupper($this->name) . '_NONE'));
		foreach($groups as $title => $id) {
			$options[] = JHTML::_('select.option',$id,$title);
		}
		// Set pre-selected values
		$selected = array();
		if($type == 'add') {
			if(! empty($this->addGroups[$level->akeebasubs_level_id])) {
				$selected = $this->addGroups[$level->akeebasubs_level_id];
			}
		} else {
			if(! empty($this->removeGroups[$level->akeebasubs_level_id])) {
				$selected = $this->removeGroups[$level->akeebasubs_level_id];
			}
		}
		// Create the select field
		return JHtmlSelect::genericlist($options, 'params[' . strtolower($this->name) . '_' . $type . 'groups][]', 'multiple="multiple" size="8" class="input-large"', 'value', 'text', $selected);
	}
}