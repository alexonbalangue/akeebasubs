<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

require_once __DIR__.'/abstract.php';

/**
 * A date input field
 *
 * @author Nicholas K. Dionysopoulos
 * @since 2.6.0
 */
class AkeebasubsCustomFieldDate extends AkeebasubsCustomFieldAbstract
{
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