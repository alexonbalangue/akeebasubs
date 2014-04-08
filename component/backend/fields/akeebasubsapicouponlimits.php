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
class F0FFormFieldAkeebasubsapicouponlimits extends F0FFormFieldText
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

		if($this->item->subscriptions)  $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_LEVELS');
		if($this->item->creation_limit) $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_HITS');

		$strLimits = implode(', ', $limits);

		return $strLimits;
	}
}
