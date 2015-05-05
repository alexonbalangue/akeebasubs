<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Tests\Site\Model\Subscribe\Validation;

use Akeeba\Subscriptions\Tests\Stubs\ValidatorTestCase;

/**
 * Test the BestUpgradeDiscount validator
 *
 * @covers Akeeba\Subscriptions\Site\Model\Subscribe\Validation\BestUpgradeDiscount
 */
class BestUpgradeDiscountTest extends ValidatorTestCase
{
	public static function setUpBeforeClass()
	{
		// Set the validator type
		self::$validatorType = 'BestUpgradeDiscount';

		// Create the base objects
		parent::setUpBeforeClass();
	}

	public function getTestData()
	{
		$jNow = \JFactory::getDate();

		$jLastYear = clone $jNow;
		$jLastYear->sub(new \DateInterval('P1Y1D'));

		$jTwoYearsAgo = clone $jNow;
		$jTwoYearsAgo->sub(new \DateInterval('P2Y'));

		$jLastHalfYear = clone($jNow);
		$jLastHalfYear->sub(new \DateInterval('P181D'));

		$j370DaysAgo = clone($jNow);
		$j370DaysAgo->sub(new \DateInterval('P370D'));

		return [
			[
				'loggedIn' => 'guineapig',
				'subs'     => [],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => null,
					'value'      => 0.0,
				    'combine'    => false
				],
				'message'  => 'No upgrade'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jLastYear->toSql(),
					    'enabled' => 0,
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 4,
					'value'      => 30.0
					,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal – caught by expired subscription rule'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jNow->toSql(),
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 1,
					'value'      => 10.0,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal, first six months'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 3,
					'value'      => 20.0,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal, last six months'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jLastHalfYear->toSql(),
						'net_amount' => 80,
					]
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 3,
					'value'      => 16.0,
					'combine'    => false
				],
				'message'  => 'LEVEL1 renewal, last six months, different price for lastpercent'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'upgrade_id' => 2,
					'value'      => 5.00,
					'combine'    => false
				],
				'message'  => 'LEVEL1 to LEVEL2, fixed price'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'LEVEL1 to LEVEL2, no subscription (rule not applied)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
					    'publish_up' => $j370DaysAgo->toSql(),
					    'enabled' => 0
					]
				],
				'state'    => [
					'id' => '2',
				],
				'expected' => [
					'upgrade_id' => 5,
					'value'      => 12.34,
					'combine'    => false
				],
				'message'  => 'Expired LEVEL1 up to 10 days to LEVEL2, fixed value – rule validated by UpgradeExpiredDiscount'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '4',
				],
				'expected' => [
					'upgrade_id' => 6,
					'value'      => 10.00,
					'combine'    => true
				],
				'message'  => 'LEVEL1 to FOREVER, 10%'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 2,
						'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '4',
				],
				'expected' => [
					'upgrade_id' => 7,
					'value'      => 10.00,
					'combine'    => true
				],
				'message'  => 'LEVEL2 to FOREVER, 10%'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 1,
						'publish_up' => $jLastHalfYear->toSql(),
					],
					[
						'level' => 2,
						'publish_up' => $jLastHalfYear->toSql(),
					]
				],
				'state'    => [
					'id' => '4',
				],
				'expected' => [
					'upgrade_id' => 7,
					'value'      => 20.00,
					'combine'    => true
				],
				'message'  => 'LEVEL1 and LEVEL2 to FOREVER, combined 10% each (the second rule is reported as active)'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 3,
						'publish_up' => $jLastHalfYear->toSql(),
					],
				],
				'state'    => [
					'id' => '5',
				],
				'expected' => [
					'upgrade_id' => 8,
					'value'      => 10.00,
					'combine'    => true
				],
				'message'  => 'RECURRING to FIXED, active, combined 10%'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 3,
						'publish_up' => $jLastYear->toSql(),
					    'enabled' => 0
					],
				],
				'state'    => [
					'id' => '5',
				],
				'expected' => [
					'upgrade_id' => 9,
					'value'      => 10.00,
					'combine'    => true
				],
				'message'  => 'RECURRING to FIXED, expired, combined 10% – only validated by UpgradeExpiredDiscount'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 3,
						'publish_up' => $jLastHalfYear->toSql(),
					    'enabled' => 1
					],
					[
						'level' => 3,
						'publish_up' => $jLastYear->toSql(),
					    'enabled' => 0
					],
				],
				'state'    => [
					'id' => '5',
				],
				'expected' => [
					'upgrade_id' => 8, // The UpgradeDiscount rule is reported, NOT the UpgradeExpiredDiscount
					'value'      => 20.00,
					'combine'    => true
				],
				'message'  => 'RECURRING to FIXED, active and expired, both combined, 2x10% – validated by both UpgradeDiscount and UpgradeExpiredDiscount'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 6,
						'publish_up' => $jLastYear->toSql(),
					],
				],
				'state'    => [
					'id' => '5',
				],
				'expected' => [
					'upgrade_id' => 0,
					'value'      => 0.00,
					'combine'    => false
				],
				'message'  => 'FREE to FIXED: the rule is unpublished'
			],
			[
				'loggedIn' => 'guineapig',
				'subs'     => [
					[
						'level' => 2,
						'publish_up' => $jLastHalfYear->toSql(),
						'enabled' => 1
					],
					[
						'level' => 2,
						'publish_up' => $jLastYear->toSql(),
						'enabled' => 0
					],
				],
				'state'    => [
					'id' => '1',
				],
				'expected' => [
					'upgrade_id' => 11, // The UpgradeDiscount rule is validated
					'value'      => 15.00,
					'combine'    => false
				],
				'message'  => 'LEVEL2 to LEVEL1, has both expired and active, no combine, the best discount is picked'
			],
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

	protected function createSubscriptions(array $subs, $user_id = 1020)
	{
		// array of arrays with keys: level, publish_up
		$db = \JFactory::getDbo();
		$db->transactionStart();

		$query = $db->getQuery(true)
		            ->delete('#__akeebasubs_subscriptions')
		            ->where($db->qn('user_id') . ' = ' . $db->q($user_id));
		$db->setQuery($query)->execute();

		if (!empty($subs))
		{
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