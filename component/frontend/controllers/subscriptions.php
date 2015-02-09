<?php

/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */
defined('_JEXEC') or die();

class AkeebasubsControllerSubscriptions extends F0FController
{

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->cacheableTasks = array('read');

		$this->cacheableTasks = array();
	}

	public function execute($task)
	{
		$allowedTasks = array('browse', 'read', 'save');

		if(in_array($task, array('edit','add')))
		{
			$task = 'read';
		}

		if(!in_array($task, $allowedTasks))
		{
			return false;
		}

		$this->input->set('task', $task);

		return parent::execute($task);
	}

	public function onBeforeBrowse()
	{
		// If we have a username/password pair, log in the user if he's a guest
		$username	 = $this->input->getString('username', '');
		$password	 = $this->input->getString('password', '');
		$user		 = JFactory::getUser();

		if ($user->guest && !empty($username) && !empty($password))
		{
			JLoader::import('joomla.user.authentication');
			$credentials	 = array(
				'username'	 => $username,
				'password'	 => $password
			);
			$app			 = JFactory::getApplication();
			$options		 = array('remember' => false);
			$authenticate	 = JAuthentication::getInstance();
			$response		 = $authenticate->authenticate($credentials, $options);
			if ($response->status == JAuthentication::STATUS_SUCCESS)
			{
				JPluginHelper::importPlugin('user');
				$results				 = $app->triggerEvent('onLoginUser', array((array) $response, $options));
				JLoader::import('joomla.user.helper');
				$userid					 = JUserHelper::getUserId($response->username);
				$user					 = JFactory::getUser($userid);
				$parameters['username']	 = $user->get('username');
				$parameters['id']		 = $user->get('id');
			}
		}

		// If we still have a guest user, show the login page
		if ($user->guest)
		{
			// Show login page
			$juri	 = JURI::getInstance();
			$myURI	 = base64_encode($juri->toString());
			JFactory::getApplication()->redirect(JURI::base() . 'index.php?option=com_users&view=login&return=' . $myURI);
			return false;
		}

		// Does the user have core.manage access or belongs to SA group?
		$isAdmin = $user->authorise('core.manage', 'com_akeebasubs');

		if ($this->input->getInt('allUsers', 0) && $isAdmin)
		{
			$this->getThisModel()->user_id(null);
		}
		else
		{
			$this->getThisModel()->user_id(JFactory::getUser()->id);
		}

		if ($this->input->getInt('allStates', 0) && $isAdmin)
		{
			$this->getThisModel()->paystate(null);
		}
		else
		{
			$this->getThisModel()->paystate('C,P');
		}

		// Let me cheat. If the request doesn't specify how many records to show, show them all!
		if ($this->input->getCmd('format', 'html') != 'html')
		{
			if (!$this->input->getInt('limit', 0) && !$this->input->getInt('limitstart', 0))
			{
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
		if (JFactory::getUser()->guest)
		{
			// Show login page
			$juri	 = JURI::getInstance();
			$myURI	 = base64_encode($juri->toString());
			JFactory::getApplication()->redirect(JURI::base() . 'index.php?option=com_users&view=login&return=' . $myURI);
			return false;
		}

		// Make sure it's the current user's subscription
		$this->getThisModel()->setIDsFromRequest();
		$this->getThisModel()->user_id(JFactory::getUser()->id);
		$this->getThisModel()->paystate('C,P');

		// Working around Progressive Caching
		JFactory::getApplication()->input->set('_x_userid', JFactory::getUser()->id);
		$this->registerUrlParams(array(
			'_x_userid'	 => 'INT',
			'id'		 => 'INT',
			'cid'		 => 'ARRAY',
		));

		$list	 = $this->getThisModel()->getItemList();
		$found	 = false;

		if (!empty($list))
		{
			foreach ($list as $id => $sub)
			{
				if ($sub->akeebasubs_subscription_id == $this->getThisModel()->getId())
				{
					$found = true;
					break;
				}
			}
		}

		if (!$found)
		{
			JError::raiseError('403', JText::_('ACCESS DENIED'));
			return false;
		}

		return true;
	}

	protected function registerUrlParams($urlparams = array())
	{
		$app = JFactory::getApplication();

		$registeredurlparams = null;

		if (property_exists($app, 'registeredurlparams'))
		{
			$registeredurlparams = $app->registeredurlparams;
		}

		if (empty($registeredurlparams))
		{
			$registeredurlparams = new stdClass;
		}

		foreach ($urlparams AS $key => $value)
		{
			// Add your safe url parameters with variable type as value {@see JFilterInput::clean()}.
			$registeredurlparams->$key = $value;
		}

		$app->registeredurlparams = $registeredurlparams;
	}

	/**
	 * Performs auth checks before saving subscription data
	 *
	 * @return bool
	 */
	protected function onBeforeSave()
	{
		$user  = JFactory::getUser();
		$subId = $this->input->getInt('akeebasubs_subscription_id', 0);

		// Guest user, go away!
		if($user->guest)
		{
			return false;
		}

		// No subscription info? Go away!
		if(!$subId)
		{
			return false;
		}

		// No info about custom fields? It's the only thing that user can update, so why continuing?
		if(!$this->input->get('subcustom', '', 2))
		{
			return false;
		}

		$sub = F0FModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
				->getItem($subId);

		// Editing a subscription of another user? Go away!
		if($user->id != $sub->user_id)
		{
			return false;
		}

		return true;
	}

	public function save()
	{
		// CSRF prevention
		if($this->csrfProtection)
		{
			$this->_csrfProtection();
		}

		// Set error message in case data won't be updated below
		$msgType = 'error';
		$msg     = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_UPDATE_ERROR');

		$subcustom = $this->input->get('subcustom', '', 2);

		// Let's setup a new input object, so I can reuse the code without messing up things
		$subId    = $this->input->getInt('akeebasubs_subscription_id');
		$newInput = clone $this->input;
		$newInput->set('opt', 'plugins');
		$newInput->set('id', $subId);

		$subscribes = F0FModel::getTmpInstance('Subscribes', 'AkeebasubsModel');
		$subscribes->setInput($newInput);

		// Subscription validation (plugins only)
		$data = $subscribes->getValidation();

		if($data->custom_valid && $data->subscription_custom_valid)
		{
			$table = F0FModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')->getItem($subId);

			// Let's get the info from previous slave subscriptions
			if(isset($table->params['slavesubs_ids']) && !empty($table->params['slavesubs_ids']))
			{
				$subcustom['slavesubs_ids'] = $table->params['slavesubs_ids'];
			}

			$table->params = json_encode($subcustom);
			if($table->store())
			{

				$msgType = 'info';
				$msg     = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_UPDATE_OK');
			}
		}

		$this->setRedirect(JRoute::_('index.php?option=com_akeebasubs&view=subscription&id='.$subId, false), $msg, $msgType);
	}
}
