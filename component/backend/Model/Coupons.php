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
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

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
		if (empty($this->title))
		{
			throw new \RuntimeException(JText::_('COM_AKEEBASUBS_COUPON_ERR_TITLE'));
		}

		// Check for coupon code
		if (empty($this->coupon))
		{
			throw new \RuntimeException(JText::_('COM_AKEEBASUBS_COUPON_ERR_COUPON'));
		}

		// Normalize coupon code to uppercase
		$this->coupon = strtoupper($this->coupon);

		// Assign sensible publish_up and publish_down settings
		JLoader::import('joomla.utilities.date');

		if (empty($this->publish_up) || ($this->publish_up == $this->getDbo()->getNullDate()))
		{
			$jUp              = new JDate();
			$this->publish_up = $jUp->toSql();
		}
		else
		{
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

			if (!preg_match($regex, $this->publish_up))
			{
				$this->publish_up = '2001-01-01';
			}

			$jUp = new JDate($this->publish_up);
		}

		if (empty($this->publish_down) || ($this->publish_down == $this->getDbo()->getNullDate()))
		{
			$jDown              = new JDate('2030-01-01 00:00:00');
			$this->publish_down = $jDown->toSql();
		}
		else
		{
			$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

			if (!preg_match($regex, $this->publish_down))
			{
				$this->publish_down = '2037-01-01';
			}

			$jDown = new JDate($this->publish_down);
		}

		if ($jDown->toUnix() < $jUp->toUnix())
		{
			$temp               = $this->publish_up;
			$this->publish_up   = $this->publish_down;
			$this->publish_down = $temp;
		}
		elseif ($jDown->toUnix() == $jUp->toUnix())
		{
			$jDown              = new JDate('2030-01-01 00:00:00');
			$this->publish_down = $jDown->toSql();
		}

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
		if (is_array($value))
		{
			return $value;
		}

		if (empty($value))
		{
			return array();
		}

		$value = explode(',', $value);
		$value = array_map('trim', $value);

		return $value;
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
		if (!is_array($value))
		{
			return $value;
		}

		$value = array_map('trim', $value);
		$value = implode(',', $value);

		return $value;
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
		if (is_array($value))
		{
			return $value;
		}

		if (empty($value))
		{
			return array();
		}

		$value = explode(',', $value);
		$value = array_map('trim', $value);

		return $value;
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
		if (!empty($value))
		{
			if (is_array($value))
			{
				$subs = $value;
			}
			else
			{
				$subs = explode(',', $value);
			}
			if (empty($subs))
			{
				$value = '';
			}
			else
			{
				$subscriptions = array();

				/** @var DataModel $levelModel */
				$levelModel = $this->container->factory->model('Levels')
				                                       ->setIgnoreRequest(true)->savestate(false);

				foreach ($subs as $id)
				{
					try
					{
						$levelModel->reset(true, true);
						$levelModel->findOrFail($id);
						$id = $levelModel->akeebasubs_level_id;
					}
					catch (\Exception $e)
					{
						$id = null;
					}


					if (!is_null($id))
					{
						$subscriptions[] = $id;
					}
				}

				$value = implode(',', $subscriptions);
			}
		}
		else
		{
			return '';
		}

		return $value;
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

		JLoader::import('joomla.utilities.date');
		$jNow = new JDate();
		$uNow = $jNow->toUnix();

		$k     = $this->getKeyName();

		foreach ($resultArray as $index => &$row)
		{
			$triggered = false;

			if ($row->publish_down && ($row->publish_down != $this->getDbo()->getNullDate()))
			{
				$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';

				if (!preg_match($regex, $row->publish_down))
				{
					$row->publish_down = '2037-01-01';
				}

				if (!preg_match($regex, $row->publish_up))
				{
					$row->publish_up = '2001-01-01';
				}

				$jDown = new JDate($row->publish_down);
				$jUp   = new JDate($row->publish_up);

				if (($uNow >= $jDown->toUnix()) && $row->enabled)
				{
					$row->enabled = 0;
					$triggered    = true;
				}
				elseif (($uNow >= $jUp->toUnix()) && !$row->enabled && ($uNow < $jDown->toUnix()))
				{
					$row->enabled = 1;
					$triggered    = true;
				}
			}

			if ($triggered)
			{
				$row->save();
			}
		}
	}
}