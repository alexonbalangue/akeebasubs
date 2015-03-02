<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use FOF30\Form\Field\Text;

defined('_JEXEC') or die;

class Discount extends Text
{
	public function getRepeatable()
	{
		// Initialise
		$class            = $this->id;
		$typeField        = 'type';
		$classValue       = 'akeebasubs-coupon-discount-value';
		$classPercent     = 'akeebasubs-coupon-discount-percent';
		$classLastPercent = 'akeebasubs-coupon-discount-lastpercent';

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
			if (ComponentParams::getParam('currencypos', 'before') == 'before')
			{
				$html .= ComponentParams::getParam('currencysymbol', '€');
			}

			$html .= ' ' . sprintf('%02.02f', (float) $this->value) . ' ';

			if (ComponentParams::getParam('currencypos', 'before') == 'after')
			{
				$html .= ComponentParams::getParam('currencysymbol', '€');
			}
		}
		else
		{
			$html .= sprintf('%2.2f', (float) $this->value) . ' %';
		}

		// End the HTML output
		$html .= '</span>';

		return $html;
	}
}