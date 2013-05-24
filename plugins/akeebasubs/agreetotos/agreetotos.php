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
class plgAkeebasubsAgreetotos extends JPlugin
{
	function onSubscriptionFormRender($userparams, $cache)
	{
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_agreetotos', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_agreetotos', JPATH_ADMINISTRATOR, null, true);

		if(version_compare(JVERSION, '3.0', 'ge')) {
			$cachechoice = $this->params->get('cachechoice', 0);
		} else {
			$cachechoice = $this->params->getValue('cachechoice', 0);
		}

		// Init the fields array which will be returned
		$fields = array();

		// ----- AGREE TO TOS FIELD -----
		// Get the current setting (or 0 if none)
		if(array_key_exists('agreetotos', $cache['custom'])) {
			if($cachechoice) {
				$current = $cache['custom']['agreetotos'];
			} else {
				$current = '';
			}
		} else {
			if(!is_object($userparams->params)) {
				$current = '';
			} else {
				if($cachechoice) {
					$current = property_exists($userparams->params, 'agreetotos') ? $userparams->params->agreetotos : 0;
				} else {
					$current = '';
				}
			}
		}
		// Setup the combobox parameters
		$options = array(
			JHTML::_('select.option',  0, JText::_('JNO') ),
			JHTML::_('select.option',  1, JText::_('JYES') ),
		);
		$html = JHTML::_('select.genericlist', $options, 'custom[agreetotos]', array(), 'value', 'text', $current, 'agreetotos');

		// Setup the field
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$url = $this->params->get('tosurl','');
		} else {
			$url = $this->params->getValue('tosurl','');
		}
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
			'invalidLabel'	=> JText::_('PLG_AKEEBASUBS_AGREETOTOS_ERR_REQUIRED'),
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
		// Immediate validation of the field
		if (akeebasubs_apply_validation)
		{
			$('#agreetotos').change(function(e){
				if($('#agreetotos').val() == 1) {
					$('#agreetotos_invalid').css('display','none');
				} else {
					$('#agreetotos_invalid').css('display','inline-block');
				}
			});
		}
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
		$('#agreetotos').parent().parent().removeClass('error').removeClass('success');
		$('#agreetotos_invalid').css('display','none');

		if (!akeebasubs_apply_validation)
		{
			return true;
		}

		if(response.custom_validation.agreetotos) {
			$('#agreetotos').parent().parent().addClass('success');
			$('#agreetotos_invalid').css('display','none');
			return true;
		} else {
			$('#agreetotos').parent().parent().addClass('error');
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