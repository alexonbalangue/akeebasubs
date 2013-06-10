<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerMessages extends FOFController
{
	private static $loggedinUser = true;

	public function __construct($config = array()) {
		parent::__construct($config);

		$this->setThisModelName('AkeebasubsModelLevels');
		$this->csrfProtection = false;

		$this->cacheableTasks = array();
	}

	public function execute($task) {
		$task = 'read';
		$this->input->set('task','read');
		parent::execute($task);
	}

	/**
	 * Use the slug instead of the id to read a record
	 *
	 * @return bool
	 */
	public function onBeforeRead()
	{
		$this->getThisModel()->setIDsFromRequest();
		$id = $this->getThisModel()->getId();
		$slug = $this->input->getString('slug',null);

		if (!$id && $slug)
		{
			$records = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->slug($slug)
				->getItemList();

			if (!empty($records))
			{
				$item = array_pop($records);
				$this->getThisModel()->setId($item->akeebasubs_level_id);
			}
		}

		$subid = $this->input->getInt('subid',0);
		$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->setId($subid)
			->getItem();

		$this->getThisView()->assign('subscription',$subscription);

		// Joomla! 1.6 and later - we have to effectively "re-login" the user,
		// otherwise his ACL privileges are stale.
		$userid = JFactory::getUser()->id;

		if (empty($userid))
		{
			$userid = $subscription->user_id;
		}
		elseif ($userid != $subscription->user_id)
		{
			// The logged in user doesn't match the subscription's user; deny access
			self::$loggedinUser = false;
			return false;
		}

		// This line returns an empty JUser object
		$newUserObject = new JUser();
		// This line FORCE RELOADS the user record.
		$newUserObject->load($userid);

		// Maybe the user cannot be found?
		if (($newUserObject->id != $userid))
		{
			self::$loggedinUser = false;
			return false;
		}

		// Mark the user as logged in
		$newUserObject->block = 0;
		$newUserObject->set('guest', 0);

		// Register the needed session variables
		$session = JFactory::getSession();
		$session->set('user', $newUserObject);

		$db = JFactory::getDBO();

		// Check to see the the session already exists.
		$app = JFactory::getApplication();
		$app->checkSession();

		// Update the user related fields for the Joomla sessions table.
		$query = $db->getQuery(true)
			->update($db->qn('#__session'))
			->set(array(
				$db->qn('guest').' = ' . $db->q($newUserObject->get('guest')),
				$db->qn('username').' = ' . $db->q($newUserObject->get('username')),
				$db->qn('userid').' = ' . (int) $newUserObject->get('id')
			))->where($db->qn('session_id').' = '.$db->q($session->getId()));
		$db->setQuery($query);
		$db->execute();

		// Hit the user last visit field
		$newUserObject->setLastVisit();

		return true;
	}

	public function onAfterRead()
	{
		if(self::$loggedinUser) {
			// Log out the logged in user
			$my 		= JFactory::getUser();
			$session 	= JFactory::getSession();
			$app 		= JFactory::getApplication();

			// Make sure we're a valid user first
			if (!$my->get('tmp_user')) {
				return true;
			}

			// Hit the user last visit field
			$my->setLastVisit();

			// Destroy the php session for this user
			$session->destroy();

			// Force logout all users with that userid
			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->delete($db->qn('#__session'))
				->where($db->qn('userid').' = '.(int) $my->id)
				->where($db->qn('client_id').' = '.(int) $app->getClientId());
			$db->setQuery($query);
			$db->execute();
		}

		return true;
	}
}