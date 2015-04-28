<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Site\Model\Subscribe\StateData;
use Akeeba\Subscriptions\Site\Model\Subscribe\ValidatorFactory;
use FOF30\Container\Container;
use JUser;

/**
 * Test the Name validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Name
 */
class Name extends \PHPUnit_Framework_TestCase
{
	/** @var   ValidatorFactory  The validator factory for this class */
	public static $factory = null;

	/** @var   Container  The container of the component */
	public static $container = null;

	/** @var   StateData  The state data we're operating on */
	public static $state = null;

	/** @var   JUser  The currently active Joomla! user object */
	public static $jUser = null;

	/**
	 * Set up the static objects before the class is created
	 */
	public static function setUpBeforeClass()
	{
		if (is_null(static::$container))
		{
			static::$container = Container::getInstance('com_akeebasubs');
		}

		if (is_null(static::$jUser))
		{
			static::$jUser = new JUser();
		}

		$model = static::$container->factory->model('Subscribe');
		static::$state = new StateData($model);

		static::$factory = new ValidatorFactory(static::$container, static::$state, static::$jUser);
	}

	public function getTestData()
	{
		return [
			[
				'state' => [
					'name' => 'Foobar'
				],
				'expected' => false,
				'message' => 'Single word names are not allowed'
			],
			[
				'state' => [
					'name' => 'Foo bar'
				],
				'expected' => true,
				'message' => 'Two word names are allowed'
			],
			[
				'state' => [
					'name' => 'Foo bar baz'
				],
				'expected' => true,
				'message' => 'Three word names are allowed'
			],
			[
				'state' => [
					'name' => 'a b'
				],
				'expected' => true,
				'message' => 'Single letter names with two parts are allowed'
			],
		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($state, $expected, $message)
	{
		static::$state->reset();

		foreach ($state as $k => $v)
		{
			static::$state->$k = $v;
		}

		$validator = static::$factory->getValidator('Name');
		$actual = $validator->execute(true);

		$this->assertEquals($expected, $actual, $message);
	}
}