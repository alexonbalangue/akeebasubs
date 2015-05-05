<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Admin\Model\Relations;
use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the BestAutomaticDiscount validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\BestAutomaticDiscount
 */
class BestAutomaticDiscountTest extends ValidatorTestCase
{

	protected static $subscriptions = [];

	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'BestAutomaticDiscount';

		// Create the base objects
		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		$jNow = \JFactory::getDate();

		$jLastYear = clone $jNow;
		$jLastYear->sub(new \DateInterval('P1Y1D'));

		$j13MonthsAgo = clone $jNow;
		$j13MonthsAgo->sub(new \DateInterval('P1Y1M'));

		$jNextYear = clone $jNow;
		$jNextYear->add(new \DateInterval('P1Y1D'));

		$jLastHalfYear = clone($jNow);
		$jLastHalfYear->sub(new \DateInterval('P181D'));

		$jLastMonth = clone($jNow);
		$jLastMonth->sub(new \DateInterval('P31D'));

		$jThreeMonthsAgo = clone($jNow);
		$jThreeMonthsAgo->sub(new \DateInterval('P92D'));

		$jElevenMonthsAgo = clone($jNow);
		$jElevenMonthsAgo->sub(new \DateInterval('P335D'));

		$j370DaysAgo = clone($jNow);
		$j370DaysAgo->sub(new \DateInterval('P370D'));

		return [
			[
				'loggedIn' => 'guest',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount'   => 0.0,
					'expiration' => 'overlap',
					'oldsub'     => null,
					'allsubs'    => [],
					'upgrade_id' => null,
				],
				'message'  => 'Not logged in, no SLL or upgrade rule'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 10,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'discount'   => 0.0,
					'expiration' => 'overlap',
					'oldsub'     => null,
					'allsubs'    => [],
					'upgrade_id' => null,
				],
				'message'  => 'Logged in, no SLL or upgrade rule'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '9',
				],
				'expected' => [
					'discount'   => 25.0,
					'expiration' => 'overlap',
					'oldsub'     => null,
					'allsubs'    => [],
					'upgrade_id' => 13,
				],
				'message'  => 'Only upgrade rule'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 1,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '10',
				],
				'expected' => [
					'discount'   => 15.0,
					'expiration' => 'replace',
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'upgrade_id' => null,
				],
				'message'  => 'Upgrade and SLL, SLL wins due to bigger discount'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 2,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '9',
				],
				'expected' => [
					'discount'   => 15.0,
					'expiration' => 'replace',
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'upgrade_id' => null,
				],
				'message'  => 'SLL only'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 2,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '9',
				],
				'expected' => [
					'discount'   => 15.0,
					'expiration' => 'replace',
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'upgrade_id' => null,
				],
				'message'  => 'SLL only'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level'      => 2,
						'publish_up' => $jNow->toSql()
					]
				],
				'state'    => [
					'id' => '10',
				],
				'expected' => [
					'discount'   => 25.0,
					'expiration' => 'replace',
					'oldsub'     => 'S1',
					'allsubs'    => ['S1'],
					'upgrade_id' => 15,
				],
				'message'  => 'Upgrade and SLL, the upgrade wins but SLL applies the subscription replacement policy, combine is ignored'
			]
		];
	}

	/**
	 * Test the validator
	 *
	 * @dataProvider getTestData
	 */
	public function testGetValidationResult($loggedIn, $subs, $state, $expected, $message)
	{
		$this->createSubscriptions($subs);

		self::$jUser = self::$users[ $loggedIn ];
		self::$factory->reset();

		parent::testGetValidationResult($state, $expected, $message);
	}

	/**
	 * Perform the assertion(s) required for this test
	 *
	 * @param   mixed  $expected Expected value
	 * @param   mixed  $actual   Actual validator result
	 * @param   string $message  Message to show on failure
	 *
	 * @return  void
	 */
	public function performAssertion($expected, $actual, $message)
	{
		$expected['oldsub']  = self::translateSubToId($expected['oldsub']);
		$expected['allsubs'] = self::translateSubToId($expected['allsubs']);

		parent::performAssertion($expected, $actual, $message);
	}

	/**
	 * The subscription records are created dynamically. Therefore our 'expected' array cannot have hard-coded IDs.
	 * Instead we use fake subscription IDs in the format S1, S2 and so on. The number after the 'S' denotes the
	 * subscription created by the createSubscriptions() method. The translateSubToId() method translates these fake
	 * IDs into the actual numeric subscription IDs. The $sub operand can be a string or an array. If $sub is null we
	 * will simply return null.
	 *
	 * @param   mixed $sub
	 *
	 * @return  mixed
	 */
	protected static function translateSubToId($sub)
	{
		if (is_null($sub))
		{
			return null;
		}

		if (is_array($sub))
		{
			$ret = [];

			foreach ($sub as $s)
			{
				$ret[] = self::translateSubToId($s);
			}

			return $ret;
		}

		if (isset(self::$subscriptions[ $sub ]))
		{
			return self::$subscriptions[ $sub ];
		}

		return null;
	}

	/**
	 * Creates subscriptions by directly inserting into the database, without going through DataModel.
	 *
	 * @param   array $subs    An array of arrays containing the customised subscription fields we're going to insert
	 * @param   int   $user_id The numeric user ID of the subscriber
	 *
	 * @return  void
	 */
	protected function createSubscriptions(array $subs, $user_id = 1020)
	{
		self::$subscriptions = [];
		$db                  = \JFactory::getDbo();
		$db->transactionStart();

		$query = $db->getQuery(true)
		            ->delete('#__akeebasubs_subscriptions')
		            ->where($db->qn('user_id') . ' = ' . $db->q($user_id));
		$db->setQuery($query)->execute();

		if (!empty($subs))
		{
			$i = 0;

			foreach ($subs as $params)
			{
				if (isset($params['level']))
				{
					$params['akeebasubs_level_id'] = $params['level'];
					unset ($params['level']);
				}

				if (!isset($params['user_id']))
				{
					$params['user_id'] = $user_id;
				}

				$query = $this->getSubscriptionQuery($params);
				$db->setQuery($query)->execute();

				$id = $db->insertid();

				$i ++;
				self::$subscriptions[ 'S' . $i ] = $id;
			}
		}

		$db->transactionCommit();
	}

	/**
	 * Get the SQL for inserting a subscription row
	 *
	 * @param   array $params Fields for creating the subscription row
	 *
	 * @return  string
	 */
	protected function getSubscriptionQuery(array $params)
	{
		$db = \JFactory::getDbo();

		$jNow = \JFactory::getDate();

		$defaultParams = [
			'user_id'               => 1020,
			'akeebasubs_level_id'   => 1,
			'publish_up'            => $jNow->toSql(),
			'publish_down'          => null,
			'enabled'               => 1,
			'processor'             => 'none',
			'processor_key'         => md5(microtime()),
			'state'                 => 'C',
			'net_amount'            => 100,
			'tax_amount'            => 0,
			'gross_amount'          => 0,
			'recurring_amount'      => 0,
			'tax_percent'           => 0,
			'created_on'            => $jNow->toSql(),
			'params'                => '',
			'akeebasubs_coupon_id'  => 0,
			'akeebasubs_upgrade_id' => 0,
			'prediscount_amount'    => 0,
			'discount_amount'       => 0,
			'contact_flag'          => 0,
			'first_contact'         => $db->getNullDate(),
			'second_contact'        => $db->getNullDate(),
			'after_contact'         => $db->getNullDate(),
		];

		$params = array_merge($defaultParams, $params);

		$params['gross_amount'] = $params['net_amount'] + $params['tax_amount'];

		if ($params['gross_amount'] > 0.001)
		{
			$params['tax_percent'] = 100.0 * $params['tax_amount'] / $params['gross_amount'];
		}

		if (empty($params['publish_down']))
		{
			$oneYear                = new \DateInterval('P1Y');
			$jTo                    = \JFactory::getDate($params['publish_up'])->add($oneYear);
			$params['publish_down'] = $jTo->toSql();
		}

		$query = $db->getQuery(true)
		            ->insert($db->qn('#__akeebasubs_subscriptions'));

		$columns = [];
		$values  = [];

		foreach ($params as $field => $value)
		{
			$columns[] = $db->qn($field);
			$values[]  = $db->q($value);
		}

		$query->columns($columns)
		      ->values(implode(',', $values));

		return (string) $query;
	}
}