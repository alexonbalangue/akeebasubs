<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsAgora extends plgAkeebasubsAbstract
{
	/** @var array Agora Groups */
	private $agoraGroups = array();

	private $agoraDbPrefix = "";
	private $ignoreAdmins = true;
	
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'agora';
		$this->loadAgoraGroups();
		
		parent::__construct($subject, $name, $config, $templatePath);
		
		$configParams = @json_decode($config['params']);
		$version = $configParams->agoraversion;
		$this->agoraDbPrefix = ($version == '3') ? '#__agora' : '#__agorapro';
		$this->ignoreAdmins = $configParams->ignoreadmins;
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
					$this->addGroups[$level->akeebasubs_level_id] = array();
					foreach($level->params->$addgroupsKey as $compositeString) {
						if(! empty($compositeString)) {
							$this->addGroups[$level->akeebasubs_level_id][] = $this->parseCompositeString($compositeString);	
						}
					}
				}
				if(property_exists($level->params, $removegroupsKey))
				{
					$this->removeGroups[$level->akeebasubs_level_id] = array();
					foreach($level->params->$removegroupsKey as $compositeString) {
						if(! empty($compositeString)) {
							$this->removeGroups[$level->akeebasubs_level_id][] = $this->parseCompositeString($compositeString);
						}
					}
				}
			}
		}
	}
	
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;
		if(array_key_exists('enabled', (array)$info['modified'])) {
			$this->onAKUserRefresh($row->user_id);
		}
	}
	
	protected function loadUserGroups($user_id, &$addGroups = array(), &$removeGroups = array())
	{
		// Make sure we're configured
		if(empty($this->addGroups) && empty($this->removeGroups)) return;

		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();

		// Make sure there are subscriptions set for the user
		if(!count($subscriptions)) return;

		// Get the initial list of groups to add/remove from
		$addGroups = array();
		$removeGroups = array();

		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				// Enabled subscription, add groups
				if(empty($this->addGroups)) continue;
				if(!array_key_exists($level, $this->addGroups)) continue;
				$addGroups[$level] = $this->addGroups[$level];
			} else {
				// Disabled subscription, remove groups
				if(empty($this->removeGroups)) continue;
				if(!array_key_exists($level, $this->removeGroups)) continue;
				$removeGroups[$level] = $this->removeGroups[$level];
			}
		}

		// If no groups are detected, do nothing
		if(empty($addGroups) && empty($removeGroups)) return;

		// Clean up the remove groups: if we are asked to both add and remove a user
		// from a group, add wins.
		if(!empty($removeGroups)) {
			foreach($removeGroups as $level => $assignment) {
				foreach ($assignment as $gid => $rid) {
					if(array_key_exists($gid, $addGroups[$level])) {
						$addGroups[$level][$gid] = max($rid, $addGroups[$level][$gid]);
					} else {
						$addGroups[$level][$gid] = $rid;
					}
				}
			}
		}
	}

	public function onAKUserRefresh($user_id)
	{
		// Load groups
		$actionGroups = array();
		$this->loadUserGroups($user_id, $actionGroups);

		// Get DB connection
		$db = JFactory::getDBO();

		// Get Agora user ID
		$agora_user_id = 0;
		if ($this->agoraVersion == '3') {
			$query = $db->getQuery(true)
				->select($db->qn('id'))
				->from($db->qn('#__agora_users'))
				->where($db->qn('jos_id').' = '.$db->q($user_id));
			$db->setQuery($query);
			$agora_user_id = $db->loadResult();
			if(empty($agora_user_id )) {
				// Gotta create Agora user record
				$user = JFactory::getUser($user_id);
				$user_object = (object)array(
					'jos_id'			=> $user_id,
					'group_id'			=> 4,
					'username'			=> $user->username,
					'imgaward'			=> '',
					'email'				=> $user->email,
					'use_avatar'		=> 0,
					'notify_with_post'	=> 0,
					'show_smilies'		=> 1,
					'show_img'			=> 1,
					'show_img_sig'		=> 1,
					'show_avatars'		=> 1,
					'show_sig'			=> 1,
					'style'				=> 'Olympus',
					'num_posts'			=> 0,
					'registered'		=> time(),
					'last_visit'		=> 0,
					'reverse_posts'		=> 0,
					'reputation_enable'	=> 0,
					'rep_minus'			=> 0,
					'rep_plus'			=> 0,
					'gender'			=> 0,
					'birthday'			=> 0,
					'hide_age'			=> 0,
					'ignore_mode'		=> 1,
					'auto_subscriptions'=> 1,
					'last_known_ip'		=> '0.0.0.0',
				);
				$db->insertObject('#__agora_users', $user_object);
				$agora_user_id = $db->insertid();
			}
		} else {
			$query = $db->getQuery(true)
				->select($db->qn('id'))
				->from($db->qn('#__agorapro_users'))
				->where($db->qn('id').' = '.$db->q($user_id));
			$db->setQuery($query);
			$agora_user_id = $db->loadResult();
			if(empty($agora_user_id )) {
				$def_ims = '{"jabber":"","icq":"","msn":"","aim":"","yahoo":"","skype":"","xfire":""}';
				$def_addon_params = '"{"avatar":{"n":"Avatar Addon","params":{"type":"1","gravatar_rating":"g","show_in_posts":"1"}},"pms":{"n":"Personal Message System Addon","params":{"msgs_per_page":"25","receive_from":"1","notifications":"1"}},"attachments":{"n":"Attachments Addon","params":{"type":"1"}},"subscriptions":{"n":"Subscriptions Addon","params":{"auto_subscriptions":"1"}},"notifications":{"n":"Notifications Addon","params":{"send_type":"1"}}}"';

				$user_object = (object)array(
						'id'			=> $user_id,
						'ims'			=> 4,
						'addon_params'			=> $user->username
					);
				$db->insertObject('#__agorapro_users', $user_object);
				$agora_user_id = $db->insertid();
			}
		}

		// Add/remove/modify Agora groups
		foreach($actionGroups as $level => $assignment) {
			foreach ($assignment as $gid => $rid) {
				// Remove old records
				$query = $db->getQuery(true)
					->delete($db->qn($this->agoraDbPrefix . '_user_group'))
					->where($db->qn('user_id').' = '.$db->q($agora_user_id))
					->where($db->qn('group_id').' = '.$db->q($gid));

				//should admins be spared from being removed?
				if ($this->ignoreAdmins) {
					$query->where($db->qn('role_id').' != '.$db->q('4'));
				}

				$db->setQuery($query);
				$db->execute();

				//if admins should be ignored, check to see if the user is already an admin for this group to prevent duplicate entries
				if ($this->ignoreAdmins && $rid == '4') {
					$query = $db->getQuery(true)
						->select($db->qn('id'))
						->from($db->qn($this->agoraDbPrefix."_user_group"))
						->where($db->qn('role_id').' = '.$db->q($rid))
						->where($db->qn('group_id').' = '.$db->q($gid))
						->where($db->qn('user_id').' = '.$db->q($agora_user_id));
					$db->setQuery($query);
					$user_already_admin = $db->loadResult();
					if (!empty($user_already_admin)) {
						continue;
					}
				}

				if($rid !== 0) {
					$object = (object)array(
						'user_id'	=> $agora_user_id,
						'group_id'	=> $gid,
						'role_id'	=> $rid,
					);
					$db->insertObject($this->agoraDbPrefix . '_user_group', $object);
				}
			}
		}
	}

	private function NameToId($title, $array)
	{
		static $groups = null;

		if(empty($title)) return -1;

		$title = strtoupper(trim($title));

		foreach($array as $key => $val) {
			// Mapping found
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

	private function parseCompositeString($string)
	{
		$ret = (object)array(
			'group'		=> 1,
			'role'		=> 2,
		);

		$string = strtoupper(trim($string));

		if(empty($string)) return $ret;

		$parts = explode('/', $string);

		if(isset($parts[0])) {
			$gid = $this->NameToId($parts[0], $this->agoraGroups);
			if($gid === -1) $gid = 1;
			$ret->group = $gid;
		}

		if(isset($parts[1])) {
			$roleName = strtoupper($parts[1]);
			$roleName = trim($roleName);
			switch($roleName) {
				case 'NONE':
					$ret->role = 0;
					break;

				case 'GUEST':
					$ret->role = 1;
					break;

				case 'MEMBER':
				default:
					$ret->role = 2;
					break;

				case 'MODERATOR':
					$ret->role = 3;
					break;

				case 'ADMIN':
					$ret->role = 4;
					break;
			}
		} else {
			$ret->role = 2; // Default role: Member
		}

		return $ret;
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

			$groups = explode(',', $rawGroups);
			if(empty($groups)) continue;
			if(!is_array($groups)) $groups = array($groups);

			$levelId = $this->ASLevelToId($level);
			$groupIds = array();
			foreach($groups as $groupTitle) {
				$groupDescriptor = $this->parseCompositeString($groupTitle);
				$groupID = $groupDescriptor->group;
				$roleID = $groupDescriptor->role;

				if(array_key_exists($groupID, $groupIds)) {
					$oldRole = $groupIds[$groupID];
					$roleID = max($oldRole, $roleID);
				}

				$groupIds[$groupID] = $roleID;
			}

			$ret[$levelId] = $groupIds;
		}

		return $ret;
	}

	private function loadAgoraGroups()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select(array(
				$db->qn('name'),
				$db->qn('id'),
			))->from($db->qn($this->agoraDbPrefix . '_group'));
		$db->setQuery($query);
		$temp = $db->loadAssocList();
		$this->agoraGroups = array();
		if(!empty($temp)) foreach($temp as $rec) {
			$key = $rec['name'];
			$this->agoraGroups[$key] = $rec['id'];
		}
	}

	protected function getGroups() {
		return $this->agoraGroups;
	}
	
	protected function getSelectField($level, $type)
	{
		if(! in_array($type, array('add', 'remove'))) return '';
		$roles = array('None', 'Guest', 'Member', 'Moderator', 'Admin');
		$groups = $this->getGroups();
		$selected = array();
		$options = array();
		$assignments = ($type == 'add') ? $this->addGroups[$level->akeebasubs_level_id] : $this->removeGroups[$level->akeebasubs_level_id];
		$options[] = JHTML::_('select.option','',JText::_('PLG_AKEEBASUBS_' . strtoupper($this->name) . '_NONE'));
		foreach($groups as $groupTitle => $groupId) {
			foreach($roles as $role) {
				$title = $groupTitle . ' [' . $role . ']';
				$id = $groupTitle . '/' . $role;
				$assignment = $this->parseCompositeString($id);
				if(in_array($assignment, $assignments)) {
					$selected[] = $id;
				}
				$options[] = JHTML::_('select.option',$id,$title);
			}
		}
		// Create the select field
		return JHtmlSelect::genericlist($options, 'params[' . strtolower($this->name) . '_' . $type . 'groups][]', 'multiple="multiple" size="8" class="input-large"', 'value', 'text', $selected);
	}
}