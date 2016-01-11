<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2016 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * Joomla! CAPTCHA integration
 *
 * This supersedes the old reCAPTCHA integration plugin from Akeeba Subscriptions 2.x - 4.x. This new plugin uses the
 * CAPTCHA integration feature of Joomla! 3, letting you use the default configured CAPTCHA method in the subscription
 * form. Joomla! ships with a reCAPTCHA integration plugin but many more are available at
 */
class plgAkeebasubsRecaptcha extends JPlugin
{
	public function onSubscriptionFormRender($userparams, $cache)
	{
		$this->loadLanguage();

		$showCAPTCHA = true;

		if (!array_key_exists('subscriptionlevel', $cache))
		{
			$cache['subscriptionlevel'] = null;
		}

		if (is_null($cache['subscriptionlevel']))
		{
			$showCAPTCHA = false;
		}

		// Make sure this is not a whitelisted subscription level
		if ($showCAPTCHA)
		{
			$levels = $this->params->get('autoauthids', array());

			if (!is_null($levels) && !empty($levels))
			{
				if (!in_array($cache['subscriptionlevel'], $levels))
				{
					if (!in_array(0, $levels))
					{
						$showCAPTCHA = false;
					}
				}
			}
		}

		// Make sure there's a CAPTCHA to show!
		$captchaPlugin = JFactory::getApplication()->getParams()->get('captcha', JFactory::getConfig()->get('captcha'));
		$captcha       = JCaptcha::getInstance($captchaPlugin, array('namespace' => 'akeebasubs'));

		$showCAPTCHA = $showCAPTCHA && !is_null($captcha);

		if (!$showCAPTCHA)
		{
			// When we're not showing the CAPTCHA pretend it's always valid
			$session = JFactory::getSession();
			$session->set('captcha.valid', true, 'com_akeebasubs');

			return array();
		}

		// Init the fields array which will be returned
		$fields = array();

		$session = JFactory::getSession();
		$isValid = $session->get('captcha.valid', false, 'com_akeebasubs');

		if ($isValid)
		{
			// The user has already solved the CAPTCHA; don't show him a new CAPTCHA
			return $fields;
		}

		// Setup the field
		$field = array(
			'id'          => 'captcha',
			'label'       => '* ' . JText::_('PLG_AKEEBASUBS_RECAPTCHA_FIELD_LABEL'),
			'elementHTML' => $captcha->display('captcha', 'captcha') . '<div style="clear: both;"></div>',
			'isValid'     => false
		);

		// Add the field to the return output
		$fields[] = $field;

		// Return the fields definitions;
		return $fields;
	}

	public function onValidate($data)
	{
		$ret = array(
			'isValid'           => true,
			'valid'             => true,
			'custom_validation' => array(
				'captcha' => true
			)
		);

		// CAPTCHA plugin validation must only run when submitting the form. Otherwise many 3PD CAPTCHA plugins will
		// reset their session and never validate the CAPTCHA. Whoops!
		$view = JFactory::getApplication()->input->getCmd('view');
		$view = strtolower($view);
		$view = trim($view);

		if ($view != 'subscribe')
		{
			return $ret;
		}

		$custom = $data->custom;

		$captchaPlugin = JFactory::getApplication()->getParams()->get('captcha', JFactory::getConfig()->get('captcha'));
		$captcha       = JCaptcha::getInstance($captchaPlugin, array('namespace' => 'akeebasubs'));

		$value = JFactory::getApplication()->input->get('captcha', null, 'raw');

		if ((!array_key_exists('captcha_response', $custom)) || (!empty($value)))
		{
			$custom['captcha_response'] = $value;
		}

		$session = JFactory::getSession();
		$isValid = $session->get('captcha.valid', false, 'com_akeebasubs');

		if ($isValid)
		{
			return $ret;
		}

		$isValid = $captcha->checkAnswer($value);

		$session->set('captcha.valid', $isValid, 'com_akeebasubs');

		$ret['custom_validation']['captcha'] = $isValid;
		$ret['valid']                        = $ret['custom_validation']['captcha'];

		return $ret;
	}

	public function onAKSubscriptionChange($row, $info)
	{
		// Reset the CAPTCHA result once a successful subscription is made
		$session = JFactory::getSession();
		$session->set('captcha.valid', null, 'com_akeebasubs');
	}
}
