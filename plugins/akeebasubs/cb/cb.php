<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsCb extends JPlugin
{
	/** @var array Subscription levels which guarantee automatic CB user authorization */
	private $authLevels = array();
	
	/** @var array Subscription levels which guarantee automatic CB user de-authorization */
	private $deauthLevels = array();
	
	/** @var bool Should I auto-add the user to CB users list if he's not already there? */
	private $autoAddUser = true;
	
	public function __construct(& $subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);

		$this->authLevels = $this->params->get('autoauthids',array());
		$this->deauthLevels = $this->params->get('autodeauthids',array());
		$this->autoAddUser = $this->params->get('adduser', 1);
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
		// Auto add the user if requested
		if($this->autoAddUser) $this->addCBUser($user_id);
		
		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();
	
		// Do I have to activate the user?
		$mustActivate = false;
		$mustDeactivate = false;
		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				if(in_array($level, $this->authLevels)) {
					$mustActivate = true;
				}
			} else {
				if(in_array($level, $this->deauthLevels)) {
					$mustDeactivate = true;
				}
			}
		}

		if($mustActivate && $mustDeactivate) {
			$mustDeactivate = false;
		}
		
		if($mustActivate) {
			$this->setCBAuth($user_id, 1);
		} elseif($mustDeactivate) {
			$this->setCBAuth($user_id, 0);
		}
		
	}
	
	private function addCBUser($user_id)
	{
		// Make sure there is no user already added
		$db = JFactory::getDBO();
		$sql = 'SELECT count(*) FROM `#__comprofiler` WHERE `user_id` = '.$db->Quote($user_id);
		$db->setQuery($sql);
		$count = $db->loadResult();
		
		if($count) return;
		
		$sql = 'INSERT INTO `#__comprofiler` (`id`, `user_id`, `firstname`, `middlename`, `lastname`, `avatarapproved`, `confirmed`, `registeripaddr`, `cbactivation`, `banned`, `acceptedterms`) VALUES(';
		$sql .= $db->Quote($user_id).', '.$db->Quote($user_id).', ';
		$user = JFactory::getUser($user_id);
		$nameParts = explode(' ', $user->name, 3);
		switch(count($nameParts)) {
			case 0:
				$firstName = '';
				$lastName = '';
				$middleName = '';
				break;
			case 1:
				$firstName = $nameParts[0];
				$lastName = '';
				$middleName = '';
				break;
			case 2:
				$firstName = $nameParts[0];
				$lastName = $nameParts[1];
				$middleName = '';
				break;
			case 3:
				$firstName = $nameParts[0];
				$lastName = $nameParts[2];
				$middleName = $nameParts[1];
				break;
		}
		$sql .= $db->Quote($firstName) .', '. $db->Quote($middleName) .', '. $db->Quote($lastName) .', ';
		$sql .= '1, 1, ';
		$ip = htmlspecialchars($_SERVER['REMOTE_ADDR']);
		$sql .= $db->Quote( empty($ip) ? '127.0.0.1' : $ip ).', ';
		$sql .= '\'\', 0, 1)';
		$db->setQuery($sql);
		$db->query();
	}

	private function setCBAuth($user_id, $auth)
	{
		$auth = $auth ? 1 : 0;
		$user_id  = (int)$user_id;
		$db = JFactory::getDBO();
		$sql = "UPDATE `#__comprofiler` SET `approved` = $auth, `confirmed` = $auth, `acceptedterms` = $auth WHERE `user_id` = $user_id";
		$db->setQuery($sql);
		$db->query();
		if($db->getError()) die($db->getError());
	}
}