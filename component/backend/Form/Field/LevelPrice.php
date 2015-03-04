<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use FOF30\Form\Field\Text;
use JText;

defined('_JEXEC') or die;

class LevelPrice extends Text
{
	public function getRepeatable()
	{
		// Initialise
		$class             = $this->id;

		// Get field parameters
		if ($this->element['class'])
		{
			$class = (string) $this->element['class'];
		}

		// Start the HTML output
		$html = '<span class="' . $class . '">';

		// First line: regular price
		if (ComponentParams::getParam('currencypos','before') == 'before')
		{
			$html .= ComponentParams::getParam('currencysymbol','€');
		}

		$html .= ' ' . sprintf('%02.02f', (float)$this->value) . ' ';

		if (ComponentParams::getParam('currencypos','before') == 'after')
		{
			$html .= ComponentParams::getParam('currencysymbol','€');
		}

		// Second line: sign-up fee
		if (property_exists($this->item, 'signupfee') && ($this->item->signupfee >= 0.01))
		{
			$html .= '<br /><span class="small">( ';
			$html .= JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SIGNUPFEE_LIST');

			if (ComponentParams::getParam('currencypos','before') == 'before')
			{
				$html .= ' ' . ComponentParams::getParam('currencysymbol','€');
			}

			$html .= sprintf('%02.02f', (float)$this->item->signupfee);

			if (ComponentParams::getParam('currencypos','before') == 'after')
			{
				$html .= ComponentParams::getParam('currencysymbol','€');
			}

			$html .= ' )</span>';
		}

		// End the HTML output
		$html .= '</span>';

		return $html;
	}
}