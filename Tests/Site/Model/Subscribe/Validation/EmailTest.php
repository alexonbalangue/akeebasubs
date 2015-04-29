<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the Email validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Email
 */
class EmailTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'Email';

		// Create the base objects
		parent::setUpBeforeClass();

		// Create users afresh
		self::$users = [
			'guest' => clone self::$jUser
		];

		self::userDelete('user1');
		self::userDelete('user2');

		self::$users['user1'] = self::userCreate([
			'name'      => 'User One',
			'username'  => 'user1',
			'email'     => 'user1@test.web',
			'block'     => 0,
			'groups'    => [2],
			'guest'		=> 0,
		]);

		// Not a typo! For some reason I have to try creating user1 TWICE for it to be created. ONLY user1. No idea!
		self::$users['user1'] = self::userCreate([
			'name'      => 'User One',
			'username'  => 'user1',
			'email'     => 'user1@test.web',
			'block'     => 0,
			'groups'    => [2],
			'guest'		=> 0,
		]);

		self::$users['user2'] = self::userCreate([
			'name'      => 'User Two',
			'username'  => 'user2',
			'email'     => 'user2@test.web',
			'block'     => 1,
			'groups'    => [2],
			'guest'		=> 0,
		]);
	}

	public function getTestData()
	{
		return [
			[
				'loggedIn' => 'guest',
				'state'    => [
					'email' => 'user1@test.web'
				],
				'expected' => false,
				'message'  => 'Existing email, not blocked user: invalid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'email' => 'user2@test.web'
				],
				'expected' => false,
				'message'  => 'Existing email, blocked user: valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'email' => 'newuser@test.web'
				],
				'expected' => true,
				'message'  => 'Not existing email, simple format: valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'email' => 'new.user+is*good@unit-test.web'
				],
				'expected' => true,
				'message'  => 'Not existing email, bells and wistles: valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'email' => 'δοκιμή@unit-test.web'
				],
				'expected' => true,
				'message'  => 'Not existing email, UTF name: valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'email' => 'δοκιμή@έλεγχος.web'
				],
				'expected' => true,
				'message'  => 'Not existing email, UTF name and domain: valid'
			],
			[
				'loggedIn' => 'guest',
				'state'    => [
					'email' => '.invalid@die.web'
				],
				'expected' => false,
				'message'  => 'Not existing email, invalid format: invalid'
			],
		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($loggedIn, $state, $expected, $message)
	{
		self::$jUser = self::$users[$loggedIn];

		parent::testGetValidationResult($state, $expected, $message);
	}


}