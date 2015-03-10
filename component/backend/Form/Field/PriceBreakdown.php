<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use FOF30\Form\Field\Text;
use JText;

defined('_JEXEC') or die;

class PriceBreakdown extends Text
{
	public function getRepeatable()
	{
		/** @var Subscriptions $subscription */
		$subscription = $this->item;

		$html = '';

		if ($subscription->net_amount > 0)
		{
			if ($subscription->discount_amount > 0)
			{
				$html .= $this->formatPrice($subscription->prediscount_amount, 'netamount') . "\n";
				$html .= $this->formatPrice(-1.0 * $subscription->discount_amount, 'discountamount') . "\n";
			}
			else
			{
				$html .= $this->formatPrice($subscription->net_amount, 'netamount') . "\n";
			}

			$html .= $this->formatPrice($subscription->tax_amount, 'taxamount') . "\n";
		}

		$html .= $this->formatPrice($subscription->gross_amount, 'grossamount') . "\n";

		return $html;
	}

	public function getRepeatableRowClass($oldClass)
	{
		/** @var Subscriptions $subscription */
		$subscription = $this->item;

		$rowClass = '';

		if (!$subscription->enabled)
		{
			$rowClass = 'expired';
			$publishDown = new \JDate($subscription->publish_down);
			$expires_timestamp = $publishDown->toUnix();

			if (($subscription->state == 'C') && ($expires_timestamp > time()))
			{
				$rowClass = 'pending-renewal';
			}
		}

		return $oldClass . ' ' . $rowClass;
	}

	/**
	 * Format a value as money
	 *
	 * @param   float   $value  The money value to format
	 * @param   string  $type   Used to create the span class
	 *
	 * @return  string  The HTML of the formatted price
	 */
	protected function formatPrice($value, $type = 'netamount')
	{
		static $currencyPosition = null;
		static $currencySymbol = null;

		if (is_null($currencyPosition))
		{
			$currencyPosition = ComponentParams::getParam('currencypos','before');
			$currencySymbol = ComponentParams::getParam('currencysymbol','€');
		}

		$html = "<span class=\"akeebasubs-subscription-$type\">";

		if ($currencyPosition == 'before')
		{
			$html .= $currencySymbol . ' ';
		}

		$html .= sprintf('%2.2f', (float) $value);

		if ($currencyPosition != 'before')
		{
			$html .= ' ' . $currencySymbol;
		}

		$html .= '</span>';

		return $html;
	}
}