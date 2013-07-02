<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Renders the price of a subscription level and its optional sign-up fee
 */
class FOFFormFieldAkeebasubslevelsprice extends FOFFormFieldText
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
		$class             = $this->id;

		// Get field parameters
		if ($this->element['class'])
		{
			$class = (string) $this->element['class'];
		}

		// Start the HTML output
		$html = '<span class="' . $class . '">';

		// First line: regular price
		if (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before')
		{
			$html .= AkeebasubsHelperCparams::getParam('currencysymbol','€');
		}

		$html .= ' ' . sprintf('%02.02f', (float)$this->value) . ' ';

		if (AkeebasubsHelperCparams::getParam('currencypos','before') == 'after')
		{
			$html .= AkeebasubsHelperCparams::getParam('currencysymbol','€');
		}

		// Second line: sign-up fee
		if (property_exists($this->item, 'signupfee') && ($this->item->signupfee >= 0.01))
		{
			$html .= '<br /><span class="small">( ';
			$html .= JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SIGNUPFEE_LIST');

			if (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before')
			{
				$html .= ' ' . AkeebasubsHelperCparams::getParam('currencysymbol','€');
			}

			$html .= sprintf('%02.02f', (float)$this->item->signupfee);

			if (AkeebasubsHelperCparams::getParam('currencypos','before') == 'after')
			{
				$html .= AkeebasubsHelperCparams::getParam('currencysymbol','€');
			}

			$html .= ' )</span>';
		}

		// End the HTML output
		$html .= '</span>';

		return $html;
	}
}
