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
		if(!$table->akeebasubs_apicoupon_id)
		{
			return array('error' => JText::_('COM_AKEEBASUBS_APICOUPONS_INVALID_CREDENTIALS'));
		}

		// Do I hit a limit?
		// TODO Check for limits

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
}