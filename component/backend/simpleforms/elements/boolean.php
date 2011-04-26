<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsSimpleformElementBoolean extends ComAkeebasubsSimpleformElementDefault
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
		
		$element = '<select name="'.$key.'" id="'.$id.'">';
		$element .= '<option value="0" '.($this->_value == 0 ? 'selected = "selected" ' : '').'>'.JText::_('No').'</option>';
		$element .= '<option value="1" '.($this->_value != 0 ? 'selected = "selected" ' : '').'>'.JText::_('Yes').'</option>';
		$element .= '</select>';
		
		return $label.$element;
	}
}