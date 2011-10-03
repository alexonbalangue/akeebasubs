<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsSimpleformElementText extends ComAkeebasubsSimpleformElementDefault
{
	public function renderHtml()
	{
		$key = $this->_name;
		$id = 'simpleform_'.strtolower($key);
		$attr = $this->getAttributesAsHTML();

		if(!empty($this->_label)) {
			$label = '<label for="'.$id.'" class="main">' .
				JText::_($this->_label) . '</label>'."\n";
		} else {
			$label = '';
		}
		
		$element = '<input type="text" name="'.$key.'" id="'.$id.'" value="'.htmlspecialchars($this->_value).'" '.$attr.' />'."\n<br/>\n";
		
		return $label.$element;
	}
}