<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * ReCAPTCHA integration
 */
class plgAkeebasubsRecaptcha extends JPlugin
{
	function onSubscriptionFormRender($userparams, $cache)
	{
		$showReCAPTCHA = true;
		if(!array_key_exists('subscriptionlevel', $cache)) $cache['subscriptionlevel'] = null;
		if(is_null($cache['subscriptionlevel'])) {
			$showReCAPTCHA = false;
		}
		if($showReCAPTCHA) {
			if(version_compare(JVERSION, '3.0', 'ge')) {
				$levels = $this->params->get('autoauthids', array());
			} else {
				$levels = $this->params->getValue('autoauthids', array());
			}
			if(!is_null($levels) && !empty($levels)) {
				if(!in_array($cache['subscriptionlevel'], $levels)) {
					if(!in_array(0, $levels)) {
						$showReCAPTCHA = false;
					}
				}
			}
		}
		
		if(!$showReCAPTCHA) {
			// When we're not showing the CAPTCHA pretend it's always valid
			$session = JFactory::getSession();
			$session->set('recaptcha.valid', true, 'com_akeebasubs');
			return;
		}
		
		// Load the library
		if(!defined('RECAPTCHA_API_SERVER')) {
			@include_once dirname(__FILE__).'/recaptcha/recaptchalib.php';
		}
		
		// Make sure the ReCAPTCHA library is loaded
		if(!defined('RECAPTCHA_API_SERVER')) {
			return array();
		}
		
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_recaptcha', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_recaptcha', JPATH_ADMINISTRATOR, null, true);
	
		// Init the fields array which will be returned
		$fields = array();
		
		$session = JFactory::getSession();
		$isValid = $session->get('recaptcha.valid', false, 'com_akeebasubs');
		if($isValid) {
			// The user has already solved the CAPTCHA; don't show him a new CAPTCHA
			return $fields;
		}
		
		// ----- RECAPTCHA FIELD -----
		$uri = new JURI();
		
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$theme = $this->params->get('theme','red');
			$language = $this->params->get('language','en');
		} else {
			$theme = $this->params->getValue('theme','red');
			$language = $this->params->getValue('language','en');
		}
		$html = <<<ENDSCRIPT
<script type="text/javascript">
var RecaptchaOptions = {
	lang : '$language',
	theme : '$theme'
};
</script>

ENDSCRIPT;
		
		$useSSL = strtolower($uri->getScheme()) == 'https';
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$publickey = $this->params->get('publickey','');
		} else {
			$publickey = $this->params->getValue('publickey','');
		}
		$html .= '<div style="display: inline-block"><div style="float: left">'.recaptcha_get_html($publickey, null, $useSSL).'</div></div><div style="clear: both"></div>';
		
		// Setup the field
		$field = array(
			'id'			=> 'recaptcha',
			'label'			=> '* '.JText::_('PLG_AKEEBASUBS_RECAPTCHA_FIELD_LABEL'),
			'elementHTML'	=> $html,
			//'invalidLabel'	=> JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED'),
			'isValid'		=> false
		);
		// Add the field to the return output
		$fields[] = $field;
		
		// ----- ADD THE JAVASCRIPT -----
		$javascript = <<<ENDJS
(function($) {
	$(document).ready(function(){
		// Tell Akeeba Subscriptions how to fetch the extra field data
		addToValidationFetchQueue(plg_akeebasubs_recaptcha_fetch);
		// Tell Akeeba Subscriptions how to validate the extra field data
		addToValidationQueue(plg_akeebasubs_recaptcha_validate);
	});
})(akeeba.jQuery);

function plg_akeebasubs_recaptcha_fetch()
{
	var result = {};
	
	(function($) {
		result.recaptcha_challenge = $('#recaptcha_challenge_field').val();
		result.recaptcha_response = $('#recaptcha_response_field').val();
	})(akeeba.jQuery);
	
	return result;
}

function plg_akeebasubs_recaptcha_validate(response)
{
	(function($) {
		if(response.custom_validation.recaptcha) {
			$('#recaptcha_invalid').css('display','none');
			return true;
		} else {
			$('#recaptcha_invalid').css('display','inline-block');
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
		$ret = array(
			'isValid'			=> true,
			'valid'				=> true,
			'custom_validation'	=> array(
				'recaptcha'	=> true
			)
		);
		
		// Load the library
		if(!defined('RECAPTCHA_API_SERVER')) {
			@include_once dirname(__FILE__).'/recaptcha/recaptchalib.php';
		}
		
		// Make sure the ReCAPTCHA library is loaded
		if(!defined('RECAPTCHA_API_SERVER')) {
			return $ret;
		}
		
		$custom = $data->custom;
		
		$challenge = JRequest::getVar('recaptcha_challenge_field','');
		$response = JRequest::getVar('recaptcha_response_field','');
		if( (!array_key_exists('recaptcha_challenge',$custom)) || (!empty($challenge)) ) $custom['recaptcha_challenge'] = $challenge;
		if( (!array_key_exists('recaptcha_response',$custom)) || (!empty($response)) ) $custom['recaptcha_response'] = $response;
		
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$privkey = $this->params->get('privatekey','');
		} else {
			$privkey = $this->params->getValue('privatekey','');
		}
		$remoteip =  $_SERVER["REMOTE_ADDR"];
		$challenge = $custom['recaptcha_challenge'];
		$response = $custom['recaptcha_response'];
		
		$session = JFactory::getSession();
		$isValid = $session->get('recaptcha.valid', false, 'com_akeebasubs');
		if($isValid) {
			return $ret;
		}
		
		$resp = recaptcha_check_answer($privkey, $remoteip, $challenge, $response);

		$isValid = ($resp->is_valid) ? true : false;
		$session->set('recaptcha.valid', $isValid, 'com_akeebasubs');
		
		$ret['custom_validation']['recaptcha'] = $isValid;
		$ret['valid'] = $ret['custom_validation']['recaptcha']; 
		
		return $ret;
	}
	
	public function onAKSubscriptionChange($row, $info)	
	{
		// reset the CAPTCHA result once a successful subscription is made
		$session = JFactory::getSession();
		$session->set('recaptcha.valid', null, 'com_akeebasubs');
	}
}