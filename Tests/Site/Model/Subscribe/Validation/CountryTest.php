<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Admin\Helper\Select;
use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the Country validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\Country
 */
class CountryTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		self::$validatorType = 'Country';

		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		return [
			// ========== Collect personal info

			[
				'componentParams' => [
					'personalinfo' => 1,
					'showcountries' => '',
					'hidecountries' => '',
				],
				'state' => [
					'country' => ''
				],
				'expected' => false,
				'message' => 'Collect personal info, empty country: invalid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'showcountries' => '',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => true,
				'message' => 'Collect personal info, valid country: valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'showcountries' => '',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'XO'
				],
				'expected' => false,
				'message' => 'Collect personal info, invalid country: invalid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'showcountries' => 'GR,DE,US',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => true,
				'message' => 'Collect personal info, country in showcountries: valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'showcountries' => 'GR,DE,US',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'ES'
				],
				'expected' => false,
				'message' => 'Collect personal info, country NOT in showcountries: invalid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'showcountries' => '',
					'hidecountries' => 'GR,DE,US',
				],
				'state' => [
					'country' => 'ES'
				],
				'expected' => true,
				'message' => 'Collect personal info, country NOT in hidecountries: valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'showcountries' => '',
					'hidecountries' => 'GR,DE,US',
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => false,
				'message' => 'Collect personal info, country in hidecountries: invalid'
			],

			// ========== Collect country only

			[
				'componentParams' => [
					'personalinfo' => -1,
					'showcountries' => '',
					'hidecountries' => '',
				],
				'state' => [
					'country' => ''
				],
				'expected' => false,
				'message' => 'Collect country only, empty country: invalid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'showcountries' => '',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => true,
				'message' => 'Collect country only, valid country: valid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'showcountries' => '',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'XO'
				],
				'expected' => false,
				'message' => 'Collect country only, invalid country: invalid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'showcountries' => 'GR,DE,US',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => true,
				'message' => 'Collect country only, country in showcountries: valid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'showcountries' => 'GR,DE,US',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'ES'
				],
				'expected' => false,
				'message' => 'Collect country only, country NOT in showcountries: invalid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'showcountries' => '',
					'hidecountries' => 'GR,DE,US',
				],
				'state' => [
					'country' => 'ES'
				],
				'expected' => true,
				'message' => 'Collect country only, country NOT in hidecountries: valid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'showcountries' => '',
					'hidecountries' => 'GR,DE,US',
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => false,
				'message' => 'Collect country only, country in hidecountries: invalid'
			],

			// ========== Do NOT collect personal info

			[
				'componentParams' => [
					'personalinfo' => 0,
					'showcountries' => '',
					'hidecountries' => '',
				],
				'state' => [
					'country' => ''
				],
				'expected' => true,
				'message' => 'Do not collect personal info, empty country: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'showcountries' => '',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => true,
				'message' => 'Do not collect personal info, valid country: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'showcountries' => '',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'XO'
				],
				'expected' => true,
				'message' => 'Do not collect personal info, invalid country: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'showcountries' => 'GR,DE,US',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => true,
				'message' => 'Do not collect personal info, country in showcountries: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'showcountries' => 'GR,DE,US',
					'hidecountries' => '',
				],
				'state' => [
					'country' => 'ES'
				],
				'expected' => true,
				'message' => 'Do not collect personal info, country NOT in showcountries: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'showcountries' => '',
					'hidecountries' => 'GR,DE,US',
				],
				'state' => [
					'country' => 'ES'
				],
				'expected' => true,
				'message' => 'Do not collect personal info, country NOT in hidecountries: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'showcountries' => '',
					'hidecountries' => 'GR,DE,US',
				],
				'state' => [
					'country' => 'GR'
				],
				'expected' => true,
				'message' => 'Do not collect personal info, country in hidecountries: always valid'
			],

		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($componentParams, $state, $expected, $message)
	{
		foreach ($componentParams as $k => $v)
		{
			if (static::$container->params->get($k) != $v)
			{
				static::$container->params->set($k, $v);
				static::$container->params->save();
			}
		}

		Select::getFilteredCountries(true);

		parent::testGetValidationResult($state, $expected, $message);
	}


}