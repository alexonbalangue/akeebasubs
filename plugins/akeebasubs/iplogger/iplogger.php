<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * Logs the IP of the user and displays it in the back-end
 */
class plgAkeebasubsIplogger extends JPlugin
{
	public function onSubscriptionFormRender($userparams, $cache)
	{
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_iplogger', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_iplogger', JPATH_ADMINISTRATOR, null, true);
	
		// Init the fields array which will be returned
		$fields = array();
		
		// The field will only be shown in the back-end
		$app = JFactory::getApplication();
		if($app->isAdmin()) {
			// ----- SIGNUP IP FIELD -----
			// Get the current setting (or 0 if none)
			if(array_key_exists('signupip', $cache['custom'])) {
				$current = $cache['custom']['signupip'];
			} else {
				if(!is_object($userparams->params)) {
					$current = '';
				} else {
					$current = property_exists($userparams->params, 'signupip') ? $userparams->params->signupip : '0.0.0.0';
				}
			}

			$html = '<input type="text" value="'.$current.'" size="15" />';

			// Setup the field
			$field = array(
				'id'			=> 'signupip',
				'label'			=> JText::_('PLG_AKEEBASUBS_IPLOGGER_LABEL'),
				'elementHTML'	=> $html,
				'invalidLabel'	=> JText::_('COM_AKEEBASUBS_LEVEL_ERR_REQUIRED'),
				'isValid'		=> true
			);
			// Add the field to the return output
			$fields[] = $field;
		}
		
		// ----- RETURN THE FIELDS -----
		return $fields;
	}
	
	public function onValidate($data)
	{
		$response = array(
			'isValid'			=> true,
			'custom_validation'	=> array('custom_validation' => array('signupip' => true))
		);
		
		return $response;
	}
	
	public function onAKSignupUserSave($userData)
	{
		$ip = array_key_exists('REMOTE_ADDR', $_SERVER) ? htmlspecialchars($_SERVER['REMOTE_ADDR']) : '0.0.0.0';
		
		return array(
			'params' => array('signupip' => $ip)
		);
	}
}