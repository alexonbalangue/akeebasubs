<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2015 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
		$labelText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_INFORMED_LABEL');
		$extraText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_INFORMED_DESC');
		$html = <<<HTML
<label class="checkbox">
	<span class="icon icon-info-sign hasPopover" title="$extraText"></span>
	<input type="checkbox" name="custom[confirm_informed]" id="confirm_informed" /> $labelText
</label>
HTML;

		// Setup the field
		$field = array(
			'id'           => 'confirm_informed',
			'label'        => '* ',
			'elementHTML'  => $html,
			'invalidLabel' => JText::_('PLG_AKEEBASUBS_AGREETOEU_ERR_REQUIRED'),
			'isValid'      => false
		);
		// Add the field to the return output
		$fields[] = $field;

		// ----- CONFIRM POSTAL ADDRESS FIELD -----
		// Setup the combobox parameters
		$labelText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_POSTAL_LABEL');
		$extraText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_POSTAL_DESC');
		$html = <<<HTML
<label class="checkbox">
	<span class="icon icon-info-sign hasPopover" title="$extraText"></span>
	<input type="checkbox" name="custom[confirm_postal]" id="confirm_postal" /> $labelText
</label>
HTML;

		// Setup the field
		$field = array(
			'id'           => 'confirm_postal',
			'label'        => '* ',
			'elementHTML'  => $html,
			'invalidLabel' => JText::_('PLG_AKEEBASUBS_AGREETOEU_ERR_REQUIRED'),
			'isValid'      => false
		);
		// Add the field to the return output
		$fields[] = $field;

		// ----- CONFIRM RIGHT TO WITHDRAWAL FIELD -----
		// Setup the combobox parameters
		$labelText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_WITHDRAWAL_LABEL');
		$extraText = JText::_('PLG_AKEEBASUBS_AGREETOEU_CONFIRM_WITHDRAWAL_DESC');
		$html = <<<HTML
<label class="checkbox">
	<span class="icon icon-info-sign hasPopover" title="$extraText"></span>
	<input type="checkbox" name="custom[confirm_withdrawal]" id="confirm_withdrawal" /> $labelText
</label>
HTML;

		// Setup the field
		$field = array(
			'id'           => 'confirm_withdrawal',
			'label'        => '* ',
			'elementHTML'  => $html,
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
				if($('#confirm_informed').is(':checked')) {
					$('#confirm_informed_invalid').css('display','none');
				} else {
					$('#confirm_informed_invalid').css('display','inline-block');
				}
			});

			$('#confirm_postal').change(function(e){
				if($('#confirm_postal').is(':checked')) {
					$('#confirm_postal_invalid').css('display','none');
				} else {
					$('#confirm_postal_invalid').css('display','inline-block');
				}
			});

			$('#confirm_withdrawal').change(function(e){
				if($('#confirm_withdrawal').is(':checked')) {
					$('#confirm_withdrawal_invalid').css('display','none');
				} else {
					$('#confirm_withdrawal_invalid').css('display','inline-block');
				}
			});
		}
	});
})(akeeba.jQuery);

function plg_akeebasubs_agreetoeu_fetch()
{
	var result = {};

	(function($) {
		result.confirm_informed = $('#confirm_informed').is(':checked') ? 1 : 0;
		result.confirm_postal = $('#confirm_postal').is(':checked') ? 1 : 0;
		result.confirm_withdrawal = $('#confirm_withdrawal').is(':checked') ? 1 : 0;
	})(akeeba.jQuery);

	return result;
}

function plg_akeebasubs_agreetoeu_validate(response)
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

		if (response.custom_validation.confirm_informed || $('#confirm_informed').is(':checked')) {
			$('#confirm_informed').parents('div.control-group').addClass('success has-success');
			$('#confirm_informed_invalid').css('display','none');
		} else {
			$('#confirm_informed').parents('div.control-group').addClass('error has-error');
			$('#confirm_informed_invalid').css('display','inline-block');
			thisIsValid = false;
		}

		if (response.custom_validation.confirm_postal || $('#confirm_postal').is(':checked')) {
			$('#confirm_postal').parents('div.control-group').addClass('success has-success');
			$('#confirm_postal_invalid').css('display','none');
		} else {
			$('#confirm_postal').parents('div.control-group').addClass('error has-error');
			$('#confirm_postal_invalid').css('display','inline-block');
			thisIsValid = false;
		}

		if (response.custom_validation.confirm_withdrawal || $('#confirm_withdrawal').is(':checked')) {
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

		$custom['confirm_informed'] = ($custom['confirm_informed'] === 'on') ? 1 : 0;
		$custom['confirm_postal'] = ($custom['confirm_postal'] === 'on') ? 1 : 0;
		$custom['confirm_withdrawal'] = ($custom['confirm_withdrawal'] === 'on') ? 1 : 0;

		$response['custom_validation']['confirm_informed'] = $custom['confirm_informed'] != 0;
		$response['custom_validation']['confirm_postal'] = $custom['confirm_postal'] != 0;
		$response['custom_validation']['confirm_withdrawal'] = $custom['confirm_withdrawal'] != 0;

		// Huh?
		$response['valid'] = $response['custom_validation']['confirm_informed'] && $response['custom_validation']['confirm_postal'] && $response['custom_validation']['confirm_withdrawal'];

		return $response;
	}
}