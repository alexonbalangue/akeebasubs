<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class FOFFormFieldAkeebasubsemailtemplatekey extends FOFFormFieldList
{
	protected function getOptions($repeatable = false)
	{
		static $options = null;
		
		if (is_null($options))
		{
			if (!class_exists('AkeebasubsHelperEmail'))
			{
				require_once JPATH_ROOT.'/components/com_akeebasubs/helpers/email.php';
			}
			
			$mode = $repeatable ? 2 : 1;
			$options = AkeebasubsHelperEmail::getEmailKeys($mode);
		}
		
		reset($options);
		
		return $options;
	}
	
	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 * 
	 * @since 2.0
	 */
	public function getRepeatable() {
		$class = $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';
		
		return '<span id="' . $this->id . '" ' . $class . '>' .
			htmlspecialchars(self::getOptionName($this->getOptions(true), $this->value), ENT_COMPAT, 'UTF-8') .
			'</span>';
	}
}
