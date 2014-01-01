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
class FOFFormFieldAkeebasubsdiscount extends FOFFormFieldText
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
		include_once FOFTemplateUtils::parsePath('admin://components/com_akeebasubs/helpers/cparams.php', true);

		// Initialise
		$class				= $this->id;
		$typeField			= 'type';
		$classValue			= 'akeebasubs-coupon-discount-value';
		$classPercent		= 'akeebasubs-coupon-discount-percent';
		$classLastPercent	= 'akeebasubs-coupon-discount-lastpercent';

		// Get field parameters
		if ($this->element['class'])
		{
			$class = (string) $this->element['class'];
		}

		if ($this->element['type_field'])
		{
			$typeField = (string) $this->element['type_field'];
		}

		if ($this->element['class_value'])
		{
			$classValue = (string) $this->element['class_value'];
		}

		if ($this->element['class_percent'])
		{
			$classPercent = (string) $this->element['class_percent'];
		}

		if ($this->element['class_lastpercent'])
		{
			$classLastPercent = (string) $this->element['class_lastpercent'];
		}

		$type = $this->item->$typeField;

		// Start the HTML output
		$extraClass = ($type == 'value') ? $classValue : $classPercent;
		$extraClass .= ($type == 'lastpercent') ? ' ' . $classLastPercent : '';

		$html = '<span class="' . $class . ' ' . $extraClass . '">';

		// Case 1: Value discount
		if ($type == 'value')
		{
			if (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before')
			{
				$html .= AkeebasubsHelperCparams::getParam('currencysymbol','€');
			}

			$html .= ' ' . sprintf('%02.02f', (float)$this->value) . ' ';

			if (AkeebasubsHelperCparams::getParam('currencypos','before') == 'after')
			{
				$html .= AkeebasubsHelperCparams::getParam('currencysymbol','€');
			}
		}
		else
		{
			$html .= sprintf('%2.2f', (float)$this->value) . ' %';
		}

		// End the HTML output
		$html .= '</span>';

		return $html;
	}
}
