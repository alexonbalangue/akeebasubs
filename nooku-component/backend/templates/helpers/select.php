<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */
class ComAkeebasubsTemplateHelperSelect extends KTemplateHelperSelect
{

	/**
	 * Generates an HTML boolean radio list
	 *
	 * @param 	array 	An optional array with configuration options
	 * @return	string	Html
	 */
	public function booleanlist( $config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'name'   	=> '',
			'attribs'	=> array(),
			'true'		=> version_compare(JVERSION, '1.6.0', 'ge') ? 'JYES' : 'yes',
			'false'		=> version_compare(JVERSION, '1.6.0', 'ge') ? 'JNO' : 'no',
			'selected'	=> null,
			'translate'	=> true
		));
		
		$name    = $config->name;
		$attribs = KHelperArray::toString($config->attribs);
		
		$html  = array();
		
		$extra = !$config->selected ? 'checked="checked"' : '';
		$text  = $config->translate ? JText::_( $config->false ) : $config->false;
		
		$html[] = '<label for="'.$name.'0">'.$text.'</label>';
		$html[] = '<input type="radio" name="'.$name.'" id="'.$name.'0" value="0" '.$extra.' '.$attribs.' />';	
		
		$extra = $config->selected ? 'checked="checked"' : '';
		$text  = $config->translate ? JText::_( $config->true ) : $config->true;
		
		$html[] = '<label for="'.$name.'1">'.$text.'</label>';
		$html[] = '<input type="radio" name="'.$name.'" id="'.$name.'1" value="1" '.$extra.' '.$attribs.' />';	
		
		return implode(PHP_EOL, $html);
	}
}