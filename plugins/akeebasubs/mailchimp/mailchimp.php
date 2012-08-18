<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsMailchimp extends JPlugin
{
	/** @var array Levels to Lists to Add mapping */
	private $addLists = array();
	
	/** @var array Levels to Lists to Remove mapping */
	private $removeLists = array();
	
	private $mcApi;

	public function __construct(& $subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);
		
		// Load the MailChimp library
		require_once dirname(__FILE__).'/library/MCAPI.class.php';
		$apiKey = $this->params->get('mailchimp_key','');
		$this->mcApi = new MCAPI($apiKey);

		// Load level to group mapping from plugin parameters		
		$strAddLists = $this->params->get('addlists','');
		$this->addLists = $this->parseLists($strAddLists);
		
		$strRemoveLists = $this->params->get('removelists','');
		$this->removeLists = $this->parseLists($strRemoveLists);
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
		if(empty($this->addLists) && empty($this->removeLists)) return;
	
		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();
			
		// Make sure there are subscriptions set for the user
		if(!count($subscriptions)) return;
		
		// Get the initial lists to add/remove from
		$addLists = array();
		$removeLists = array();
		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				// Enabled subscription, add lists
				if(empty($this->addLists)) continue;
				if(!array_key_exists($level, $this->addLists)) continue;
				$lists = $this->addLists[$level];
				foreach($lists as $list) {
					if(!in_array($list, $addLists) && !empty($list)) {
						$addLists[] = $list;
					}
				}
			} else {
				// Disabled subscription, remove lists
				if(empty($this->removeLists)) continue;
				if(!array_key_exists($level, $this->removeLists)) continue;
				$lists = $this->removeLists[$level];
				
				foreach($lists as $list) {
					if(!in_array($list, $removeLists) && !empty($list)) {
						$removeLists[] = $list;
					}
				}
			}
		}
		
		// If no groups are detected, do nothing
		if(empty($addLists) && empty($removeLists)) return;
		
		// Sort the lists
		asort($addLists);
		asort($removeLists);
		
		// Clean up the remove groups: if we are asked to both add and remove a user
		// from a group, add wins.
		if(!empty($removeLists) && !empty($addLists)) {
			$temp = $removeLists;
			$removeLists = array();
			foreach($temp as $list) {
				if(!in_array($list, $addLists)) {
					$removeLists[] = $list;
				}
			}
		}
		
		// Get the user's name and email
		$user = JUser::getInstance($user_id);
		$nameParts = explode(' ', trim($user->name), 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		$email = $user->email;
		
		// Get the user's MailChimp lists
		$currentLists = $this->mcApi->listsForEmail($email);
		
		// Get the session
		$session = JFactory::getSession();
		
		// Remove from MailChimp list
		if(!empty($removeLists)) {
			foreach($removeLists as $listToRemove) {
				if(is_array($currentLists) && in_array($listToRemove, $currentLists)) {
					$mcSubscribeId = $user_id . ':' . $listToRemove;
					$this->mcApi->listUnsubscribe(
							$listToRemove,
							$email,
							$this->params->get('delete_member', false),
							$this->params->get('send_goodbye', true),
							$this->params->get('send_notify', true));
				}
				$mcSubscribeId = $user_id . ':' . $listToRemove;
				$session->clear('mailchimp.' . $mcSubscribeId, 'com_akeebasubs');
			}
		}
		
		// Add to MailChimp list
		if(!empty($addLists)) {
			foreach($addLists as $listToAdd) {
				if(! (is_array($currentLists) && in_array($listToAdd, $currentLists))) {
					$mcSubscribeId = $user_id . ':' . $listToAdd;
					if($session->get('mailchimp.' . $mcSubscribeId, '', 'com_akeebasubs') != 'new') {
						// Subscribe if email is not already in the MailChimp list and
						// if the subscription is not already sent for that user (but not confirmed yet)
						if($this->mcApi->listSubscribe(
								$listToAdd,
								$email,
								array('FNAME' => $firstName, 'LNAME' => $lastName),
								$this->params->get('email_type', 'html'),
								$this->params->get('double_optin', true),
								true,
								false,
								$this->params->get('send_welcome', false))) {
							// Add new MailChimp subscription to session to void that MailChimp sends multiple
							// emails for one subscription (before subscription is confirmed by the user)
							$session->set('mailchimp.' . $mcSubscribeId , 'new', 'com_akeebasubs');
						}	
					}	
				}
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
	
	private function MailChimpListToId($name)
	{
		// Get the list
		$mcLists = $this->mcApi->lists(array(
			'list_name' => $name
			), 0, 1
		);
		
		if($mcLists['total'] > 0) {
			// Get the id
			$id = $mcLists['data'][0]['id'];
			return $id;
		}
		
		// No match!
		return -1;
	}

	private function parseLists($rawData)
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
			$rawLists = $parts[1];
			
			$lists = explode(',', $rawLists);
			if(empty($lists)) continue;
			if(!is_array($lists)) $lists = array($lists);
			
			$levelId = $this->ASLevelToId($level);
			$listIds = array();
			foreach($lists as $listName) {
				$listIds[] = $this->MailChimpListToId($listName);
			}
			
			$ret[$levelId] = $listIds;
		}
		
		return $ret;
	}
}