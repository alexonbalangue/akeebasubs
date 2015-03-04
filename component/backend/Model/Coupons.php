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
use JDate;
use JLoader;
use JText;

class Coupons extends DataModel
{
	use Mixin\Assertions, Mixin\DateManipulation, Mixin\ImplodedArrays, Mixin\ImplodedLevels;

	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		// Always load the Filters behaviour
		$this->addBehaviour('Filters');
	}

	/**
	 * Check the data for validity.
	 *
	 * @return  static  Self, for chaining
	 *
	 * @throws \RuntimeException  When the data bound to this record is invalid
	 */
	public function check()
	{
		// Check for title
		$this->assertNotEmpty($this->title, 'COM_AKEEBASUBS_COUPON_ERR_TITLE');

		// Check for coupon code
		$this->assertNotEmpty($this->coupon, 'COM_AKEEBASUBS_COUPON_ERR_COUPON');

		// Normalize coupon code to uppercase
		$this->coupon = strtoupper($this->coupon);

		// Assign sensible publish_up and publish_down settings
		JLoader::import('joomla.utilities.date');

		// Normalise the publish up / down dates
		$this->publish_up = $this->normaliseDate($this->publish_up, '2001-01-01 00:00:00');
		$this->publish_down = $this->normaliseDate($this->publish_down, '2038-01-18 00:00:00');
		list($this->publish_up, $this->publish_down) = $this->sortPublishDates($this->publish_up, $this->publish_down);

		// Make sure the specified user (if any) exists
		if (!empty($this->user))
		{
			$userObject = \JFactory::getUser($this->user);
			$this->user = null;

			if (is_object($userObject))
			{
				if ($userObject->id > 0)
				{
					$this->user = $userObject->id;
				}
			}
		}

		// Check the hits limit
		if ($this->hitslimit <= 0)
		{
			$this->hitslimit = 0;
		}

		// Check the type
		if (!in_array($this->type, array('value', 'percent')))
		{
			$this->type = 'value';
		}

		// Check value
		if ($this->value < 0)
		{
			throw new \RuntimeException(JText::_('COM_AKEEBASUBS_COUPON_ERR_VALUE'));
		}

		if (($this->value > 100) && ($this->type == 'percent'))
		{
			$this->value = 100;
		}

		return $this;
	}

	/**
	 * Converts the loaded comma-separated list of user groups into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getUsergroupsAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of user groups into a comma separated list
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setUsergroupsAttribute($value)
	{
		return $this->setAttributeForImplodedArray($value);
	}

	/**
	 * Converts the loaded comma-separated list of subscription levels into an array
	 *
	 * @param   string  $value  The comma-separated list
	 *
	 * @return  array  The exploded array
	 */
	protected function getSubscriptionsAttribute($value)
	{
		return $this->getAttributeForImplodedArray($value);
	}

	/**
	 * Converts the array of subscription levels into a comma separated list
	 *
	 * @param   array  $value  The array of values
	 *
	 * @return  string  The imploded comma-separated list
	 */
	protected function setSubscriptionsAttribute($value)
	{
		return $this->setAttributeForImplodedLevels($value);
	}

	/**
	 * Post-process the loaded items list. Used to implement automatic expiration of coupons.
	 *
	 * @param   Coupons[]  $resultArray
	 */
	protected function onAfterGetItemsArray(&$resultArray)
	{
		// Implement the coupon automatic expiration
		if (empty($resultArray))
		{
			return;
		}

		if ($this->getState('skipOnProcessList', 0))
		{
			return;
		}

		foreach ($resultArray as $index => &$row)
		{
			$this->publishByDate($row);
		}
	}
}