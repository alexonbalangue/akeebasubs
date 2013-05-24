<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

require_once __DIR__.'/abstract.php';

/**
 * A dropdown (selection list) field with price modifier options
 *
 * @author Nicholas K. Dionysopoulos
 * @since 2.6.0
 */
class AkeebasubsCustomFieldPricedropdown extends AkeebasubsCustomFieldAbstract
{
	public function __construct(array $config = array()) {
		parent::__construct($config);

		$this->input_type = 'pricedropdown';
	}

	public function getPerSubscriptionField($item, $cache)
	{
		// Get the current value
		if(array_key_exists($item->slug, $cache['subcustom'])) {
			$current = $cache['subcustom'][$item->slug];
		} else {
			$current = $item->default;
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
				$price = 0;
				$daysmod = 0;
				$label = trim($data[1]);

				// Break down value, price and subscription length components
				$pieces = explode('|', $label);
				$label = $pieces[0];

				if (isset($pieces[1]))
				{
					$price = (float)($pieces[1]);
				}

				if (isset($pieces[2]))
				{
					$daysmod = (int)($pieces[2]);
				}

				if(abs($price) >= 0.01)
				{
					$sign = $price > 0 ? '+' : '-';
					$addon = $sign . sprintf('%.2f', abs($price));
					$label .= ' (' . $addon . ')';
				}

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

		// Set up field's HTML content
		$html = "<select name=\"subcustom[{$item->slug}]\" id=\"{$item->slug}\">\n";

		foreach($options as $o)
		{
			$value = $o['value'];
			$label = JText::_($o['label']);
			$html .= "\t<option value=\"$value\"";
			if($current == $value) {
				$html .= "selected=\"selected\"";
			}
			$html .= ">$label</option>\n";
		}

		$html .= "</select>\n";

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

	/**
	 * Create the necessary Javascript for a textbox
	 * @param	AkeebasubsTableCustomfield	$item	The item to render the Javascript for
	 */
	public function getJavascript($item)
	{
		$slug = $item->slug;
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		addToSubValidationFetchQueue(plg_akeebasubs_subcustomfields_fetch_$slug);
ENDJS;
		if(!$item->allow_empty) $javascript .= <<<ENDJS

		addToSubValidationQueue(plg_akeebasubs_subcustomfields_validate_$slug);
ENDJS;
		$javascript .= <<<ENDJS
	});
})(akeeba.jQuery);

function plg_akeebasubs_subcustomfields_fetch_$slug()
{
	var result = {};
	(function($) {
		result.$slug = $('#$slug').val();
	})(akeeba.jQuery);
	return result;
}

ENDJS;

		if(!$item->allow_empty):
			$success_javascript = '';
			$failure_javascript = '';
			if(!empty($item->invalid_label)) {
				$success_javascript .= "$('#{$slug}_invalid').css('display','none');\n";
				$failure_javascript .= "$('#{$slug}_invalid').css('display','inline-block');\n";
			}
			if(!empty($item->valid_label)) {
				$success_javascript .= "$('#{$slug}_valid').css('display','inline-block');\n";
				$failure_javascript .= "$('#{$slug}_valid').css('display','none');\n";
			}
			$javascript .= <<<ENDJS

function plg_akeebasubs_subcustomfields_validate_$slug(response)
{
	var thisIsValid = true;
	(function($) {
		$('#$slug').parent().parent().removeClass('error').removeClass('success');
		$('#{$slug}_invalid').css('display','none');
		$('#{$slug}_valid').css('display','none');
		if (!akeebasubs_apply_validation)
		{
			return true;
		}

		if(response.subcustom_validation.$slug) {
			$('#$slug').parent().parent().addClass('success');
			$success_javascript
		} else {
			$('#$slug').parent().parent().addClass('error');
			$failure_javascript
			thisIsValid = false;
		}
		return thisIsValid;
	})(akeeba.jQuery);
}

ENDJS;
		endif;

		$document = JFactory::getDocument();
		$document->addScriptDeclaration($javascript);
	}

	/**
	 * Validate a text field
	 * @param AkeebasubsTableCustomfield	$item	The custom field to validate
	 * @param array							$custom	The custom fields' values array
	 * @return int 1 if the field is valid, 0 otherwise
	 */
	public function validatePerSubscription($item, $custom)
	{
		if(!array_key_exists($item->slug, $custom)) $custom[$item->slug] = '';
		$valid = true;
		if(!$item->allow_empty) {
			$valid = !empty($custom[$item->slug]);
		}
		return $valid ? 1 : 0;
	}

	public function validatePrice($item, $data)
	{
		$cache = (array)$data;

		// Get the current value
		if(array_key_exists($item->slug, $cache['subcustom'])) {
			$current = $cache['subcustom'][$item->slug];
		} else {
			$current = $item->default;
		}

		// Is this a required field?
		$required = $item->allow_empty ? '' : '* ';

		// Parse options
		$options = array();
		if($item->options) {
			$options_raw = explode("\n", $item->options);
			$options = array();
			foreach($options_raw as $optionLine) {
				$optionLine = trim($optionLine, " \r\t");
				if(empty($optionLine)) continue;
				if(!strstr($optionLine, '=')) continue;
				$data = explode('=', $optionLine, 2);
				if(count($data) < 2) continue;
				$value = trim($data[0]);
				$price = 0;
				$daysmod = 0;
				$label = trim($data[1]);

				// Break down value, price and subscription length components
				$pieces = explode('|', $label);
				$label = $pieces[0];

				if (isset($pieces[1]))
				{
					$price = (float)($pieces[1]);
				}

				if (isset($pieces[2]))
				{
					$daysmod = (int)($pieces[2]);
				}

				if(abs($price) < 0.01)
				{
					$price = 0;
				}

				if(array_key_exists($value, $options)) continue;

				$options[$value] = $price;
			}
		} else {
			return null;
		}

		if(empty($options)) return null;

		$result = 0;

		foreach($options as $value => $price)
		{
			if($current == $value)
			{
				$result = $price;
				break;
			}
		}

		return $result;
	}

	public function validateLength($item, $data)
	{
		$cache = (array)$data;

		// Get the current value
		if(array_key_exists($item->slug, $cache['subcustom'])) {
			$current = $cache['subcustom'][$item->slug];
		} else {
			$current = $item->default;
		}

		// Is this a required field?
		$required = $item->allow_empty ? '' : '* ';

		// Parse options
		$options = array();
		if($item->options) {
			$options_raw = explode("\n", $item->options);
			$options = array();
			foreach($options_raw as $optionLine) {
				$optionLine = trim($optionLine, " \r\t");
				if(empty($optionLine)) continue;
				if(!strstr($optionLine, '=')) continue;
				$data = explode('=', $optionLine, 2);
				if(count($data) < 2) continue;
				$value = trim($data[0]);
				$price = 0;
				$daysmod = 0;
				$label = trim($data[1]);

				// Break down value, price and subscription length components
				$pieces = explode('|', $label);
				$label = $pieces[0];

				if (isset($pieces[1]))
				{
					$price = (float)($pieces[1]);
				}

				if (isset($pieces[2]))
				{
					$daysmod = (int)($pieces[2]);
				}

				if(array_key_exists($value, $options)) continue;

				$options[$value] = $daysmod;
			}
		} else {
			return null;
		}

		if(empty($options)) return null;

		$result = 0;

		foreach($options as $value => $daysmod)
		{
			if($current == $value)
			{
				$result = $daysmod;
				break;
			}
		}

		return $result;
	}
}