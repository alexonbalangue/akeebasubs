<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Model;

defined('_JEXEC') or die;

use FOF30\Container\Container;
use FOF30\Model\DataModel;
use JApplicationHelper;
use JComponentHelper;
use JFactory;
use JLoader;
use JUser;
use JUserHelper;

class JoomlaUsers extends DataModel
{
	/**
	 * Override the constructor since I need to attach to a core table and add the Filters behaviour
	 *
	 * @param Container $container
	 * @param array     $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		$config['tableName'] = '#__users';
		$config['idFieldName'] = 'id';

		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');
	}

	/**
	 * Overridden for additional filters which are not implemented by the Filters behaviour
	 *
	 * @param   bool  $overrideLimits
	 *
	 * @return  \JDatabaseQuery
	 */
	public function buildQuery($overrideLimits = false)
	{
		$query = parent::buildQuery($overrideLimits);
		$db = $this->getDbo();

		$userId = $this->getState('user_id', null, 'int');

		if (!empty($userId))
		{
			$query->where($db->qn('id') . ' = ' . $db->q($userId));
		}

		$search = $this->getState('search', null);

		if ($search)
		{
			$search = '%' . $search . '%';
			$query->where(
				'(' .
				'(' . $db->qn('username') . ' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('name') . ' LIKE ' . $db->q($search) . ') OR ' .
				'(' . $db->qn('email') . ' LIKE ' . $db->q($search) . ') ' .
				')'
			);
		}

		return $query;
	}

	/**
	 * Creates a new Joomla! user
	 *
	 * @param   array  $params  The parameters to bind to the new user entry
	 *
	 * @return  bool|int  The user ID if successful, false if it failed
	 */
	public function createNewUser(array $params)
	{
		JLoader::import('joomla.application.component.helper');
		JLoader::import('joomla.user.helper');

		$user = new JUser(0);

		$usersConfig = JComponentHelper::getParams('com_users');
		$newUsertype = $usersConfig->get('new_usertype');

		// get the New User Group from com_users' settings
		if (empty($newUsertype))
		{
			$newUsertype = 2;
		}

		$params['groups']    = array($newUsertype);
		$params['sendEmail'] = 0;

		// Set the user's default language to whatever the site's current language is
		if (!isset($params['params']) || !is_array($params['params']))
		{
			$params['params'] = array();
		}

		$params['params']['language'] = JFactory::getConfig()->get('language');

		$params['block']      = 0;
		$randomString         = JUserHelper::genRandomPassword();
		$hash                 = JApplicationHelper::getHash($randomString);
		$params['activation'] = $hash;

		$user->bind($params);
		$userIsSaved = $user->save();

		if ($userIsSaved)
		{
			return $user->id;
		}
		else
		{
			return false;
		}
	}
}