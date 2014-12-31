<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2014 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * A plugin which creates two extra fields for conformance to EU directives regarding consumer protection and VAT.
 */
class plgAkeebasubsAgreetoeu extends JPlugin
{
	function onSubscriptionFormPrepaymentRender($userparams, $cache)
	{
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_agreetoeu', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_agreetoeu', JPATH_ADMINISTRATOR, null, true);

		// Init the fields array which will be returned
		$fields = array();

		// ----- CONFIRM BEING INFORMED FIELD -----
		// Setup the combobox parameters
		$options = array(
			JHTML::_('select.option', 0, JText::_('JNO')),
			JHTML::_('select.option', 1, JText::_('JYES')),
		);
		$html = JHTML::_('select.genericlist', $options, 'custom[confirm_informed]', array(), 'value', 'text', 0, 'confirm_informed');

		// Setup the field
		$field = array(
			'id'           => 'confirm_informed',
			'label'        => '* ' . JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_INFORMED_LABEL'),
			'elementHTML'  => $html . '<br/><small style="text-align: justify; display: block; line-height: 120%; margin: 3pt 0;">' . JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_INFORMED_DESC') . '</small>',
			'invalidLabel' => JText::_('PLG_AKEEBASUBS_AGREETOEU_ERR_REQUIRED'),
			'isValid'      => false
		);
		// Add the field to the return output
		$fields[] = $field;

		// ----- CONFIRM POSTAL ADDRESS FIELD -----
		// Setup the combobox parameters
		$options = array(
			JHTML::_('select.option', 0, JText::_('JNO')),
			JHTML::_('select.option', 1, JText::_('JYES')),
		);
		$html = JHTML::_('select.genericlist', $options, 'custom[confirm_postal]', array(), 'value', 'text', 0, 'confirm_postal');

		// Setup the field
		$field = array(
			'id'           => 'confirm_postal',
			'label'        => '* ' . JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_POSTAL_LABEL'),
			'elementHTML'  => $html . '<br/><small style="text-align: justify; display: block; line-height: 120%; margin: 3pt 0;">' . JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_POSTAL_DESC') . '</small>',
			'invalidLabel' => JText::_('PLG_AKEEBASUBS_AGREETOEU_ERR_REQUIRED'),
			'isValid'      => false
		);
		// Add the field to the return output
		$fields[] = $field;

		// ----- CONFIRM RIGHT TO WITHDRAWAL FIELD -----
		// Setup the combobox parameters
		$options = array(
			JHTML::_('select.option', 0, JText::_('JNO')),
			JHTML::_('select.option', 1, JText::_('JYES')),
		);
		$html = JHTML::_('select.genericlist', $options, 'custom[confirm_withdrawal]', array(), 'value', 'text', 0, 'confirm_withdrawal');

		// Setup the field
		$field = array(
			'id'           => 'confirm_withdrawal',
			'label'        => '* ' . JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_WITHDRAWAL_LABEL'),
			'elementHTML'  => $html . '<br/><small style="text-align: justify; display: block; line-height: 120%; margin: 3pt 0;">' . JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_WITHDRAWAL_DESC') . '</small>',
			'invalidLabel' => JText::_('PLG_AKEEBASUBS_AGREETOEU_ERR_REQUIRED'),
			'isValid'      => false
		);
		// Add the field to the return output
		$fields[] = $field;

		// ----- ADD THE JAVASCRIPT -----
		$javascript = <<<JS

;// This comment is intentionally put here to prevent badly written plugins from causing a Javascript error
// due to missing trailing semicolon and/or newline in their code.
(function($) {
	$(document).ready(function(){
		// Tell Akeeba Subscriptions how to fetch the extra field data
		addToValidationFetchQueue(plg_akeebasubs_agreetoeu_fetch);
		// Tell Akeeba Subscriptions how to validate the extra field data
		addToValidationQueue(plg_akeebasubs_agreetoeu_validate);
		// Immediate validation of the field
		if (akeebasubs_apply_validation)
		{
			$('#confirm_informed').change(function(e){
				if($('#confirm_informed').val() == 1) {
					$('#confirm_informed_invalid').css('display','none');
				} else {
					$('#confirm_informed_invalid').css('display','inline-block');
				}
			});

			$('#confirm_postal').change(function(e){
				if($('#confirm_postal').val() == 1) {
					$('#confirm_postal_invalid').css('display','none');
				} else {
					$('#confirm_postal_invalid').css('display','inline-block');
				}
			});

			$('#confirm_withdrawal').change(function(e){
				if($('#confirm_withdrawal').val() == 1) {
					$('#confirm_withdrawal_invalid').css('display','none');
				} else {
					$('#confirm_withdrawal_invalid').css('display','inline-block');
				}
			});
		}
	});
})(akeeba.jQuery);

function plg_akeebasubs_agreetotos_fetch()
{
	var result = {};

	(function($) {
		result.confirm_informed = $('#confirm_informed').val();
		result.confirm_postal = $('#confirm_postal').val();
		result.confirm_withdrawal = $('#confirm_withdrawal').val();
	})(akeeba.jQuery);

	return result;
}

function plg_akeebasubs_agreetotos_validate(response)
{
    var thisIsValid = true;

	(function($) {
		$('#confirm_informed').parents('div.control-group').removeClass('error has-error success has-success');
		$('#confirm_informed_invalid').css('display','none');
		$('#confirm_postal').parents('div.control-group').removeClass('error has-error success has-success');
		$('#confirm_postal_invalid').css('display','none');
		$('#confirm_withdrawal').parents('div.control-group').removeClass('error has-error success has-success');
		$('#confirm_withdrawal_invalid').css('display','none');

		if (!akeebasubs_apply_validation)
		{
			thisIsValid = true;
			return;
		}

		if (response.custom_validation.confirm_informed) {
			$('#confirm_informed').parents('div.control-group').addClass('success has-success');
			$('#confirm_informed_invalid').css('display','none');
		} else {
			$('#confirm_informed').parents('div.control-group').addClass('error has-error');
			$('#confirm_informed_invalid').css('display','inline-block');
			thisIsValid = false;
		}

		if (response.custom_validation.confirm_postal) {
			$('#confirm_postal').parents('div.control-group').addClass('success has-success');
			$('#confirm_postal_invalid').css('display','none');
		} else {
			$('#confirm_postal').parents('div.control-group').addClass('error has-error');
			$('#confirm_postal_invalid').css('display','inline-block');
			thisIsValid = false;
		}

		if (response.custom_validation.confirm_withdrawal) {
			$('#confirm_withdrawal').parents('div.control-group').addClass('success has-success');
			$('#confirm_withdrawal_invalid').css('display','none');
		} else {
			$('#confirm_withdrawal').parents('div.control-group').addClass('error has-error');
			$('#confirm_withdrawal_invalid').css('display','inline-block');
			thisIsValid = false;
		}
	})(akeeba.jQuery);

	return thisIsValid;
}

JS;
		$document = JFactory::getDocument();
		$document->addScriptDeclaration($javascript);

		// ----- RETURN THE FIELDS -----
		return $fields;
	}

	function onValidate($data)
	{
		$response = array(
			'isValid'           => true,
			'custom_validation' => array()
		);

		$custom = $data->custom;

		if (!array_key_exists('confirm_informed', $custom))
		{
			$custom['confirm_informed'] = 0;
		}

		if (!array_key_exists('confirm_postal', $custom))
		{
			$custom['confirm_postal'] = 0;
		}

		if (!array_key_exists('confirm_withdrawal', $custom))
		{
			$custom['confirm_withdrawal'] = 0;
		}

		$response['custom_validation']['confirm_informed'] = $custom['confirm_informed'] != 0;
		$response['custom_validation']['confirm_postal'] = $custom['confirm_postal'] != 0;
		$response['custom_validation']['confirm_withdrawal'] = $custom['confirm_withdrawal'] != 0;

		// Huh?
		$response['valid'] = $response['custom_validation']['confirm_informed'] && $response['custom_validation']['confirm_postal'] && $response['custom_validation']['confirm_withdrawal'];

		return $response;
	}
}