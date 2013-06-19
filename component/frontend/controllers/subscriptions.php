<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerSubscriptions extends FOFController
{
	public function __construct($config = array()) {
		parent::__construct($config);

		$this->cacheableTasks = array('read');
	}

	public function execute($task) {
		$allowedTasks = array('browse', 'read');
		if(in_array($task,array('edit','add'))) $task = 'read';
		if(!in_array($task,$allowedTasks)) return false;

		$this->input->set('task',$task);

		parent::execute($task);
	}

	public function onBeforeBrowse()
	{
		// If we have a username/password pair, log in the user if he's a guest
		$username = $this->input->getString('username','');
		$password = $this->input->getString('password','');
		$user = JFactory::getUser();

		if($user->guest && !empty($username) && !empty($password)) {
			JLoader::import( 'joomla.user.authentication');
			$credentials = array(
				'username'	=> $username,
				'password'	=> $password
			);
			$app = JFactory::getApplication();
			$options = array('remember' => false);
			$authenticate = JAuthentication::getInstance();
			$response	  = $authenticate->authenticate($credentials, $options);
			if ($response->status == JAuthentication::STATUS_SUCCESS) {
				JPluginHelper::importPlugin('user');
				$results = $app->triggerEvent('onLoginUser', array((array)$response, $options));
				JLoader::import('joomla.user.helper');
				$userid = JUserHelper::getUserId($response->username);
				$user = JFactory::getUser($userid);
				$parameters['username']	= $user->get('username');
				$parameters['id']		= $user->get('id');
			}
		}

		// If we still have a guest user, show the login page
		if($user->guest) {
			// Show login page
			$juri = JURI::getInstance();
			$myURI = base64_encode($juri->toString());
			$com = version_compare(JVERSION, '1.6.0', 'ge') ? 'users' : 'user';
			JFactory::getApplication()->redirect(JURI::base().'index.php?option=com_'.$com.'&view=login&return='.$myURI);
			return false;
		}

		// Does the user have core.manage access or belongs to SA group?
		$isAdmin = $user->authorise('core.manage','com_akeebasubs');

		if($this->input->getInt('allUsers',0) && $isAdmin) {
			$this->getThisModel()->user_id(null);
		} else {
			$this->getThisModel()->user_id(JFactory::getUser()->id);
		}

		if($this->input->getInt('allStates',0) && $isAdmin) {
			$this->getThisModel()->paystate(null);
		} else {
			$this->getThisModel()->paystate('C,P');
		}

		// Let me cheat. If the request doesn't specify how many records to show, show them all!
		if($this->input->getCmd('format','html') != 'html') {
			if(!$this->input->getInt('limit',0) && !$this->input->getInt('limitstart',0)) {
				$this->getThisModel()->limit(0);
				$this->getThisModel()->limitstart(0);
			}
		}

		return true;
	}

	public function onBeforeRead()
	{
		// Force the item layout
		$this->layout = 'default';
		$this->getThisView()->setLayout('default');

		// Do we have a user?
		if(JFactory::getUser()->guest) {
			// Show login page
			$juri = JURI::getInstance();
			$myURI = base64_encode($juri->toString());
			$com = version_compare(JVERSION, '1.6.0', 'ge') ? 'users' : 'user';
			JFactory::getApplication()->redirect(JURI::base().'index.php?option=com_'.$com.'&view=login&return='.$myURI);
			return false;
		}

		// Make sure it's the current user's subscription
		$this->getThisModel()->setIDsFromRequest();
		$this->getThisModel()->user_id(JFactory::getUser()->id);
		$this->getThisModel()->paystate('C,P');

		$list = $this->getThisModel()->getItemList();
		$found = false;
		if(!empty($list)) foreach($list as $id => $sub) {
			if($sub->akeebasubs_subscription_id == $this->getThisModel()->getId()) {
				$found = true;
				break;
			}
		}

		if(!$found) {
			JError::raiseError('403',JText::_('ACCESS DENIED'));
			return false;
		}

		return true;
	}
}
