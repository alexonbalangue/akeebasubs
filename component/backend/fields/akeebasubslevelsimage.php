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
class F0FFormFieldAkeebasubslevelsimage extends F0FFormFieldText
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
		include_once F0FTemplateUtils::parsePath('admin://components/com_akeebasubs/helpers/image.php', true);

		// Initialise
		$class             = $this->id;

		// Get field parameters
		if ($this->element['class'])
		{
			$class = (string) $this->element['class'];
		}

		// Start the HTML output
		$html = '<span class="' . $class . '">';

		$html .= '<img src="' .
				AkeebasubsHelperImage::getURL($this->value) .
				'" width="32" height="32" class="sublevelpic" />';

		// End the HTML output
		$html .= '</span>';

		return $html;
	}
}
