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
class plgAkeebasubsAgreetotos extends JPlugin
{
	function onSubscriptionFormRender($userparams, $cache)
	{
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_agreetotos', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_agreetotos', JPATH_ADMINISTRATOR, null, true);
	
		// Init the fields array which will be returned
		$fields = array();
		
		// ----- AGREE TO TOS FIELD -----
		// Get the current setting (or 0 if none)
		if(array_key_exists('agreetotos', $cache['custom'])) {
			$current = $cache['custom']['agreetotos'];
		} else {
			if(!is_object($userparams->params)) {
				$current = '';
			} else {
				$current = property_exists($userparams->params, 'agreetotos') ? $userparams->params->agreetotos : 0;
			}
		}
		// Setup the combobox parameters
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$no = 'JNO';
			$yes = 'JYES';
		} else {
			$no = 'NO';
			$yes = 'YES';
		}
		$options = array(
			JHTML::_('select.option',  0, JText::_($no) ),
			JHTML::_('select.option',  1, JText::_($yes) ),
		);
		$html = JHTML::_('select.genericlist', $options, 'custom[agreetotos]', array(), 'value', 'text', $current, 'agreetotos');

		// Setup the field
		$url = $this->params->getValue('tosurl','');
		if(empty($url)) {
			$urlField = JText::_('PLG_AKEEBASUBS_AGREETOTOS_TOS_LABEL');
		} else {
			$text = JText::_('PLG_AKEEBASUBS_AGREETOTOS_TOS_LABEL');
			$urlField = '<a href="javascript:return false;" onclick="window.open(\''.$url.'\',\'toswindow\',\'width=640,height=480,resizable=yes,scrollbars=yes,toolbar=no,location=no,directories=no,status=no,menubar=no\');">'.$text.'</a>';
		}
		$field = array(
			'id'			=> 'agreetotos',
			'label'			=> '* '.JText::sprintf('PLG_AKEEBASUBS_AGREETOTOS_AGREE_LABEL', $urlField),
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
		addToValidationFetchQueue(plg_akeebasubs_agreetotos_fetch);
		// Tell Akeeba Subscriptions how to validate the extra field data
		addToValidationQueue(plg_akeebasubs_agreetotos_validate);
	});
})(akeeba.jQuery);

function plg_akeebasubs_agreetotos_fetch()
{
	var result = {};
	
	(function($) {
		result.agreetotos = $('#agreetotos').val();
	})(akeeba.jQuery);
	
	return result;
}

function plg_akeebasubs_agreetotos_validate(response)
{
	(function($) {
		if(response.custom_validation.agreetotos) {
			$('#agreetotos_invalid').css('display','none');
			return true;
		} else {
			$('#agreetotos_invalid').css('display','inline-block');
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
		
		if(!array_key_exists('agreetotos',$custom)) $custom['agreetotos'] = 0;
		
		$response['custom_validation']['agreetotos'] = $custom['agreetotos'] != 0;
		$response['valid'] = $response['custom_validation']['agreetotos']; 
		
		return $response;
	}
}