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
class FOFFormFieldAkeebasubsaffiliatesowed extends FOFFormFieldText
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

		$outstanding = $this->item->owed - $this->item->paid;

		// Start the HTML output
		$html = '<span class="' . $class . '">';

		if (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before')
		{
			$html .= AkeebasubsHelperCparams::getParam('currencysymbol','€');
		}

		$html .= ' ' . sprintf('%02.02f', (float)$outstanding) . ' ';

		if (AkeebasubsHelperCparams::getParam('currencypos','before') == 'after')
		{
			$html .= AkeebasubsHelperCparams::getParam('currencysymbol','€');
		}

		// End the HTML output
		$html .= '</span>';

		return $html;
	}
}
