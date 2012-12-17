<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $name, $config);
		
		$this->templatePath = $templatePath;
		$this->name = $name;
		
		$this->loadLanguage();
		
		// Do we have values from the Olden Days?
		$strAddGroups = $this->params->get('addgroups','');
		$strRemoveGroups = $this->params->get('removegroups','');

		if(!empty($strAddGroups) || !empty($strAddGroups)) {
			// Load level to group mapping from plugin parameters		
			$this->addGroups = $this->parseGroups($strAddGroups);
			$this->removeGroups = $this->parseGroups($strRemoveGroups);
			// Do a transparent upgrade
			$this->upgradeSettings();
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
		jimport('joomla.filesystem.file');
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
				$save = false;
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
					$this->addGroups[$level->akeebasubs_level_id] = $level->params->$addgroupsKey;
				}
				if(property_exists($level->params, $removegroupsKey))
				{
					$this->removeGroups[$level->akeebasubs_level_id] = $level->params->$removegroupsKey;
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
	protected function upgradeSettings()
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
		$this->params->set('addgroups', '');
		$this->params->set('removegroups', '');
		$param_string = $this->params->toString();
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->where($db->qn('type').'='.$db->q('plugin'))
			->where($db->qn('element').'='.$db->q(strtolower($this->name)))
			->where($db->qn('folder').'='.$db->q('akeebasubs'))
			->set($db->qn('params').' = '.$db->q($param_string));
		$db->setQuery($query);
		$db->query();
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
	
	abstract protected function MyGroupToId($title);

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
				$groupIds[] = $this->MyGroupToId($groupTitle);
			}
			
			$ret[$levelId] = $groupIds;
		}
		
		return $ret;
	}
}