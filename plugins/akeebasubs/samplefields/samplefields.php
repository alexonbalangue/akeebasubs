<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * A sample plugin which creates two extra fields, age group and gender.
 * The former is mandatory, the latter is not
 */
class plgAkeebasubsSamplefields extends JPlugin
{
	function onSubscriptionFormRender($userparams, $cache)
	{
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_samplefields', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_samplefields', JPATH_ADMINISTRATOR, null, true);
	
		// Init the fields array which will be returned
		$fields = array();
		
		// ----- AGE GROUP FIELD -----
		// Get the current setting (or 0 if none)
		if(array_key_exists('agegroup', $cache['custom'])) {
			$current = $cache['custom']['agegroup'];
		} else {
			$current = property_exists($userparams->params, 'agegroup') ? $userparams->params->agegroup : 0;
		}
		// Setup the combobox parameters
		$helper = KFactory::tmp('admin::com.akeebasubs.template.helper.listbox');
		$config = new KConfig(array(
			'name'		=> 'custom[agegroup]',
			'attribs'	=> array('id' => 'agegroup'),
			'deselect'	=> true,
			'selected'  => $current,
			'options'	=> array(
				$helper->option(array('text' => JText::_('PLG_AKEEBASUBS_SAMPLEFIELDS_AGEGROUP_SELECT'), 'value' => '0')),
				$helper->option(array('text' => '0-17', 'value' => '1')),
				$helper->option(array('text' => '18-24', 'value' => '2')),
				$helper->option(array('text' => '25-34', 'value' => '3')),
				$helper->option(array('text' => '35-44', 'value' => '4')),
				$helper->option(array('text' => '45-54', 'value' => '5')),
				$helper->option(array('text' => '55+', 'value' => '6'))
			)
		));
		// Setup the field
		$field = array(
			'id'			=> 'agegroup',
			'label'			=> '* '.JText::_('PLG_AKEEBASUBS_SAMPLEFIELDS_AGEGROUP_LABEL'),
			'elementHTML'	=> $helper->optionlist($config),
			'invalidLabel'	=> JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED'),
			'isValid'		=> $current != 0
		);
		// Add the field to the return output
		$fields[] = $field;
		
		// ----- GENDER FIELD -----
		// Get the current setting (or 0 if none)
		if(array_key_exists('gender', $cache['custom'])) {
			$current = $cache['custom']['gender'];
		} else {
			$current = property_exists($userparams->params, 'gender') ? $userparams->params->gender : 0;
		}
		// Setup the combobox parameters
		$helper = KFactory::tmp('admin::com.akeebasubs.template.helper.listbox');
		$config = new KConfig(array(
			'name'		=> 'custom[gender]',
			'attribs'	=> array('id' => 'gender'),
			'selected'  => $current,
			'options'	=> array(
				$helper->option(array('text' => JText::_('PLG_AKEEBASUBS_SAMPLEFIELDS_GENDER_SELECT'), 'value' => '0')),
				$helper->option(array('text' => JText::_('PLG_AKEEBASUBS_SAMPLEFIELDS_GENDER_MALE'), 'value' => '1')),
				$helper->option(array('text' => JText::_('PLG_AKEEBASUBS_SAMPLEFIELDS_GENDER_FEMALE'), 'value' => '2'))
			)
		));
		// Setup the field
		$field = array(
			'id'			=> 'gender',
			'label'			=> JText::_('PLG_AKEEBASUBS_SAMPLEFIELDS_GENDER_LABEL'),
			'elementHTML'	=> $helper->optionlist($config)
		);
		// Add the field to the return output
		$fields[] = $field;
		
		// ----- ADD THE JAVASCRIPT -----
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		// Tell Akeeba Subscriptions how to fetch the extra field data
		addToValidationFetchQueue(plg_akeebasubs_samplefields_fetch);
		// Tell Akeeba Subscriptions how to validate the extra field data
		addToValidationQueue(plg_akeebasubs_samplefields_validate);
	});
})(akeeba.jQuery);

function plg_akeebasubs_samplefields_fetch()
{
	var result = {};
	
	(function($) {
		result.agegroup = $('#agegroup').val();
		result.gender = $('#gender').val();
	})(akeeba.jQuery);
	
	return result;
}

function plg_akeebasubs_samplefields_validate(response)
{
	(function($) {
		if(response.custom_validation.agegroup) {
			$('#agegroup_invalid').css('display','none');
			return true;
		} else {
			$('#agegroup_invalid').css('display','inline-block');
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
		
		$response['custom_validation']['agegroup'] = $data->custom['agegroup'] != 0;
		$response['valid'] = $response['custom_validation']['agegroup']; 
		
		return $response;
	}
}