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
class FOFFormFieldAkeebasubsgroupedlevels extends FOFFormFieldText
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
		static $cache = array();

		$temp = array();

		if(!$cache)
		{
			$cache = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
						->createIdLookup();
		}

		$parts = explode(',', $this->value);

		foreach($parts as $part)
		{
			$temp[] = $cache[$part]->title;
		}

		$html = implode('<br/>', $temp);

		return $html;
	}
}
