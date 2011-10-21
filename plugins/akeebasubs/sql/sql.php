<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsSql extends JPlugin
{
	/** @var array Levels to SQL mapping, for subscription activation */
	private $addGroups = array();
	
	/** @var array Levels to SQL mapping, for subscription deactivation */
	private $removeGroups = array();

	public function __construct(& $subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);

		// Load level to group mapping from plugin parameters		
		$strAddGroups = $this->params->get('addgroups','');
		$this->addGroups = $this->parseSQL($strAddGroups);
		
		$strRemoveGroups = $this->params->get('removegroups','');
		$this->removeGroups = $this->parseSQL($strRemoveGroups);
	}
	
	/**
	 * Called when a new subscription is created, either manually or through
	 * the front-end interface
	 */
	public function onAKSubscriptionCreate($row)
	{
		$this->onAKUserRefresh($row->user_id);
	}
	
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row)
	{
		$this->onAKUserRefresh($row->user_id);
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
		
		// If no sql commands are detected, do nothing
		if(empty($addGroups) && empty($removeGroups)) return;
		
		// Get DB connection
		$db = JFactory::getDBO();
		
		// Deactivation SQL
		if(!empty($removeGroups)) {
			foreach($removeGroups as $sql) {
				$sql = str_replace('[USERID]', $user_id, $sql);
				$db->setQuery($sql);
				$db->queryBatch(false);
			}
		}

		// Activation SQL
		if(!empty($addGroups)) {
			foreach($addGroups as $sql) {
				$sql = str_replace('[USERID]', $user_id, $sql);
				$db->setQuery($sql);
				$db->queryBatch(false);
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
	
	private function parseSQL($rawData)
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
			$rawSQL = $parts[1];
			
			$levelId = $this->ASLevelToId($level);
			$ret[$levelId] = $rawSQL;
		}
		
		return $ret;
	}
}