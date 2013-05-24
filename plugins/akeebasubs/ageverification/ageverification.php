<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * A sample plugin which creates two extra fields, age group and gender.
 * The former is mandatory, the latter is not
 */
class plgAkeebasubsAgeverification extends JPlugin
{
	function onSubscriptionFormRender($userparams, $cache)
	{
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_ageverification', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_ageverification', JPATH_ADMINISTRATOR, null, true);

		// Init the fields array which will be returned
		$fields = array();

		// ----- AGREE TO TOS FIELD -----
		// Get the current setting (or 0 if none)
		if(array_key_exists('ageverification', $cache['custom'])) {
			$current = $cache['custom']['ageverification'];
		} else {
			if(!is_object($userparams->params)) {
				$current = '';
			} else {
				$current = property_exists($userparams->params, 'ageverification') ? $userparams->params->ageverification : 0;
			}
		}
		// Setup the combobox parameters
		$options = array(
			JHTML::_('select.option',  0, JText::_('JNO') ),
			JHTML::_('select.option',  1, JText::_('JYES') ),
		);
		$html = JHTML::_('select.genericlist', $options, 'custom[ageverification]', array(), 'value', 'text', $current, 'ageverification');

		// Setup the field
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$minage = $this->params->get('minage','18');
		} else {
			$minage = $this->params->getValue('minage','18');
		}
		$field = array(
			'id'			=> 'ageverification',
			'label'			=> '* '.JText::sprintf('PLG_AKEEBASUBS_AGEVERIFICATION_AGE_LABEL', $minage),
			'elementHTML'	=> $html,
			'invalidLabel'	=> JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED'),
			'isValid'		=> $current != 0
		);
		// Add the field to the return output
		$fields[] = $field;

		// ----- ADD THE JAVASCRIPT -----
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		// Tell Akeeba Subscriptions how to fetch the extra field data
		addToValidationFetchQueue(plg_akeebasubs_ageverification_fetch);
		// Tell Akeeba Subscriptions how to validate the extra field data
		addToValidationQueue(plg_akeebasubs_ageverification_validate);
	});
})(akeeba.jQuery);

function plg_akeebasubs_ageverification_fetch()
{
	var result = {};

	(function($) {
		result.ageverification = $('#ageverification').val();
	})(akeeba.jQuery);

	return result;
}

function plg_akeebasubs_ageverification_validate(response)
{
	(function($) {
		$('#ageverification').parent().parent().removeClass('error').removeClass('success');
		$('#ageverification_invalid').css('display','none');

		if (!akeebasubs_apply_validation)
		{
			return true;
		}

		if(response.custom_validation.ageverification) {
			$('#ageverification').parent().parent().addClass('success');
			$('#ageverification_invalid').css('display','none');
			return true;
		} else {
			$('#ageverification').parent().parent().addClass('error');
			$('#ageverification_invalid').css('display','inline-block');
			return false;
		}
	})(akeeba.jQuery);
}

ENDJS;
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($javascript);

		// ----- RETURN THE FIELDS -----
		return $fields;
	}

	function onValidate($data)
	{
		$response = array(
			'isValid'			=> true,
			'custom_validation'	=> array()
		);

		$custom = $data->custom;

		if(!array_key_exists('ageverification',$custom)) $custom['ageverification'] = 0;

		$response['custom_validation']['ageverification'] = $custom['ageverification'] != 0;
		$response['valid'] = $response['custom_validation']['ageverification'];

		return $response;
	}
}