<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsFrontenduseraccess extends JPlugin{

	/** @var array Levels to Groups to Add mapping */
	private $addGroups = array();
	
	/** @var array Levels to Groups to Remove mapping */
	private $removeGroups = array();

	public function __construct(& $subject, $config = array())
	{

		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);		
		
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
	
		$db = JFactory::getDBO();

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
				$groups = $this->addGroups[$level];
				foreach($groups as $group) {
					if(!in_array($group, $addGroups) && ($group > 0)) {
						$addGroups[] = $group;
					}
				}
			} else {
				// Disabled subscription, remove groups
				if(empty($this->removeGroups)) continue;
				if(!array_key_exists($level, $this->removeGroups)) continue;
				$groups = $this->removeGroups[$level];				
				foreach($groups as $group) {
					if(!in_array($group, $removeGroups) && ($group > 0)) {
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
		
		//get the user's current groups		
		$query = $db->getQuery(true);
		$query->select('user_id, group_id');
		$query->from('#__fua_userindex');
		$query->where('user_id='.$user_id);			
		$rows = $db->setQuery($query);				
		$rows = $db->loadObjectList();		
		$group_ids = '';			
		foreach($rows as $row){				
			$group_ids = $row->group_id;
		}	
		
		//make into array
		if($group_ids==''){
			$groups_array = array();				
		}else{
			$groups_array = array_unique($this->csv_to_array($group_ids));
		}
		
		// Add groups
		if(!empty($addGroups)) {				
			$groups_array = array_merge($groups_array, $addGroups);				
		}

		// Remove groups
		if(!empty($removeGroups)) {				
			$temp_array = array();
			foreach($groups_array as $group){
				if(!in_array($group, $removeGroups)){
					$temp_array[] = $group;
				}
			}
			$groups_array = $temp_array;
		}

		$groups_array = array_unique($groups_array);
		sort($groups_array);
		$fua_group = $this->array_to_csv($groups_array);
		
		//process update / insert / delete
		$query = $db->getQuery(true);
		if($group_ids!=''){	
			if($fua_group=='""'){					
				$query->delete();
				$query->from('#__fua_userindex');
				$query->where('user_id='.$user_id);
			}else{					
				$query->update('#__fua_userindex');
				$query->set('group_id='.$db->q($fua_group));			
				$query->where('user_id='.$user_id);
			}
		}else{					
			$query->insert('#__fua_userindex');
			$query->set('user_id='.$db->q($user_id));
			$query->set('group_id='.$db->q($fua_group));	
		}
		$db->setQuery($query);
		$db->query();
		
	}

	private function csv_to_array($json){		
		$array = array();
		$temp = explode(',', $json);
		for($n = 0; $n < count($temp); $n++){
			$value = str_replace('"','',$temp[$n]);
			$array[] = $value;
		}
		return $array;
	}

	private function array_to_csv($array){	
		$return = '';	
		for($n = 0; $n < count($array); $n++){
			if($n){
				$return .= ',';
			}
			$row = each($array);
			$value = $row['value'];
			if(is_string($value)){
				$value = addslashes($value);
			}	
			$return .= '"'.$value.'"';		
		}		
		return $return;
	}	

}