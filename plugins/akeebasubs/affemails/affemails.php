<?php
/**
 * @package		akeebasubs
 * @subpackage	plugins.akeebasubs.affemails
 * @copyright	Copyright (c)2012 AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsAffemails extends JPlugin
{
	protected $emails = array();

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		// No point running if there is no affiliate
		if(empty($row->akeebasubs_affiliate_id)) return;
		
		// The payment is not complete yet; do not contact the affiliate
		if($row->state != 'C') return;
		
		// Did the payment status just change to C or P? It's a new subscription
		if(array_key_exists('state', (array)$info['modified']) && in_array($row->state, array('P','C'))) {
			if($row->enabled) {
				if(is_object($info['previous']) && ($row->state == 'C') && ($info['previous']->state == 'P')) {
					// A pending subscription just got paid
					$this->sendEmail($row, 'paid');
				} elseif($row->state == 'C') {
					// A new subscription just got paid; send new subscription notification
					$this->sendEmail($row, 'new_active');
				}
			} elseif($row->state == 'C') {
				// A new subscription which is for a renewal (will be active in a future date)
				$this->sendEmail($row, 'new_renewal');
			} else {
				// A new subscription which is pending payment by the processor
				$this->sendEmail($row, 'new_pending');
			}
		} elseif(array_key_exists('state', (array)$info['modified']) && ($row->state == 'X')) {
			// The payment just got refused
			if(!is_object($info['previous']) || $info['previous']->state == 'N') {
				// A new subscription which could not be paid
				$this->sendEmail($row, 'cancelled_new');
			} else {
				// A pending or paid subscription which was cancelled/refunded/whatever
				$this->sendEmail($row, 'cancelled_existing');
			}
		} elseif($info['status'] == 'modified') {
			// If the subscription got disabled and contact_flag is 3, do not send out
			// an expiration notification. The flag is set to 3 only when a user has
			// already renewed his subscription.
			if(array_key_exists('enabled', (array)$info['modified']) && !$row->enabled && ($row->contact_flag == 3)) {
				return;
			} elseif(array_key_exists('enabled', (array)$info['modified']) && !$row->enabled) {
				// Disabled subscription, suppose expired
				if(($row->state == 'C')) $this->sendEmail($row, 'expired');
			} elseif(array_key_exists('enabled', (array)$info['modified']) && $row->enabled) {
				// Subscriptions just enabled, suppose date triggered
				if(($row->state == 'C')) $this->sendEmail($row, 'published');
			} elseif(array_key_exists('contact_flag', (array)$info['modified']) ) {
				// Only contact_flag change; ignore
				return;
			} else {
				// All other cases: generic email
				$this->sendEmail($row, 'generic');
			}
		}
	}
	
	/**
	 * Notifies the component of the supported email keys by this plugin.
	 * 
	 * @return  array
	 * 
	 * @since 3.0
	 */
	public function onAKGetEmailKeys()
	{
		return array(
			'section'		=> $this->_name,
			'title'			=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAILSECTION'),
			'keys'			=> array(
				'paid'					=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAIL_PAID'),
				'new_active'			=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAIL_NEW_ACTIVE'),
				'new_renewal'			=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAIL_NEW_RENEWAL'),
				'new_pending'			=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAIL_NEW_PENDING'),
				'cancelled_new'			=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAIL_CANCELLED_NEW'),
				'cancelled_existing'	=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAIL_CANCELLED_EXISTING'),
				'expired'				=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAIL_EXPIRED'),
				'published'				=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAIL_PUBLISHED'),
				'generic'				=> JText::_('PLG_AKEEBASUBS_AFFEMAILS_EMAIL_GENERIC'),
			)
		);
	}	
	
	/**
	 * Sends out the email to the owner of the subscription.
	 * 
	 * @param $row AkeebasubsTableSubscription The subscription row object
	 * @param $type string The type of the email to send (generic, new,)
	 */
	private function sendEmail($row, $type = '')
	{
		// Get the site name
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$sitename = $config->get('sitename');
		} else {
			$sitename = $config->getValue('config.sitename');
		}
	
		// Get the user object
		$user = JFactory::getUser($row->user_id);
		
		// Get the affiliate object
		$affiliate = FOFModel::getTmpInstance('Affiliates','AkeebasubsModel')
			->setId($row->akeebasubs_affiliate_id)
			->getItem();
		
		// Make sure the affiliate exists
		if($affiliate->akeebasubs_affiliate_id != $row->akeebasubs_affiliate_id) return;
		
		// Get the affiliate user object
		$affiliateUser = JFactory::getUser($affiliate->user_id);
		
		// Load the language files and their overrides
		$jlang = JFactory::getLanguage();
		// -- English (default fallback)
		$jlang->load('plg_akeebasubs_affemails', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akeebasubs_affemails.override', JPATH_ADMINISTRATOR, 'en-GB', true);
		// -- Default site language
		$jlang->load('plg_akeebasubs_affemails', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akeebasubs_affemails.override', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		// -- Current site language
		$jlang->load('plg_akeebasubs_affemails', JPATH_ADMINISTRATOR, null, true);
		$jlang->load('plg_akeebasubs_affemails.override', JPATH_ADMINISTRATOR, null, true);
		// -- Affiliate user's preferred language
		jimport('joomla.registry.registry');
		$uparams = is_object($user->params) ? $user->params : new JRegistry($affiliateUser->params);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$userlang = $uparams->get('language','');
		} else {
			$userlang = $uparams->getValue('language','');
		}
		if(!empty($userlang)) {
			$jlang->load('plg_akeebasubs_affemails', JPATH_ADMINISTRATOR, $affiliateUser, true);
			$jlang->load('plg_akeebasubs_affemails.override', JPATH_ADMINISTRATOR, $affiliateUser, true);
		}
		
		// Get the user's name
		$fullname = $user->name;
		$nameParts = explode(' ',$fullname, 2);
		$firstname = array_shift($nameParts);
		$lastname = !empty($nameParts) ? array_shift($nameParts) : '';
		
		// Get the level
		$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->setId($row->akeebasubs_level_id)
			->getItem();
			
		// Get the from/to dates
		jimport('joomla.utilities.date');
		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
		if(!preg_match($regex, $row->publish_up)) {
			$row->publish_up = '2001-01-01';
		}
		if(!preg_match($regex, $row->publish_down)) {
			$row->publish_down = '2037-01-01';
		}
		$jFrom = new JDate($row->publish_up);
		$jTo = new JDate($row->publish_down);
		
		// Get the "my subscriptions" URL
		$baseURL = JURI::base();
		$baseURL = str_replace('/administrator', '', $baseURL);
		$subpathURL = JURI::base(true);
		$subpathURL = str_replace('/administrator', '', $subpathURL);
		
		if(JFactory::getApplication()->isAdmin()) {
			$url = 'index.php?option=com_akeebasubs&view=subscriptions&layout=default';
		} else {
			$url = str_replace('&amp;','&', JRoute::_('index.php?option=com_akeebasubs&view=subscriptions&layout=default'));
		}
		$url = ltrim($url, '/');
		$subpathURL = ltrim($subpathURL, '/');
		if(substr($url,0,strlen($subpathURL)+1) == "$subpathURL/") $url = substr($url,strlen($subpathURL)+1);
		$url = rtrim($baseURL,'/').'/'.ltrim($url,'/');
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		
		$replacements = array(
			"\\n"			=> "\n",
			'[SITENAME]'	=> $sitename,
			'[FULLNAME]'	=> $fullname,
			'[FIRSTNAME]'	=> $firstname,
			'[LASTNAME]'	=> $lastname,
			'[USERNAME]'	=> $user->username,
			'[USEREMAIL]'	=> $user->email,
			'[LEVEL]'		=> $level->title,
			'[ENABLED]'		=> JText::_('PLG_AKEEBASUBS_SUBSCRIPTIONEMAILS_COMMON_'. ($row->enabled ? 'ENABLED' : 'DISABLED')),
			'[PAYSTATE]'	=> JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$row->state),
			'[PUBLISH_UP]'	=> $jFrom->format(JText::_('DATE_FORMAT_LC2'), true),
			'[PUBLISH_DOWN]' => $jTo->format(JText::_('DATE_FORMAT_LC2'), true),
			'[MYSUBSURL]'	=> $url,
			'[CURRENCY]'	=> AkeebasubsHelperCparams::getParam('currencysymbol','€'),
		);
		
		$subject = JText::_('PLG_AKEEBASUBS_AFFEMAILS_HEAD_'.strtoupper($type));
		$body = JText::_('PLG_AKEEBASUBS_AFFEMAILS_BODY_'.strtoupper($type));
		
		foreach($replacements as $key => $value) {
			$subject = str_replace($key, $value, $subject);
			$body = str_replace($key, $value, $body);
		}
		
		// Process merge tags
		require_once JPATH_SITE.'/components/com_akeebasubs/helpers/message.php';
		$subject = AkeebasubsHelperMessage::processSubscriptionTags($subject, $row);
		$body = AkeebasubsHelperMessage::processSubscriptionTags($body, $row);
		
		// If the subject or the body is empty, skip the email
		if(empty($subject) || empty($body)) return;
		
		// DEBUG ---
		/**
		echo "<p><strong>From</strong>: ".$config->getvalue('config.fromname')." &lt;".$config->getvalue('config.mailfrom')."&gt;<br/><strong>To: </strong>".$affiliateUser->email."</p><hr/><p>$subject</p><hr/><p>".nl2br($body)."</p>"; die();
		/**/
		// -- DEBUG


		// Send the email
		$mailer = JFactory::getMailer();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$mailfrom = $config->get('mailfrom');
			$fromname = $config->get('fromname');
		} else {
			$mailfrom = $config->getValue('config.mailfrom');
			$fromname = $config->getValue('config.fromname');
		}
		$mailer->setSender(array( $mailfrom, $fromname ));
		$mailer->addRecipient($affiliateUser->email);
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$mailer->Send();
	}
}