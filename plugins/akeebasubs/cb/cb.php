<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsCb extends plgAkeebasubsAbstract
{
	/** @var array Subscription levels which guarantee automatic CB user authorization */
	private $authLevels = array();
	
	/** @var array Subscription levels which guarantee automatic CB user de-authorization */
	private $deauthLevels = array();
	
	/** @var bool Should I auto-add the user to CB users list if he's not already there? */
	private $autoAddUser = true;
	
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'cb';
		
		parent::__construct($subject, $name, $config, $templatePath, false);

		$configParams = @json_decode($config['params']);
		$this->autoAddUser = $configParams->adduser;
		
		// Do we have values from the Olden Days?
		$authLevels = $configParams->autoauthids;
		$deauthLevels = $configParams->autodeauthids;

		if(!empty($authLevels) || !empty($deauthLevels)) {
			// Load level to group mapping from plugin parameters		
			$this->authLevels = $authLevels;
			$this->deauthLevels = $deauthLevels;
			// Do a transparent upgrade
			$this->upgradeSettings($config);
		} else {
			$this->loadGroupAssignments();
		}
	}
	
	protected function loadGroupAssignments()
	{
		$this->authLevels = array();
		$this->deauthLevels = array();
		
		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$autoauthdeidsKey = 'cb_autoauthids';
		$autodeauthidsKey = 'cb_autodeauthids';
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
				if(property_exists($level->params, $autoauthdeidsKey) && $level->params->$autoauthdeidsKey)
				{
					$this->authLevels[] = $level->akeebasubs_level_id;
				}
				if(property_exists($level->params, $autodeauthidsKey) && $level->params->$autodeauthidsKey)
				{
					$this->deauthLevels[] = $level->akeebasubs_level_id;
				}
			}
		}
	}
	
	protected function parseGroups($rawData)
	{
		// Do nothing
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
	protected function upgradeSettings($config = array())
	{
		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					$level->params = new stdClass();
				}
				$lid = (string)$level->akeebasubs_level_id;
				$level->params->cb_autoauthids = 0;
				if(array_key_exists($lid, $this->authLevels)) {
					if(empty($level->params->cb_autoauthids)) {
						$level->params->cb_autoauthids = 1;
						$save = true;
					}
				}
				$level->params->cb_autodeauthids = 0;
				if(array_key_exists($lid, $this->deauthLevels)) {
					if(empty($level->params->cb_autodeauthids)) {
						$level->params->cb_autodeauthids = 1;
						$save = true;
					}
				}
				$level->params = json_encode($level->params);
				$result = $model->setId($level->akeebasubs_level_id)->save( $level );
			}
		}
		
		// Remove the plugin parameters
		if(isset($config['params'])) {
			$configParams = @json_decode($config['params']);
			unset($configParams->autoauthids);
			unset($configParams->autodeauthids);
			$param_string = @json_encode($configParams);

			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->update($db->qn('#__extensions'))
				->where($db->qn('type').'='.$db->q('plugin'))
				->where($db->qn('element').'='.$db->q('cb'))
				->where($db->qn('folder').'='.$db->q('akeebasubs'))
				->set($db->qn('params').' = '.$db->q($param_string));
			$db->setQuery($query);
			$db->execute();
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
		$db->execute();
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
		$db->execute();
		if($db->getError()) die($db->getError());
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
			$selected = in_array($level->akeebasubs_level_id, $this->authLevels);
			return JHTML::_('select.radiolist',  $opts, 'params[cb_autoauthids]', '', 'value', 'text', (int)$selected, 'paramscb_autoauthids');
		}
		if($type == 'remove') {
			$selected = in_array($level->akeebasubs_level_id, $this->deauthLevels);
			return JHTML::_('select.radiolist',  $opts, 'params[cb_autodeauthids]', '', 'value', 'text', (int)$selected, 'paramscb_autodeauthids');
		}
		return '';
	}
}