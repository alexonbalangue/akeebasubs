<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsMailchimp extends plgAkeebasubsAbstract
{	
	private $mcApi;
	private $delete_member = false;
	private $send_goodbye = true;
	private $send_notify = true;
	private $email_type = 'html';
	private $double_optin = true;
	private $send_welcome = false;
	
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'mailchimp';
		
		parent::__construct($subject, $name, $config, $templatePath);
		
		// Load the MailChimp library
		require_once dirname(__FILE__).'/library/MCAPI.class.php';
		
		$configParams = @json_decode($config['params']);
		$apiKey = $configParams->mailchimp_key;
		$this->mcApi = new MCAPI($apiKey);
		$this->delete_member = $configParams->delete_member;
		$this->send_goodbye = $configParams->send_goodbye;
		$this->send_notify = $configParams->send_notify;
		$this->email_type = $configParams->email_type;
		$this->double_optin = $configParams->double_optin;
		$this->send_welcome = $configParams->send_welcome;
		
		// Do we have values from the Olden Days?
		$strAddGroups = $configParams->addlists;
		$strRemoveGroups = $configParams->removelists;

		if(!empty($strAddGroups) || !empty($strAddGroups)) {
			// Load level to group mapping from plugin parameters		
			$this->addGroups = $this->parseGroups($strAddGroups);
			$this->removeGroups = $this->parseGroups($strRemoveGroups);
			// Do a transparent upgrade
			$this->upgradeSettings($config);
		}
	}
	
	public function onAKUserRefresh($user_id)
	{
		// Load groups
		$addLists = array();
		$removeLists = array();
		$this->loadUserGroups($user_id, $addLists, $removeLists);
		if(empty($addLists) && empty($removeLists)) return;
		
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
			foreach($removeLists as $mcListToRemove) {
				if(is_array($currentLists) && in_array($mcListToRemove, $currentLists)) {
					$mcSubscribeId = $user_id . ':' . $mcListToRemove;
					$this->mcApi->listUnsubscribe(
							$mcListToRemove,
							$email,
							$this->delete_member,
							$this->send_goodbye,
							$this->send_notify);
				}
				$mcSubscribeId = $user_id . ':' . $mcListToRemove;
				$session->clear('mailchimp.' . $mcSubscribeId, 'com_akeebasubs');
			}
		}
		
		// Add to MailChimp list
		if(!empty($addLists)) {
			foreach($addLists as $mcListToAdd) {
				if(! (is_array($currentLists) && in_array($mcListToAdd, $currentLists))) {
					$mcSubscribeId = $user_id . ':' . $mcListToAdd;
					if($session->get('mailchimp.' . $mcSubscribeId, '', 'com_akeebasubs') != 'new') {
						// Subscribe if email is not already in the MailChimp list and
						// if the subscription is not already sent for that user (but not confirmed yet)
						if($this->mcApi->listSubscribe(
								$mcListToAdd,
								$email,
								array('FNAME' => $firstName, 'LNAME' => $lastName),
								$this->email_type,
								$this->double_optin,
								true,
								false,
								$this->send_welcome)) {
							// Add new MailChimp subscription to session to avoid that MailChimp sends multiple
							// emails for one subscription (before subscription is confirmed by the user)
							$session->set('mailchimp.' . $mcSubscribeId , 'new', 'com_akeebasubs');
						}	
					}	
				}
			}
		}
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
		$addgroupsKey = strtolower($this->name).'_addgroups';
		$removegroupsKey = strtolower($this->name).'_removegroups';
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				$save = false;
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					$level->params = new stdClass();
				}
				if(array_key_exists($level->akeebasubs_level_id, $this->addGroups)) {
					if(empty($level->params->$addgroupsKey)) {
						$level->params->$addgroupsKey = $this->addGroups[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				if(array_key_exists($level->akeebasubs_level_id, $this->removeGroups)) {
					if(empty($level->params->$removegroupsKey)) {
						$level->params->$removegroupsKey = $this->removeGroups[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				if($save) {
					$level->params = json_encode($level->params);
					$result = $model->setId($level->akeebasubs_level_id)->save( $level );
				}
			}
		}
		
		// Remove the plugin parameters
		if(isset($config['params'])) {
			$configParams = @json_decode($config['params']);
			unset($configParams->addlists);
			unset($configParams->removelists);
			$param_string = @json_encode($configParams);
			
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->update($db->qn('#__extensions'))
				->where($db->qn('type').'='.$db->q('plugin'))
				->where($db->qn('element').'='.$db->q(strtolower($this->name)))
				->where($db->qn('folder').'='.$db->q('akeebasubs'))
				->set($db->qn('params').' = '.$db->q($param_string));
			$db->setQuery($query);
			$db->execute();
		}
	}

	protected function getGroups() {
		static $groups = null;
		
		if(is_null($groups)) {
			$groups = array();
			
			$start = 0;
			$limit = 100;
			$mcLists = $this->mcApi->lists(array(), $start, $limit);
			$total = $mcLists['total'];
			while(true) {
				foreach($mcLists['data'] as $list) {
					$listTitle = $list['name'];
					$listId = $list['id'];
					$groups[$listTitle] = $listId;
				}
				if(($start + $limit) < $total) {
					$start += $limit;
					$mcLists = $this->mcApi->lists(array(), $start, $limit);
				} else {
					break;
				}
			}
		}
		
		return $groups;
	}
}