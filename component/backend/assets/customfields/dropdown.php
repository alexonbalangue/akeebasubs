<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

require_once __DIR__.'/abstract.php';
require_once __DIR__.'/text.php';

/**
 * A dropdown (selection list) field
 * 
 * @author Nicholas K. Dionysopoulos
 * @since 2.6.0
 */
class AkeebasubsCustomFieldDropdown extends AkeebasubsCustomFieldText
{
	public function __construct(array $config = array()) {
		parent::__construct($config);
		
		$this->input_type = 'dropdown';
	}
	
	public function getField($item, $cache, $userparams)
	{
		// Get the current value
		if(array_key_exists($item->slug, $cache['custom'])) {
			$current = $cache['custom'][$item->slug];
		} else {
			if(!is_object($userparams->params)) {
				$current = $item->default;
			} else {
				$slug = $item->slug;
				$current = property_exists($userparams->params, $item->slug) ? $userparams->params->$slug : $item->default;
			}
		}
		
		if(($this->input_type == 'multiselect') && !is_array($current)) {
			$current = explode(',', $current);
		}
		
		// Is this a required field?
		$required = $item->allow_empty ? '' : '* ';

		// Parse options
		$options = array();
		if($item->options) {
			$options_raw = explode("\n", $item->options);
			$options = array();
			$values=array();
			foreach($options_raw as $optionLine) {
				$optionLine = trim($optionLine, " \r\t");
				if(empty($optionLine)) continue;
				if(!strstr($optionLine, '=')) continue;
				$data = explode('=', $optionLine, 2);
				if(count($data) < 2) continue;
				$value = trim($data[0]);
				$label = trim($data[1]);
				if(in_array($value, $values)) continue;
				$options[] = array(
					'value'	=> $value,
					'label'	=> $label
				);
				$values[] = $value;
			}
			
			if(!in_array('', $values)) {
				$entry = array(
					'value'	=> '',
					'label'	=> '- '.JText::_('COM_AKEEBASUBS_COMMON_SELECT').' -'
				);
				array_unshift($options, $entry);
			}
		} else {
			return null;
		}
		
		if(empty($options)) return null;
		
		$multiselect = false;
		// Set up field's HTML content
		switch($this->input_type) {
			case 'multiselect':
				$multiselect = true;

			case 'dropdown':
				
				if($multiselect) {
					$html = "<select name=\"custom[{$item->slug}][]\" id=\"{$item->slug}\"";
					$html .= " size=\"5\" multiple=\"multiple\"";
				} else {
					$html = "<select name=\"custom[{$item->slug}]\" id=\"{$item->slug}\"";
				}
				$html .= ">\n";
				
				foreach($options as $o)
				{
					$value = $o['value'];
					$label = JText::_($o['label']);
					$html .= "\t<option value=\"$value\"";
					if(!$multiselect) {
						if($current == $value) {
							$html .= "selected=\"selected\"";
						}
					} else {
						if(in_array($value, $current)) {
							$html .= "selected=\"selected\"";
						}
					}
					$html .= ">$label</option>\n";
				}
				
				$html .= "</select>\n";
				break;

			case 'radio':
				$html = "<label class=\"radio inline\" id=\"{$item->slug}\">\n";
				foreach($options as $o)
				{
					$value = $o['value'];
					$label = JText::_($o['label']);
					$id = $item->slug.'_'.md5($value.$label);
					$checked = '';
					if($current == $value) {
						$checked = "checked=\"checked\"";
					}
					$html .= "\t<input type=\"radio\" name=\"custom[{$item->slug}]\" id=\"$id\" value=\"$value\" $checked />\n";
					$html .= "\t<label for=\"$id\">$label</label>\n";
				}
				$html .= "</label>\n";
				break;
		}
		
		// Setup the field
		$field = array(
			'id'			=> $item->slug,
			'label'			=> $required.JText::_($item->title),
			'elementHTML'	=> $html,
			'isValid'		=> $required ? !empty($current) : true
		);
		
		if($item->invalid_label) {
			$field['invalidLabel'] = JText::_($item->invalid_label);
		}
		if($item->valid_label) {
			$field['validLabel'] = JText::_($item->valid_label);
		}
		
		return $field;
	}
}