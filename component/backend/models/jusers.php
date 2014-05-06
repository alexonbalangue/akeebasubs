<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelJusers extends F0FModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__users'));

		$username = $this->getState('username',null,'raw');
		if(!empty($username)) {
			$query->where($db->qn('username').' = '.$db->q($username));
		}

		$userid = $this->getState('user_id',null,'int');
		if(!empty($userid)) {
			$query->where($db->qn('id').' = '.$db->q($userid));
		}

		$email = $this->getState('email',null,'raw');
		if(!empty($email)) {
			$query->where($db->qn('email').' = '.$db->q($email));
		}

		$block = $this->getState('block',null,'int');
		if(!is_null($block)) {
			$query->where($db->qn('block').' = '.$db->q($block));
		}

		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where(
				'('.
				'('.$db->qn('username').' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('name').' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('email').' LIKE '.$db->q($search).') '.
				')'
			);
		}

		$order = $this->getState('filter_order', 'id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}

	public function createNewUser($params)
	{
		$user = new JUser(0);

		JLoader::import('joomla.application.component.helper');
		$usersConfig = JComponentHelper::getParams( 'com_users' );
		$newUsertype = $usersConfig->get( 'new_usertype' );

		// get the New User Group from com_users' settings
		if (empty($newUsertype))
		{
			$newUsertype = 2;
		}

		$params['groups']    = array($newUsertype);
		$params['sendEmail'] = 0;

		// Set the user's default language to whatever the site's current language is
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$params['params'] = array(
				'language'	=> JFactory::getConfig()->get('language')
			);
		} else {
			$params['params'] = array(
				'language'	=> JFactory::getConfig()->getValue('config.language')
			);
		}

		JLoader::import('joomla.user.helper');
		$params['block'] = 0;
		$randomString = JUserHelper::genRandomPassword();
		if (version_compare(JVERSION, '3.2', 'ge'))
		{
			$hash = JApplication::getHash($randomString);
		}
		else
		{
			$hash = JFactory::getApplication()->getHash($randomString);
		}
		$params['activation'] = $hash;

		$user->bind($params);
		$userIsSaved = $user->save();

		if($userIsSaved)
		{
			return $user->id;
		}
		else
		{
			return false;
		}
	}
}