<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsProjectfork extends JPlugin
{
	private $levels = array();
	private $archive = false;
	private $author_id = 0;

	public function __construct(& $subject, $config = array())
	{		
		if(!is_object($config['params'])) {
			JLoader::import('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akeebasubs_projectfork', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akeebasubs_projectfork', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akeebasubs_projectfork', JPATH_ADMINISTRATOR, null, true);

		// Initialise the class parameters
		$this->levels = $this->params->get('level_ids', array());
		$this->archive = $this->params->get('archive', false);
		$author_name = trim($this->params->get('author', ''));
		if($author_name) {
			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->select($db->qn('id'))
				->from($db->qn('#__users'))
				->where($db->qn('username') . ' = ' . $db->q($author_name));
			$db->setQuery($query);
			$this->author_id = $db->loadResult();
		}
	}
	
	/**
	 * Add the custom field to the subscription from that allow to enter the project name.
	 */
	function onSubscriptionFormRender($userparams, $cache)
	{
		$level = $cache['id'];
		if(in_array($level, $this->levels)) {
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
			$html = '<input type="text" name="custom[projectfork]" id="projectfork" value="'.htmlentities($current).'" class="main" />';

			// Setup the field
			$pf_field[] = array(
				'id'			=> 'projectfork',
				'label'			=> JText::_('PLG_AKEEBASUBS_PROJECTFORK_SUBSCRIPTIONFORM_TITLE'),
				'elementHTML'	=> $html,
				'invalidLabel'	=> JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED'),
				'isValid'		=> true
			);
			return $pf_field;
		}
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
	public function onAKUserRefresh($user_id)
	{
		// Any need to run?
		if(empty($this->levels) && !$this->archive) return;
		if(!$this->doesTableExist('#__pf_projects')
				|| !$this->doesTableExist('#__pf_project_members')
				|| !$this->doesTableExist('#__pf_groups')
				|| !$this->doesTableExist('#__pf_group_users')
				|| !$this->doesTableExist('#__akeebasubs_pf_projects')) {
			return;
		}
		
		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();
			
		// Make sure there are subscriptions set for the user
		if(!count($subscriptions)) return;
		
		// Check all subscriptions in order to add or archive (unpublish) ProjectFork projects
		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				// Create projects
				if(empty($this->levels)) continue;
				if(! in_array($level, $this->levels)) continue;
				
				// 1) Add the project
				$proj_id = $this->getProjectId($user_id, $level);
				preg_match('/projectfork[^,]*:([^,]*)/', $sub->userparams, $matches);
				$proj_title = trim($matches[1], '"{} ');
				$author_id = $this->author_id ? $this->author_id : $user_id;
				if(! $proj_title) {
					// If no title is entered, set a default one in the form "USERNAME - LEVEL1 - 2012/08/03"
					$level_title = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
						->setId($sub->akeebasubs_level_id)
						->getItem()
						->title;
					$proj_title = strtoupper($sub->username) . ' - ' . strtoupper($level_title) . ' - ' . date('Y/m/d');
				}
				if($proj_id) {
					$this->updateProjectTitle($proj_id, $proj_title);
					$this->activateArchivedProject($proj_id);
				} else {
					$proj_id = $this->createProject($user_id, $level, $author_id, $proj_title);
				}
				// 2) Add the group
				$group_id = $this->getGroupId($proj_id);
				if(! $group_id) {
					$group_id = $this->createGroup($proj_id, $user_id);
				}
			} else {
				// Archive projects
				if($this->archive) {
					$proj_id = $this->getProjectId($user_id, $level);
					if($proj_id) {
						$this->archiveProject($proj_id);
					}	
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
			->from($db->qn('#__akeebasubs_pf_projects'))
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
			// Delete the reference if the project doesn't exist
			$query = $db->getQuery(true)
				->delete($db->qn('#__akeebasubs_pf_projects'))
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
			->from($db->qn('#__akeebasubs_pf_projects'))
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
	private function createProject($user_id, $level_id, $author_id, $title)
	{
		$db = JFactory::getDBO();
		
		// Create the project
		$query = $db->getQuery(true)
			->insert($db->qn('#__pf_projects'))
			->columns(array(
				$db->qn('title'),
				$db->qn('author'),
				$db->qn('approved'),
				$db->qn('cdate')
			))
			->values($db->q($title) . ', '
					. $db->q($author_id) . ', '
					. $db->q('1') . ', '
					. $db->q(time())
			);
		$db->setQuery($query);
		$db->execute();
		$proj_id = $db->insertid();

		// Save the reference about the project
		$query = $db->getQuery(true)
			->insert($db->qn('#__akeebasubs_pf_projects'))
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
		
		// Make user a project member
		$query = $db->getQuery(true)
			->insert($db->qn('#__pf_project_members'))
			->columns(array(
				$db->qn('project_id'),
				$db->qn('user_id'),
				$db->qn('approved')
			))
			->values($db->q($proj_id) . ', '
					. $db->q($user_id) . ', '
					. $db->q('1')
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
	 * Archives/unpublishes the project.
	 */
	private function archiveProject($project_id)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->update($db->qn('#__pf_projects'))
			->set($db->qn('archived') . ' = ' . $db->q('1'))
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
			->set($db->qn('archived') . ' = ' . $db->q('0'))
			->where($db->qn('id') .' = '. $db->q($project_id))
			->where($db->qn('archived') . ' != ' . $db->q('0'));
		$db->setQuery($query);
		$db->execute();
		if($db->getError()) die($db->getError());
	}
	
	/**
	 * Returns the id of the group or null if it doesn't exist.
	 */
	private function getGroupId($project_id)
	{
		$db = JFactory::getDBO();
		$group_name = JText::sprintf('PLG_AKEEBASUBS_PROJECTFORK_PFGROUP_NAME');
		$query = $db->getQuery(true)
			->select($db->qn('id'))
			->from($db->qn('#__pf_groups'))
			->where($db->qn('title') . ' = ' . $db->q($group_name))
			->where($db->qn('project') . ' = ' . $db->q($project_id));
		$db->setQuery($query);
		return $db->loadResult();
	}
	
	/**
	 * Creates a new PF group and assigns the user to it.
	 */
	private function createGroup($project_id, $user_id)
	{
		$db = JFactory::getDBO();
		
		// Create the project
		$group_name = JText::sprintf('PLG_AKEEBASUBS_PROJECTFORK_PFGROUP_NAME');
		$group_description = JText::sprintf('PLG_AKEEBASUBS_PROJECTFORK_PFGROUP_DESC');
		$query = $db->getQuery(true)
			->insert($db->qn('#__pf_groups'))
			->columns(array(
				$db->qn('title'),
				$db->qn('description'),
				$db->qn('project'),
				$db->qn('permissions')
			))
			->values($db->q($group_name) . ', '
				. $db->q($group_description) . ', '
				. $db->q($project_id) . ', '
				. $db->q('a:2:{s:8:"sections";a:1:{i:0;s:8:"projects";}s:8:"projects";a:1:{i:0;s:15:"display_details";}}')
			);
		$db->setQuery($query);
		$db->execute();
		$group_id = $db->insertid();
		
		// Add user to the group
		$query = $db->getQuery(true)
			->insert($db->qn('#__pf_group_users'))
			->columns(array(
				$db->qn('group_id'),
				$db->qn('user_id'),
			))
			->values($db->q($group_id) . ', '
					.$db->q($user_id)
			);
		$db->setQuery($query);
		$db->execute();
		
		return $group_id;
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
}