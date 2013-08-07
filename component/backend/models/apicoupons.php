<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelApicoupons extends FOFModel
{
	public function createCoupon($key, $pwd)
	{
		// Do I have a key/pwd pair?
		if(!$key || !$pwd)
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_INVALID_CREDENTIALS'));
		}

		$table = $this->getTable();
		$table->load(array('key' => $key, 'password' => $pwd));

		// Are they valid?
		if(!$table->akeebasubs_apicoupon_id || !$table->enabled)
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_INVALID_CREDENTIALS'));
		}

		// Do I hit a limit?
		if(!$this->performApiChecks($table))
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_LIMIT_EXCEEDED'));
		}

		// If I'm here, I'm clear to go
		JLoader::import('joomla.user.helper');
		$coupon = FOFTable::getAnInstance('Coupon', 'AkeebasubsTable');

		$data['akeebasubs_apicoupon_id'] = $table->akeebasubs_apicoupon_id;
		$data['title']         = 'API coupon for: '.$table->title;
		$data['coupon']        = strtoupper(JUserHelper::genRandomPassword(10));
		$data['subscriptions'] = $table->subscriptions;

		// By default I want the coupon to be single-use
		$data['hitslimit'] = 1;
		$data['userhits']  = 1;

		$data['type']   = $table->type;
		$data['value']  = $table->value;

		if(!$coupon->save($data))
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_COUPON_ERROR'));
		}

		return array('coupon' => $coupon->coupon);
	}

	protected function performApiChecks($table)
	{
		$db = JFactory::getDbo();

		if($table->creation_limit)
		{
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from('#__akeebasubs_coupons')
				->where('akeebasubs_apicoupon_id = '.$table->akeebasubs_apicoupon_id);
			if($db->setQuery($query)->loadResult() >= $table->creation_limit)
			{
				return false;
			}
		}
		elseif($table->subscription_limit)
		{
			$query = $db->getQuery(true)
				->select('akeebasubs_coupon_id')
				->from('#__akeebasubs_coupons')
				->where('akeebasubs_apicoupon_id = '.$table->akeebasubs_apicoupon_id);
			$coupons = $db->setQuery($query)->loadColumn();

			if($coupons)
			{
				$query = $db->getQuery(true)
					->select('COUNT(*)')
					->from('#__akeebasubs_subscriptions')
					->where('akeebasubs_coupon_id IN('.implode(',', $coupons).')');
				if($db->setQuery($query)->loadResult() >= $table->subscription_limit)
				{
					return false;
				}
			}
		}
		elseif($table->value_limit)
		{
			$query = $db->getQuery(true)
				->select('akeebasubs_coupon_id')
				->from('#__akeebasubs_coupons')
				->where('akeebasubs_apicoupon_id = '.$table->akeebasubs_apicoupon_id);
			$coupons = $db->setQuery($query)->loadColumn();

			if($coupons)
			{
				$query = $db->getQuery(true)
					->select('SUM(net_amount) as total_amount, COUNT(*) as total')
					->from('#__akeebasubs_subscriptions')
					->where('akeebasubs_coupon_id IN('.implode(',', $coupons).')');
				$sub = $db->setQuery($query)->loadObject();

				if($table->type == 'value')
				{
					// Did I hit the limit using "fixed" discount value (ie 5$ off)?
					if(($sub->total * $table->value) >= $table->value_limit)
					{
						return false;
					}
				}
				else
				{
					// Did I hit the limit using % discount value (ie 15% off)?
					if(($sub->total_amount * $table->value / 100) >= $table->value_limit)
					{
						return false;
					}
				}

			}
		}

		return true;
	}
}