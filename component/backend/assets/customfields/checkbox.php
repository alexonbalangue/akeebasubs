<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

require_once __DIR__.'/abstract.php';

/**
 * A single checkbox field
 *
 * @author Nicholas K. Dionysopoulos
 * @since 2.6.0
 */
class AkeebasubsCustomFieldCheckbox extends AkeebasubsCustomFieldAbstract
{
	public function getField($item, $cache, $userparams)
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

	public function getJavascript($item)
	{
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
		$('#{$slug}_invalid').css('display','none');
		$('#{$slug}_valid').css('display','none');
		if (!akeebasubs_apply_validation)
		{
			return true;
		}

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

	public function validate($item, $custom)
	{
		if(!array_key_exists($item->slug, $custom)) $custom[$item->slug] = 0;
		$valid = true;
		if(!$item->allow_empty) {
			$valid = $custom[$item->slug];
		}
		return $valid ? 1 : 0;
	}
}