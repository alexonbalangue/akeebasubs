<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsAgora extends JPlugin
{
	/** @var array Levels to Groups to Add mapping */
	private $addGroups = array();
	
	/** @var array Levels to Groups to Remove mapping */
	private $removeGroups = array();
	
	/** @var array Agora Groups */
	private $agoraGroups = array();
	
	public function __construct(& $subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);

		$this->loadAgoraGroups();
		
		// Load level to group mapping from plugin parameters		
		$strAddGroups = $this->params->get('addgroups','');
		$this->addGroups = $this->parseGroups($strAddGroups);
		
		$strRemoveGroups = $this->params->get('removegroups','');
		$this->removeGroups = $this->parseGroups($strRemoveGroups);
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
				$addGroups = $this->addGroups[$level];
			} else {
				// Disabled subscription, remove groups
				if(empty($this->removeGroups)) continue;
				if(!array_key_exists($level, $this->removeGroups)) continue;
				$removeGroups = $this->removeGroups[$level];
			}
		}
		
		
		// If no groups are detected, do nothing
		if(empty($addGroups) && empty($removeGroups)) return;
		
		$actionGroups = $addGroups;
		
		// Clean up the remove groups: if we are asked to both add and remove a user
		// from a group, add wins.
		if(!empty($removeGroups)) {
			foreach($removeGroups as $gid => $rid) {
				if(array_key_exists($gid, $actionGroups)) {
					$actionGroups[$gid] = max($rid, $actionGroups[$gid]);
				} else {
					$actionGroups[$gid] = $rid;
				}
			}
		}
		
		// Get DB connection
		$db = JFactory::getDBO();
		
		// Get Agora user ID
		$agora_user_id = 0;
		$query = FOFQueryAbstract::getNew($db)
			->select($db->nameQuote('id'))
			->from($db->nameQuote('#__agora_users'))
			->where($db->nameQuote('jos_id').' = '.$db->quote($user_id));
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
		
		// Add/remove/modify Agora groups
		foreach($actionGroups as $gid => $rid) {
			// Remove old records
			$query = FOFQueryAbstract::getNew($db)
				->delete($db->nameQuote('#__agora_user_group'))
				->where($db->nameQuote('user_id').' = '.$db->quote($agora_user_id))
				->where($db->nameQuote('group_id').' = '.$db->quote($gid));
			$db->setQuery($query);
			$db->query();
			if($rid !== 0) {
				$object = (object)array(
					'user_id'	=> $agora_user_id,
					'group_id'	=> $gid,
					'role_id'	=> $rid,
				);
				$db->insertObject('#__agora_user_group', $object);
			}
		}
	}
	
	/**
	 * Converts an Akeeba Subscriptions level to a numeric ID
	 * 
	 * @param $title string The level's name to be converted to an ID
	 *
	 * @return int The subscription level's ID or -1 if no match is found
	 */
	private function ASLevelToId($title)
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
	
	private function NameToId($title, $array)
	{
		static $groups = null;
		
		if(empty($title)) return -1;

		$title = strtoupper(trim($title));
		
		if(array_key_exists($title, $array)) {
			// Mapping found
			return($array[$title]);
		} elseif( (int)$title == $title ) {
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
	
	private function parseGroups($rawData)
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
		$query = FOFQueryAbstract::getNew($db)
			->select(array(
				$db->nameQuote('name'),
				$db->nameQuote('id'),
			))->from($db->nameQuote('#__agora_group'));
		$db->setQuery($query);
		$temp = $db->loadAssocList();
		$this->agoraGroups = array();
		if(!empty($temp)) foreach($temp as $rec) {
			$key = strtoupper($rec['name']);
			$this->agoraGroups[$key] = $rec['id'];
		}
	}
}