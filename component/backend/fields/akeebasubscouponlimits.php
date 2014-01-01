<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Renders the price of a subscription level and its optional sign-up fee
 */
class FOFFormFieldAkeebasubscouponlimits extends FOFFormFieldText
{
	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
		$limits = array();

		if($this->item->user)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_USERS').' ('.JFactory::getUser($this->item->user)->username.')';
		}

		if($this->item->email)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_EMAIL'). ' ('.$this->item->email.')';
		}

		if($this->item->subscriptions)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_LEVELS');
		}

		if($this->item->hitslimit)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_HITS');
		}

		if($this->item->userhits)
		{
			$limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_USERHITS');
		}

		$strLimits = implode(', ', $limits);

		return $strLimits;
	}
}
