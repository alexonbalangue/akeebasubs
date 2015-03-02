<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerMessages extends F0FController
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
			$records = F0FModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->slug($slug)
				->getItemList();

			if (!empty($records))
			{
				$item = array_pop($records);
				$this->getThisModel()->setId($item->akeebasubs_level_id);
			}
		}

		$subid = $this->input->getInt('subid',0);
		$subscription = F0FModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->setId($subid)
			->getItem();

		// Working around Progressive Caching
		JFactory::getApplication()->input->set('subid', $subid);
		$this->registerUrlParams(array(
			'subid' => 'INT'
		));

		$this->getThisView()->assign('subscription',$subscription);

		if ($subscription->akeebasubs_level_id)
		{
			$this->getThisModel()->setId($subscription->akeebasubs_level_id);
		}

		/**
		 * Joomla! 1.6 and later - we have to effectively "re-login" the user,
		 * otherwise his ACL privileges are stale.
		 */

		// Get the current user's ID
		$userid = JFactory::getUser()->id;

		// Get a reference to Joomla!'s session object
		$session = JFactory::getSession();

		if (empty($userid))
		{
			// Guest user; we'll have to log him in
			$userid = $subscription->user_id;

			// Is it the same user who initiated the subscription payment?
			$subscriber_user_id = $session->get('subscribes.user_id', null, 'com_akeebasubs');

			if ($subscriber_user_id == $subscription->user_id)
			{
				// Do not log him out; he's the user who initiated this subscription
				self::$loggedinUser = false;

				// Unset the subscriber user ID value
				$session->set('subscribes.user_id', null, 'com_akeebasubs');
			}
			else
			{
				// This is just someone who knows the URL. Let's log him out
				// after we're done showing the page.
				self::$loggedinUser = true;
			}
		}
		elseif ($userid == $subscription->user_id)
		{
			// User already logged in. We'll log him back in (due to Joomla!
			// ACLs not being applied otherwise) but we are not going to log him
			// back out.
			self::$loggedinUser = false;
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

		if (($newUserObject->id != $userid))
		{
			// The user cannot be found. Abort.
			self::$loggedinUser = false;
			return false;
		}

		// If it is a blocked user let's log him out after loading this page.
		// This decision is made no matter how we ended up deciding to log in
		// this user.
		if ($newUserObject->block)
		{
			self::$loggedinUser = true;
		}

		// Mark the user as logged in
		$newUserObject->block = 0;
		$newUserObject->set('guest', 0);

		// Register the needed session variables
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
		// Log out the logged in user
		if (self::$loggedinUser)
		{
			$userid = JFactory::getUser()->id;
			$newUserObject = new JUser();
			$newUserObject->load($userid);

			$app = JFactory::getApplication();

			// Perform the log out.
			$error = $app->logout();

			if ($newUserObject->block)
			{
				$newUserObject->lastvisitDate = JFactory::getDbo()->getNullDate();
				$newUserObject->save();
			}
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
}