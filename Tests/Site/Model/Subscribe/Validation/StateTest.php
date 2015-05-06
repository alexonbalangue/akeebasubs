<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use Akeeba\Subscriptions\Admin\Helper\Select;
use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the State validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\State
 */
class StateTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		self::$validatorType = 'State';

		parent::setUpBeforeClass();

		// Only enable the USA, Greece and Spain states
		$db = \JFactory::getDbo();
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_states'))
			->set($db->qn('enabled') . ' = ' . $db->q(0))
			->where($db->qn('country') . ' != ' . $db->q('US'));
		$db->setQuery($query)->execute();
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_states'))
			->set($db->qn('enabled') . ' = ' . $db->q(1))
			->where($db->qn('country') . ' = ' . $db->q('US'));
		$db->setQuery($query)->execute();
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_states'))
			->set($db->qn('enabled') . ' = ' . $db->q(1))
			->where($db->qn('country') . ' = ' . $db->q('GR'));
		$db->setQuery($query)->execute();
		$query = $db->getQuery(true)
			->update($db->qn('#__akeebasubs_states'))
			->set($db->qn('enabled') . ' = ' . $db->q(1))
			->where($db->qn('country') . ' = ' . $db->q('ES'));
		$db->setQuery($query)->execute();

		\Akeeba\Subscriptions\Admin\Helper\akeebasubsHelperSelect_init();
	}

	public function getTestData()
	{
		return [
			[
				'componentParams' => [
					'personalinfo' => 1,
					'_expectEmpty' => true
				],
				'state' => [
					'country' => 'GR',
					'state' => 'AL'
				],
				'expected' => false,
				'message' => 'Correct country, state belongs to other country: invalid'
			],

			// ========== Personal information: 1 (YES)
			[
				'componentParams' => [
					'personalinfo' => 1,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'US',
					'state' => 'AL'
				],
				'expected' => true,
				'message' => 'Correct country, state belongs to country: valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'_expectEmpty' => true
				],
				'state' => [
					'country' => 'US',
					'state' => 'BC'
				],
				'expected' => false,
				'message' => 'Correct country, state not belongs to country: invalid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'_expectEmpty' => true
				],
				'state' => [
					'country' => 'US',
					'state' => ''
				],
				'expected' => false,
				'message' => 'Correct country, no state: invalid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'_expectEmpty' => true
				],
				'state' => [
					'country' => '',
					'state' => ''
				],
				'expected' => false,
				'message' => 'Empty country, no state: invalid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'XX',
					'state' => ''
				],
				'expected' => true,
				'message' => 'No country (XX), no state: valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'_expectEmpty' => true
				],
				'state' => [
					'country' => 'CY',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Country without published states, no state: valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 1,
					'_expectEmpty' => true
				],
				'state' => [
					'country' => 'GR',
					'state' => 'AL'
				],
				'expected' => false,
				'message' => 'Correct country, state belongs to other country: invalid'
			],

			// ========== Personal information: -1 (Only country)
			[
				'componentParams' => [
					'personalinfo' => -1,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'US',
					'state' => 'AL'
				],
				'expected' => true,
				'message' => 'Personal information: only country. Correct country, state belongs to country: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'US',
					'state' => 'BC'
				],
				'expected' => true,
				'message' => 'Personal information: only country. Correct country, state not belongs to country: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'US',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Personal information: only country. Correct country, no state: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => '',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Personal information: only country. Empty country, no state: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'XX',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Personal information: only country. No country (XX), no state: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'CY',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Personal information: only country. Country without published states, no state: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => -1,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'GR',
					'state' => 'AL'
				],
				'expected' => true,
				'message' => 'Personal information: only country. Correct country, state belongs to other country: always valid'
			],

			// ========== Personal information: 0 (None)
			[
				'componentParams' => [
					'personalinfo' => 0,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'US',
					'state' => 'AL'
				],
				'expected' => true,
				'message' => 'Personal information: only country. Correct country, state belongs to country: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'US',
					'state' => 'BC'
				],
				'expected' => true,
				'message' => 'Personal information: only country. Correct country, state not belongs to country: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'US',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Personal information: only country. Correct country, no state: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => '',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Personal information: only country. Empty country, no state: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'XX',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Personal information: only country. No country (XX), no state: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'CY',
					'state' => ''
				],
				'expected' => true,
				'message' => 'Personal information: only country. Country without published states, no state: always valid'
			],
			[
				'componentParams' => [
					'personalinfo' => 0,
					'_expectEmpty' => false
				],
				'state' => [
					'country' => 'GR',
					'state' => 'AL'
				],
				'expected' => true,
				'message' => 'Personal information: only country. Correct country, state belongs to other country: always valid'
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
			if (substr($k, 0, 1) == '_')
			{
				continue;
			}

			if (ComponentParams::getParam($k) != $v)
			{
				ComponentParams::setParam($k, $v);
			}
		}

		Select::getFilteredCountries(true);

		parent::testGetValidationResult($state, $expected, $message);

		if ($componentParams['_expectEmpty'])
		{
			$this->assertEquals('', self::$state->state, $message . ' (State data MUST be reset!)');
		}
	}


}