<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsSubscriptionemails extends JPlugin
{
	/**
	 * Public constructor. Overridden to load the language strings.
	 */
	public function __construct(& $subject, $config = array())
	{
		parent::__construct($subject, $config);
		
		// Load the language files
		$jlang =& JFactory::getLanguage();
		$jlang->load('plg_akeebasubs_subscriptionemails', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akeebasubs_subscriptionemails', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akeebasubs_subscriptionemails', JPATH_ADMINISTRATOR, null, true);
		
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);
	}

	/**
	 * Called when a new subscription is created, either manually or through
	 * the front-end interface
	 */
	public function onAKSubscriptionCreate(KDatabaseRowDefault $row)
	{
		$this->sendEmail($row, true);
	}
	
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange(KDatabaseRowDefault $row)
	{
		$this->sendEmail($row, false);
	}
	
	/**
	 * Sends out the email to the owner of the subscription.
	 * 
	 * @param $row KDatabaseRowDefault The subscription row object
	 * @param $new bool True if it's a new subscription, false if it's an existing subscription being modified
	 */
	private function sendEmail(KDatabaseRowDefault $row, $new = true)
	{
		// Get the site name
		$config = JFactory::getConfig();
		$sitename = $config->getValue('config.sitename');
	
		// Get the user object
		$user = KFactory::get('lib.joomla.user')->getInstance($row->user_id);
		
		// Get the level
		$level = KFactory::tmp('site::com.akeebasubs.model.levels')
			->id($row->akeebasubs_level_id)
			->getItem();
			
		// Get the from/to dates
		jimport('joomla.utilities.date');
		$jFrom = new JDate($row->publish_up);
		$jTo = new JDate($row->publish_down);
		
		// Get the "my subscriptions" URL
		$baseURL = JURI::base();
		$baseURL = str_replace('/administrator', '', $baseURL);
		$url = str_replace('&amp;','&', $baseURL.JRoute::_('index.php?option=com_akeebasubs&view=subscriptions'));
		
		if($new) {
			$subject_key = 'PLG_AKEEBASUBS_SUBSCRIPTIONEMAILS_NEWHEADER';
			$body_key = 'PLG_AKEEBASUBS_SUBSCRIPTIONEMAILS_NEWBODY';
		} else {
			$subject_key = 'PLG_AKEEBASUBS_SUBSCRIPTIONEMAILS_MODHEADER';
			$body_key = 'PLG_AKEEBASUBS_SUBSCRIPTIONEMAILS_MODBODY';
		}
		
		$subject = JText::sprintf($subject_key, $sitename);
		$body = JText::sprintf($body_key,
			$user->name,
			$sitename,
			$user->username,
			$level->title,
			$row->enabled ? JText::_('Enabled') : JText::_('Disabled'),
			JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$row->state),
			$jFrom->toFormat(JText::_('DATE_FORMAT_LC2')),
			$jTo->toFormat(JText::_('DATE_FORMAT_LC2')),
			$url,
			$sitename
		);
		
		// DEBUG ---
		/* *
		echo "<p><strong>From</strong>: ".$config->getvalue('config.fromname')." &lt;".$config->getvalue('config.mailfrom')."&gt;<br/><strong>To: </strong>".$user->email."</p><hr/><p>$subject</p><hr/><p>".nl2br($body)."</p>"; die();
		/* */
		// -- DEBUG
		
		// Send the email
		$mailer = JFactory::getMailer();
		$mailer->setSender(array( $config->getvalue('config.mailfrom'), $config->getvalue('config.fromname') ));
		$mailer->addRecipient($user->email);
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->Send();	
	}
}