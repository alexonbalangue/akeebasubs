<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerMessages extends FOFController
{
	private static $loggedinUser = false;

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
		if(!$id && $slug) {
			$records = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->slug($slug)
				->getItemList();
			if(!empty($records)) {
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
		if(empty($userid)) {
			self::$loggedinUser = true;
			$userid = $subscription->user_id;
		} else {
			self::$loggedinUser = false;
			$session = JFactory::getSession();
			$session->set('loggedin', null, 'com_akeebasubs');
		}

		if($userid) {
			// This line returns an empty JUser object
			$newUserObject = new JUser();
			// This line FORCE RELOADS the user record.
			$newUserObject->load($userid);

			if(($newUserObject->id == $userid) && !$newUserObject->block)
			{
				// Mark the user as logged in
				$newUserObject->set('guest', 0);
				// Register the needed session variables
				$session = JFactory::getSession();
				$session->set('user', $newUserObject);
				$db = JFactory::getDBO();
				// Check to see the the session already exists.
				$app = JFactory::getApplication();
				$app->checkSession();
				// Update the user related fields for the Joomla sessions table.
				$db->setQuery(
					'UPDATE `#__session`' .
					' SET `guest` = '.$db->q($newUserObject->get('guest')).',' .
					'	`username` = '.$db->q($newUserObject->get('username')).',' .
					'	`userid` = '.(int) $newUserObject->get('id') .
					' WHERE `session_id` = '.$db->q($session->getId())
				);
				$db->execute();
				// Hit the user last visit field
				$newUserObject->setLastVisit();
			} elseif(($newUserObject->id == $userid) && $newUserObject->block) {
				// Register the needed session variables
				$session = JFactory::getSession();
				$newUserObject = new JUser();
				$session->set('user', $newUserObject);
				$db = JFactory::getDBO();
				// Check to see the the session already exists.
				$app = JFactory::getApplication();
				$app->checkSession();
				// Update the user related fields for the Joomla sessions table.
				$db->setQuery(
					'UPDATE `#__session`' .
					' SET `guest` = '.$db->q($newUserObject->get('guest')).',' .
					'	`username` = '.$db->q($newUserObject->get('username')).',' .
					'	`userid` = '.(int) $newUserObject->get('id') .
					' WHERE `session_id` = '.$db->q($session->getId())
				);
				$db->execute();
			}
		}

		return true;
	}

	public function onAfterRead()
	{
		if(self::$loggedinUser) {
			$newUserObject = new JUser();
			$newUserObject->set('guest', 0);

			$session = JFactory::getSession();
			$session->set('user', $newUserObject);
			$db = JFactory::getDBO();
			$app = JFactory::getApplication();
			$app->checkSession();
			$query = $db->getQuery(true)
				->delete($db->qn('#__session'))
				->where( $db->qn('session_id') .' = '.$db->q($session->getId()) );
			$db->setQuery($query);
			$db->execute();
		}

		return true;
	}
}