<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Stubs;

use Akeeba\Subscriptions\Admin\Model\TaxConfig;
use FOF30\Container\Container;
use JFactory;
use JUser;
use JUserHelper;

abstract class CommonSetup
{
	/** @var   array  Known users we have already created */
	protected static $users = [];

	public static function masterSetup()
	{
		self::getUsers();
		self::setupTaxRules();
	}

	/**
	 * Get a list of pre-fabricated Joomla! users we're using throughout our tests
	 *
	 * @return  JUser[]
	 */
	public static function getUsers()
	{
		if (empty(self::$users))
		{
			// Create users afresh
			self::$users = [
				'guest' => new JUser()
			];

			// Delete existing users
			self::userDelete('user1');
			self::userDelete('user2');
			self::userDelete('user3');

			// Regular, active user
			self::$users['user1'] = self::userCreate([
				'name'     => 'User One',
				'username' => 'user1',
				'email'    => 'user1@test.web',
				'block'    => 0,
				'groups'   => [2],
				'guest'    => 0,
			]);

			// Not a typo! For some reason I have to try creating user1 TWICE for it to be created. ONLY user1. No idea!
			self::$users['user1'] = self::userCreate([
				'name'     => 'User One',
				'username' => 'user1',
				'email'    => 'user1@test.web',
				'block'    => 0,
				'groups'   => [2],
				'guest'    => 0,
			]);

			// Blocked, activated user (not allowed to be a subscriber)
			self::$users['user2'] = self::userCreate([
				'name'     => 'User Two',
				'username' => 'user2',
				'email'    => 'user2@test.web',
				'block'    => 1,
				'groups'   => [2],
				'guest'    => 0,
			]);

			// Blocked, not activated user (allowed to be a subscriber)
			self::$users['user3'] = self::userCreate([
				'name'       => 'User Three',
				'username'   => 'user3',
				'email'      => 'user3@test.web',
				'block'      => 1,
				'groups'     => [2],
				'guest'      => 0,
				'activation' => 'notempty'
			]);
		}

		return self::$users;
	}

	/**
	 * Set up the tax rules. Default configuration: Cyprus, 19% VAT, VIES registered business
	 */
	public static function setupTaxRules()
	{
		$container = Container::getInstance('com_akeebasubs', [
			'tempInstance' => true,
			'factoryClass' => '\\FOF30\\Factory\\SwitchFactory'
		], 'admin');
		/** @var TaxConfig $taxConfigModel */
		$taxConfigModel = new TaxConfig($container);

		foreach ([
					 'novatcalc' => 0,
					 'akeebasubs_level_id'  => 0,
					 'country' => 'CY',
					 'taxrate' => '19',
					 'viesreg' => 1,
					 'showvat' => 0,
				 ] as $key => $value)
		{
			$taxConfigModel->setState($key, $value);
		}

		$taxConfigModel->clearTaxRules();
		$taxConfigModel->createTaxRules();
		$taxConfigModel->applyComponentConfiguration();
	}

	/**
	 * Delete a Joomla! user by username
	 *
	 * @param   string  $username  The username of the user to delete
	 *
	 * @return  void
	 */
	protected static function userDelete($username)
	{
		$userId = JUserHelper::getUserId($username);

		if ($userId == 0)
		{
			return;
		}

		$db = JFactory::getDbo();

		$query = $db->getQuery(true)
					->delete('#__users')
					->where($db->qn('id') . ' = ' . $db->q($userId));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
					->delete('#__user_usergroup_map')
					->where($db->qn('user_id') . ' = ' . $db->q($userId));
		$db->setQuery($query)->execute();

		$query = $db->getQuery(true)
					->delete('#__user_profiles')
					->where($db->qn('user_id') . ' = ' . $db->q($userId));
		$db->setQuery($query)->execute();
	}

	/**
	 * Create a Joomla! user
	 *
	 * @param   array   $userInfo  The information of the user being created
	 *
	 * @return  JUser  The newly created user
	 */
	protected static function userCreate(array $userInfo)
	{
		$user = new JUser();
		$user->bind($userInfo);
		$user->save();

		return $user;
	}
}