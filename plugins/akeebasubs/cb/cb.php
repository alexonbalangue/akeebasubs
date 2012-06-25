<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
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
		$sql = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__comprofiler'))
			->where($db->qn('user_id').' = '.$db->q($user_id));
		$db->setQuery($sql);
		$count = $db->loadResult();
		
		if($count) return;

		$query = $db->getQuery(true)
			->insert('#__comprofiler')
			->columns(array(
				$db->qn('id'),
				$db->qn('user_id'),
				$db->qn('firstname'),
				$db->qn('middlename'),
				$db->qn('lastname'),
				$db->qn('avatarapproved'),
				$db->qn('confirmed'),
				$db->qn('registeripaddr'),
				$db->qn('cbactivation'),
				$db->qn('banned'),
				$db->qn('acceptedterms'),
			));
		
		$values = $db->q($user_id).', '.$db->q($user_id).', ';
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
		$values .= $db->q($firstName) .', '. $db->q($middleName) .', '. $db->q($lastName) .', ';
		$values .= $db->q('1').', '.$db->q('1').', ';
		$ip = htmlspecialchars($_SERVER['REMOTE_ADDR']);
		$values .= $db->q( empty($ip) ? '127.0.0.1' : $ip ).', ';
		$values .= $db->q('').', '.$db->q('0').', '.$db->q('1');
		$query->values($values);
		$db->setQuery($query);
		$db->query();
	}

	private function setCBAuth($user_id, $auth)
	{
		$auth = $auth ? 1 : 0;
		$user_id  = (int)$user_id;
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->update($db->qn('#__comprofiler'))
			->set($db->qn('approved').' = '.$db->q($auth))
			->set($db->qn('confirmed').' = '.$db->q($auth))
			->set($db->qn('acceptedterms').' = '.$db->q($auth))
			->where($db->qn('user_id').' = '.$db->q($user_id));
		$db->setQuery($query);
		$db->query();
		if($db->getError()) die($db->getError());
	}
}