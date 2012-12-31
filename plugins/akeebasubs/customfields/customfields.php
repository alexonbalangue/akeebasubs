<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * A sample plugin which creates two extra fields, age group and gender.
 * The former is mandatory, the latter is not
 */
class plgAkeebasubsCustomfields extends JPlugin
{
	function onSubscriptionFormRender($userparams, $cache)
	{
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_customfields', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_customfields', JPATH_ADMINISTRATOR, null, true);
	
		// Init the fields array which will be returned
		$fields = array();
		
		// Which subscription level is that?
		if(!array_key_exists('subscriptionlevel', $cache)) $cache['subscriptionlevel'] = null;
		
		// Load field definitions
		$items = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
			->enabled(1)
			->filter_order('ordering')
			->filter_order_Dir('ASC')
			->getItemList(true);
		
		if(empty($items)) return $fields;

		// Loop through the items
		foreach($items as $item) {
			// If it's not something shown in this level, skip it
			if($item->show == 'level') {
				if(is_null($cache['subscriptionlevel'])) continue;
				if($cache['subscriptionlevel'] != $item->akeebasubs_level_id) continue;
			}
			
			// Get the names of the methods to use
			$type = $item->type;
			$makeMethod = '_createField'.ucfirst($type);
			$jsMethod = '_createJavascript'.ucfirst($type);
			
			// Unknown field type? Skip it.
			if(!method_exists($this, $makeMethod)) continue;
			
			// Add the field to the list
			$result = $this->$makeMethod($item, $cache, $userparams);
			
			if(is_null($result) || empty($result)) {
				continue;
			} else {
				$fields[] = $result;
			}
			
			// Add Javascript for the field if necessary
			if(method_exists($this, $jsMethod)) {
				$this->$jsMethod($item);
			}
		}
		
		// ----- RETURN THE FIELDS -----
		return $fields;
	}
	
	public function onValidate($data)
	{
		// Initialise the validation respone
		$response = array(
			'valid'				=> true,
			'isValid'			=> true,
			'custom_validation'	=> array()
		);
		
		// Fetch the custom data
		$custom = $data->custom;
		
		// Load field definitions
		$items = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
			->enabled(1)
			->filter_order('ordering')
			->filter_order_Dir('ASC')
			->getItemList(true);
		
		// If there are no custom fields return true (all valid)
		if(empty($items)) return $response;
		
		// Loop through each custom field
		foreach($items as $item) {
			// Make sure it's supposed to be shown in the particular level
			if($item->show == 'level') {
				if(is_null($data->id)) continue;
				if($data->id != $item->akeebasubs_level_id) continue;
			}
			
			// Make sure there is a validation method for this type of field
			$type = $item->type;
			$validateMethod = '_validateField'.ucfirst($type);
			
			if(!method_exists($this, $validateMethod)) continue;
			
			// Get the validation result and save it in the $response array
			$response['custom_validation'][$item->slug] = $this->$validateMethod($item, $custom);
			if(!$item->allow_empty) {
				$response['isValid'] &= $response['custom_validation'][$item->slug];
			}
		}
		
		// Update the master "valid" reponse. If one of the fields is invalid,
		// the entire plugin's result is invalid (the form should not be submitted)
		$response['valid'] = $response['isValid'];
		
		return $response;
	}

	// =========================================================================
	// ============================== TEXT BOX
	// =========================================================================
	
	/**
	 * Creates a custom field of the "text" type
	 * @param	AkeebasubsTableCustomfield	$item	A custom field definition
	 * @param	array						$cache	The values cache
	 */
	private function _createFieldText($item, $cache, $userparams, $type = 'text')
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
		
		// Is this a required field?
		$required = $item->allow_empty ? '' : '* ';

		// Parse options
		if($item->options) {
			$placeholder = htmlentities(str_replace("\n", '', $item->options), ENT_COMPAT, 'UTF-8');
		} else {
			$placeholder = '';
		}
		
		// Set up field's HTML content
		$html = '<input type="'.$type.'" name="custom['.$item->slug.']" id="'.$item->slug.'" value="'.htmlentities($current, ENT_COMPAT, 'UTF-8').'" placeholder="'.$placeholder.'" />';
		
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
	private function _createJavascriptText($item) {
		$slug = $item->slug;
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		addToValidationFetchQueue(plg_akeebasubs_customfields_fetch_$slug);
ENDJS;
		if(!$item->allow_empty) $javascript .= <<<ENDJS

		addToValidationQueue(plg_akeebasubs_customfields_validate_$slug);
ENDJS;
		$javascript .= <<<ENDJS
	});
})(akeeba.jQuery);

function plg_akeebasubs_customfields_fetch_$slug()
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
function plg_akeebasubs_customfields_validate_$slug(response)
{
	var thisIsValid = true;
	(function($) {
		$('#$slug').parent().parent().removeClass('error').removeClass('success');
		if(response.custom_validation.$slug) {
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
	private function _validateFieldText($item, $custom)
	{
		if(!array_key_exists($item->slug, $custom)) $custom[$item->slug] = '';
		$valid = true;
		if(!$item->allow_empty) {
			$valid = !empty($custom[$item->slug]);
		}
		return $valid ? 1 : 0;
	}

	// =========================================================================
	// ============================== PASSWORD BOX
	// =========================================================================	
	
	/**
	 * Creates a custom field of the "password" type
	 * @param	AkeebasubsTableCustomfield	$item	A custom field definition
	 * @param	array						$cache	The values cache
	 */
	private function _createFieldPassword($item, $cache, $userparams)
	{
		return $this->_createFieldText($item, $cache, $userparams, 'password');
	}
	
	/**
	 * Create the necessary Javascript for a password box
	 * @param	AkeebasubsTableCustomfield	$item	The item to render the Javascript for
	 */
	private function _createJavascriptPassword($item) {
		$this->_createJavascriptText($item);	
	}
	
	/**
	 * Validate a password field
	 * @param AkeebasubsTableCustomfield	$item	The custom field to validate
	 * @param array							$custom	The custom fields' values array
	 * @return int 1 if the field is valid, 0 otherwise
	 */
	private function _validateFieldPassword($item, $custom)
	{
		return $this->_validateFieldText($item, $custom);
	}
	
	// =========================================================================
	// ============================== CHECKBOX
	// =========================================================================	

	/**
	 * Creates a custom field of the "checkbox" type
	 * @param	AkeebasubsTableCustomfield	$item	A custom field definition
	 * @param	array						$cache	The values cache
	 */
	private function _createFieldCheckbox($item, $cache, $userparams)
	{
		$default = strtoupper(trim($item->default));
		switch($default) {
			case 'YES':
			case 'TRUE':
			case 'ON':
			case '1':
			case 'ENABLED':
			case 'CHECKED':
			case 'SELECTED':
				$default = 1;
				break;
			
			default:
				$default = 0;
				break;
		}
		
		// Get the current value
		if(array_key_exists($item->slug, $cache['custom'])) {
			$current = $cache['custom'][$item->slug];
		} else {
			if(!is_object($userparams->params)) {
				$current = $default;
			} else {
				$slug = $item->slug;
				$current = property_exists($userparams->params, $item->slug) ? $userparams->params->$slug : $default;
			}
		}
		
		// Is this a required field?
		$required = $item->allow_empty ? '' : '* ';

		// Parse value
		if($current) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}
		
		// Set up field's HTML content
		$html = '<input type="checkbox" name="custom['.$item->slug.']" id="'.$item->slug.'" '.$checked.' />';
		
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
	 * Create the necessary Javascript for a checkbox
	 * @param	AkeebasubsTableCustomfield	$item	The item to render the Javascript for
	 */
	private function _createJavascriptCheckbox($item) {
		$slug = $item->slug;
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		addToValidationFetchQueue(plg_akeebasubs_customfields_fetch_$slug);
ENDJS;
		if(!$item->allow_empty) $javascript .= <<<ENDJS

		addToValidationQueue(plg_akeebasubs_customfields_validate_$slug);
ENDJS;
		$javascript .= <<<ENDJS
	});
})(akeeba.jQuery);

function plg_akeebasubs_customfields_fetch_$slug()
{
	var result = {};
	(function($) {
		result.$slug = $('#$slug').is(':checked') ? 1 : 0;
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
function plg_akeebasubs_customfields_validate_$slug(response)
{
	var thisIsValid = true;
	(function($) {
		$('#$slug').parent().parent().removeClass('error').removeClass('success');
		if(response.custom_validation.$slug) {
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
	 * Validate a check field
	 * @param AkeebasubsTableCustomfield	$item	The custom field to validate
	 * @param array							$custom	The custom fields' values array
	 * @return int 1 if the field is valid, 0 otherwise
	 */
	private function _validateFieldCheckbox($item, $custom)
	{
		if(!array_key_exists($item->slug, $custom)) $custom[$item->slug] = 0;
		$valid = true;
		if(!$item->allow_empty) {
			$valid = $custom[$item->slug];
		}
		return $valid ? 1 : 0;
	}
	
	// =========================================================================
	// ============================== DROP-DOWN (SELECT) LIST
	// =========================================================================	
	
	/**
	 * Creates a custom field of the "dropdown" type
	 * @param	AkeebasubsTableCustomfield	$item	A custom field definition
	 * @param	array						$cache	The values cache
	 */
	private function _createFieldDropdown($item, $cache, $userparams, $type = 'dropdown')
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
		
		if(($type == 'multiselect') && !is_array($current)) {
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
		switch($type) {
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
	
	/**
	 * Create the necessary Javascript for a dropdown
	 * @param	AkeebasubsTableCustomfield	$item	The item to render the Javascript for
	 */
	private function _createJavascriptDropdown($item) {
		$this->_createJavascriptText($item);
	}

	/**
	 * Validate a dropdown field
	 * @param AkeebasubsTableCustomfield	$item	The custom field to validate
	 * @param array							$custom	The custom fields' values array
	 * @return int 1 if the field is valid, 0 otherwise
	 */
	private function _validateFieldDropdown($item, $custom)
	{
		return $this->_validateFieldText($item, $custom);
	}
	
	// =========================================================================
	// ============================== MULTISELECT LIST
	// =========================================================================	
	
	/**
	 * Creates a custom field of the "multiselect" type
	 * @param	AkeebasubsTableCustomfield	$item	A custom field definition
	 * @param	array						$cache	The values cache
	 */
	private function _createFieldMultiselect($item, $cache, $userparams)
	{
		return $this->_createFieldDropdown($item, $cache, $userparams, 'multiselect');
	}
	
	/**
	 * Create the necessary Javascript for a multiselect
	 * @param	AkeebasubsTableCustomfield	$item	The item to render the Javascript for
	 */
	private function _createJavascriptMultiselect($item) {
		$this->_createJavascriptText($item);
	}

	/**
	 * Validate a dropdown multiselect
	 * @param AkeebasubsTableCustomfield	$item	The custom field to validate
	 * @param array							$custom	The custom fields' values array
	 * @return int 1 if the field is valid, 0 otherwise
	 */
	private function _validateFieldMultiselect($item, $custom)
	{
		return $this->_validateFieldText($item, $custom);
	}

	// =========================================================================
	// ============================== MULTISELECT LIST
	// =========================================================================	
	
	/**
	 * Creates a custom field of the "radio" type
	 * @param	AkeebasubsTableCustomfield	$item	A custom field definition
	 * @param	array						$cache	The values cache
	 */
	private function _createFieldRadio($item, $cache, $userparams)
	{
		return $this->_createFieldDropdown($item, $cache, $userparams, 'radio');
	}
	
	/**
	 * Create the necessary Javascript for a multiselect
	 * @param	AkeebasubsTableCustomfield	$item	The item to render the Javascript for
	 */
	private function _createJavascriptRadio($item) {
		$slug = $item->slug;
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		addToValidationFetchQueue(plg_akeebasubs_customfields_fetch_$slug);
ENDJS;
		if(!$item->allow_empty) $javascript .= <<<ENDJS

		addToValidationQueue(plg_akeebasubs_customfields_validate_$slug);
ENDJS;
		$javascript .= <<<ENDJS
	});
})(akeeba.jQuery);

function plg_akeebasubs_customfields_fetch_$slug()
{
	var result = {};
	(function($) {
		result.$slug = $('input:radio[name=custom\\\\[$slug\\\\]]:checked').val();
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
function plg_akeebasubs_customfields_validate_$slug(response)
{
	var thisIsValid = true;
	(function($) {
		$('#$slug').parent().parent().removeClass('error').removeClass('success');
		if(response.custom_validation.$slug) {
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
	 * Validate a dropdown multiselect
	 * @param AkeebasubsTableCustomfield	$item	The custom field to validate
	 * @param array							$custom	The custom fields' values array
	 * @return int 1 if the field is valid, 0 otherwise
	 */
	private function _validateFieldRadio($item, $custom)
	{
		return $this->_validateFieldText($item, $custom);
	}
	
	// =========================================================================
	// ============================== DATE FIELD
	// =========================================================================	
	
	/**
	 * Creates a custom field of the "date" type
	 * @param	AkeebasubsTableCustomfield	$item	A custom field definition
	 * @param	array						$cache	The values cache
	 */
	private function _createFieldDate($item, $cache, $userparams)
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
		
		// Is this a required field?
		$required = $item->allow_empty ? '' : '* ';

		// Set up field's HTML content
		$html = JHTML::_('calendar', $current, 'custom['.$item->slug.']', $item->slug);
		
		// Setup the field
		$field = array(
			'id'			=> $item->slug,
			'label'			=> $required.JText::_($item->title),
			'elementHTML'	=> $html,
			'isValid'		=> $required ? (!empty($current) && $current != '0000-00-00 00:00:00') : true
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
	 * Create the necessary Javascript for a date field
	 * @param	AkeebasubsTableCustomfield	$item	The item to render the Javascript for
	 */
	private function _createJavascriptDate($item) {
		$slug = $item->slug;
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		addToValidationFetchQueue(plg_akeebasubs_customfields_fetch_$slug);
ENDJS;
		if(!$item->allow_empty) $javascript .= <<<ENDJS

		addToValidationQueue(plg_akeebasubs_customfields_validate_$slug);
ENDJS;
		$javascript .= <<<ENDJS
	});
})(akeeba.jQuery);

function plg_akeebasubs_customfields_fetch_$slug()
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
function plg_akeebasubs_customfields_validate_$slug(response)
{
	var thisIsValid = true;
	(function($) {
		$('#$slug').parent().parent().removeClass('error').removeClass('success');
		if(response.custom_validation.$slug) {
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
	 * Validate a date field
	 * @param AkeebasubsTableCustomfield	$item	The custom field to validate
	 * @param array							$custom	The custom fields' values array
	 * @return int 1 if the field is valid, 0 otherwise
	 */
	private function _validateFieldDate($item, $custom)
	{
		if (!array_key_exists($item->slug, $custom))
		{
			$custom[$item->slug] = '';
		}
		
		$valid = true;
		
		if (!$item->allow_empty)
		{
			$valid = !empty($custom[$item->slug]);
			if ($valid)
			{
				$valid = $custom[$item->slug] != '0000-00-00 00:00:00';
			}
		}

		return $valid ? 1 : 0;
	}
}