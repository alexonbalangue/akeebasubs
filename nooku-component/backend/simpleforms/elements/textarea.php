<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsSimpleformElementTextarea extends ComAkeebasubsSimpleformElementDefault
{
	protected function _initialize(KConfig $options)
	{
		$defaults = array(
			'attributes'	=> array('cols' => 50, 'rows' => 5)
       	);
       	
       	$options->append($defaults);
		
		return parent::_initialize($options);
    }
	
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
		
		$element = '<textarea name="'.$key.'" id="'.$id.'" '.$attr.'>'.htmlspecialchars($this->_value)."</textarea>\n<br/>\n";
		
		return $label.$element;
	}
}