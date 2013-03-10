<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsProjectfork4 extends plgAkeebasubsAbstract
{
	private $addLevels = array();
	private $removeLevels = array();
	private $author_id = -1;
	private $category = 0;

	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'projectfork4';
		
		parent::__construct($subject, $name, $config, $templatePath, false);
		
		// Get the author
		$configParams = @json_decode($config['params']);
		$author_name = $configParams->author;
		if($author_name) {
			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->select($db->qn('id'))
				->from($db->qn('#__users'))
				->where($db->qn('username') . ' = ' . $db->q($author_name));
			$db->setQuery($query);
			$author_id = $db->loadResult();
			if(!empty($author_id)) {
				$this->author_id = $author_id;
			}
		}
		
		// Get category
		$this->category = $configParams->category[0];
	}
	
	/**
	 * Add the custom field to the subscription from that allow to enter the project name.
	 */
	function onSubscriptionFormRender($userparams, $cache)
	{
		$level = $cache['id'];
		if(in_array($level, $this->addLevels)) {
			// Current value of the field
			if(array_key_exists('projectfork', $cache['custom'])) {
				$current = $cache['custom']['projectfork'];
			} else {
				if(!is_object($userparams->params)) {
					$current = '';
				} else {
					$current = property_exists($userparams->params, 'projectfork') ? $userparams->params->projectfork : '';
				}
			}
			if(empty($current)) {
				// Take the existing title if the project already exists
				$user_id = $userparams->user_id;
				$current = $this->getProjectTitle($user_id, $level);
			}
			$html = '<input type="text" name="custom[projectfork]" id="projectfork" value="'.htmlentities($current).'" />';

			// Setup the field
			$pf_field[] = array(
				'id'			=> 'projectfork',
				'label'			=> JText::_('PLG_AKEEBASUBS_PROJECTFORK4_SUBSCRIPTIONFORM_TITLE'),
				'elementHTML'	=> $html,
				'invalidLabel'	=> JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED'),
				'isValid'		=> true
			);
			return $pf_field;
		}
	}
	
	protected function loadGroupAssignments()
	{
		$this->addLevels = array();
		$this->removeLevels = array();
		
		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$addKey = 'projectfork4_add';
		$removeKey = 'projectfork4_remove';
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
				if(property_exists($level->params, $addKey) && $level->params->$addKey)
				{
					$this->addLevels[] = $level->akeebasubs_level_id;
				}
				if(property_exists($level->params, $removeKey) && $level->params->$removeKey)
				{
					$this->removeLevels[] = $level->akeebasubs_level_id;
				}
			}
		}
	}
	
	/**
	 * Called whenever the administrator asks to refresh integration status.
	 * 
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Any need to run?
		if(empty($this->addLevels) && empty($this->removeLevels)) return;
		// We expect these tables to exist for this plugin
		if(!$this->doesTableExist('#__pf_projects')
				|| !$this->doesTableExist('#__akeebasubs_pf4_projects')) {
			return;
		}
		
		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();
	
		$levelsToAdd = array();
		$levelsToRemove = array();
		$userProjectTitle = '';
		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				if(in_array($level, $this->addLevels) && !in_array($level, $levelsToAdd)) {
					$params = json_decode($sub->userparams);
					$userProjectTitle = $params->projectfork;
					$levelsToAdd[] = $level;
				}
			} else {
				if(in_array($level, $this->removeLevels) && !in_array($level, $levelsToRemove)) {
					$levelsToRemove[] = $level;
				}
			}
		}
		
		// Create project
		foreach($levelsToAdd as $level) {
			// Create or update project
			$author_id = ($this->author_id < 0) ? $user_id : $this->author_id;
			$user = JUser::getInstance($user_id);
			// Get existing title
			$newProjectTitle = $this->getProjectTitle($user_id, $level);
			if(empty($newProjectTitle)) {
				$newProjectTitle = $userProjectTitle;
				if(empty($newProjectTitle)) {
					// If no title is entered, set a default one in the form "USERNAME - LEVEL1 - 2012/08/03"
					$level_title = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
						->setId($level->akeebasubs_level_id)
						->getItem()
						->title;
					$newProjectTitle = strtoupper($user->name) . ' - ' . strtoupper($level_title) . ' - ' . date('Y/m/d');	
				} else {
					// Don't accept special character in the title
					$newProjectTitle = preg_replace('/[^\w- ]+/', '', $newProjectTitle);
				}
			}
			$proj_id = $this->getProjectId($user_id, $level);
			if($proj_id) {
				$this->updateProjectTitle($proj_id, $newProjectTitle);
				$this->activateArchivedProject($proj_id);
			} else {
				$proj_id = $this->createProject($user_id, $level, $author_id, $newProjectTitle, $this->category);
			}
		}

		// Archive projects
		foreach($levelsToRemove as $level) {
			if(! in_array($level, $levelsToAdd)) {
				$proj_id = $this->getProjectId($user_id, $level);
				if($proj_id) {
					$this->archiveProject($proj_id);
				}
			}
		}
	}

	/**
	 * Returns the id of the project or null if it doesn't exist.
	 */
	private function getProjectId($user_id, $level_id)
	{
		// Check if reference to this project is stored
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select($db->qn('pf_projects_id'))
			->from($db->qn('#__akeebasubs_pf4_projects'))
			->where($db->qn('users_id') . ' = ' . $db->q($user_id))
			->where($db->qn('akeebasubs_level_id') . ' = ' . $db->q($level_id));
		$db->setQuery($query);
		$proj_id = $db->loadResult();
		if($proj_id == null) return null;
		
		// Check if project actually exists
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__pf_projects'))
			->where($db->qn('id') . ' = ' . $db->q($proj_id));
		$db->setQuery($query);
		$proj_count = $db->loadResult();
		if(! ($proj_count > 0)) {
			// Clean-up: Delete the reference if the project doesn't exist
			$query = $db->getQuery(true)
				->delete($db->qn('#__akeebasubs_pf4_projects'))
				->where($db->qn('pf_projects_id') . ' = ' . $db->q($proj_id));
			$db->setQuery($query);
			$db->execute();
			return null;
		}
		
		return $proj_id;
	}

	/**
	 * Returns the title of the project or an empty string if it doesn't exist.
	 */
	private function getProjectTitle($user_id, $level_id)
	{
		// Check if reference to this project is stored
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select($db->qn('pf_projects_id'))
			->from($db->qn('#__akeebasubs_pf4_projects'))
			->where($db->qn('users_id') . ' = ' . $db->q($user_id))
			->where($db->qn('akeebasubs_level_id') . ' = ' . $db->q($level_id));
		$db->setQuery($query);
		$proj_id = $db->loadResult();
		if($proj_id == null) return "";
		
		// Get the title
		$query = $db->getQuery(true)
			->select($db->qn('title'))
			->from($db->qn('#__pf_projects'))
			->where($db->qn('id') . ' = ' . $db->q($proj_id));
		$db->setQuery($query);
		$proj_title = $db->loadResult();
		if($proj_title == null) return "";
		
		return $proj_title;
	}
	
	/**
	 * Creates a new PF project, assigns the user to it and
	 * stores the reference about the project in our DB table.
	 */
	private function createProject($user_id, $level_id, $author_id, $title, $category)
	{
		$db = JFactory::getDBO();
		
		// Set alias
		$alias = strtolower($title);
		$alias = preg_replace('/\s+/', '-', $alias);
		$alias = preg_replace('/[^a-z0-9-]+/', '', $alias);
		$newAlias = $alias;
		$i = 0;
		while(true) {
			if(! $this->doesAliasExist($newAlias)) {
				break;
			}
			$newAlias = $alias . '-' . $i++;
		}
		
		// Create the project
		$now = strftime("%Y-%m-%d %H:%M:%S");
		$query = $db->getQuery(true)
			->insert($db->qn('#__pf_projects'))
			->columns(array(
				$db->qn('catid'),
				$db->qn('title'),
				$db->qn('alias'),
				$db->qn('created'),
				$db->qn('created_by'),
				$db->qn('access'),
				$db->qn('state')
			))
			->values($db->q($category) . ', '
					. $db->q($title) . ', '
					. $db->q($newAlias) . ', '
					. $db->q($now) . ', '
					. $db->q($author_id) . ', '
					. $db->q('1') . ', '
					. $db->q('1')
			);
		$db->setQuery($query);
		$db->execute();
		$proj_id = $db->insertid();

		// Save the reference about the project
		$query = $db->getQuery(true)
			->insert($db->qn('#__akeebasubs_pf4_projects'))
			->columns(array(
				$db->qn('users_id'),
				$db->qn('akeebasubs_level_id'),
				$db->qn('pf_projects_id')
			))
			->values($db->q($user_id) . ', '
					. $db->q($level_id) . ', '
					. $db->q($proj_id)
			);
		$db->setQuery($query);
		$db->execute();
		
		return $proj_id;
	}
	
	/**
	 * Sets/updates the project's title.
	 */
	private function updateProjectTitle($project_id, $project_title)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->update($db->qn('#__pf_projects'))
			->set($db->qn('title') . ' = ' . $db->q($project_title))
			->where($db->qn('id')  .' = '. $db->q($project_id))
			->where($db->qn('title') . ' != '. $db->q($project_title));
		$db->setQuery($query);
		$db->execute();
	}
	
	/**
	 * Archives the project.
	 */
	private function archiveProject($project_id)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->update($db->qn('#__pf_projects'))
			->set($db->qn('state') . ' = ' . $db->q('2'))
			->where($db->qn('id') .' = '. $db->q($project_id));
		$db->setQuery($query);
		$db->execute();
		if($db->getError()) die($db->getError());
	}
	
	/**
	 * Activates/publishes the project.
	 */
	private function activateArchivedProject($project_id)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->update($db->qn('#__pf_projects'))
			->set($db->qn('state') . ' = ' . $db->q('1'))
			->where($db->qn('id') .' = '. $db->q($project_id))
			->where($db->qn('state') . ' != ' . $db->q('1'));
		$db->setQuery($query);
		$db->execute();
		if($db->getError()) die($db->getError());
	}
	
	private function doesAliasExist($alias)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select($db->qn('COUNT(*)'))
			->from($db->qn('#__pf_projects'))
			->where($db->qn('alias') . ' = ' . $db->q($alias));
		$db->setQuery($query);
		$count = $db->loadResult();
		if(empty($count) || $count < 1) {
			return false;
		}
		return true;
	}
	
	private function doesTableExist($tableName)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn($tableName));
		$db->setQuery($query);
		$result = $db->loadResult();
		return $result != null;
	}
	
	protected function groupToId($title) {
		// do nothing
	}

	protected function getGroups() {
		// do nothing
	}
	
	protected function getSelectField($level, $type)
	{
		$opts = array();
		$opts[] = JHTML::_('select.option', '0', JText::_('JNo'));
		$opts[] = JHTML::_('select.option', '1', JText::_('JYes'));
		if($type == 'add') {
			$selected = in_array($level->akeebasubs_level_id, $this->addLevels);
			return JHTML::_('select.radiolist',  $opts, 'params[projectfork4_add]', '', 'value', 'text', (int)$selected, 'projectfork4_add');
		}
		if($type == 'remove') {
			$selected = in_array($level->akeebasubs_level_id, $this->removeLevels);
			return JHTML::_('select.radiolist',  $opts, 'params[projectfork4_remove]', '', 'value', 'text', (int)$selected, 'projectfork4_remove');
		}
		return '';
	}
}