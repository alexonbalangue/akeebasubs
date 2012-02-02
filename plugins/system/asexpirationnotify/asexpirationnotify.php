<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/fof/include.php';
if(!defined('FOF_INCLUDED')) return;

class plgSystemAsexpirationnotify extends JPlugin
{
	/**
	 * Public constructor. Overridden to load the language strings.
	 */
	public function __construct(& $subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);
		
		// Timezone fix; avoids errors printed out by PHP 5.3.3+ (thanks Yannick!)
		if(function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
			if(function_exists('error_reporting')) {
				$oldLevel = error_reporting(0);
			}
			$serverTimezone = @date_default_timezone_get();
			if(empty($serverTimezone) || !is_string($serverTimezone)) $serverTimezone = 'UTC';
			if(function_exists('error_reporting')) {
				error_reporting($oldLevel);
			}
			@date_default_timezone_set( $serverTimezone);
		}
	}

	/**
	 * Called when Joomla! is booting up and checks the subscriptions statuses.
	 * If a subscription is close to expiration, it sends out an email to the user.
	 */
	public function onAfterInitialise()
	{
		// Check if we need to run
		if(!$this->doIHaveToRun()) return;
		
		// Get today's date
		jimport('joomla.utilities.date');
		$jNow = new JDate();
		$now = $jNow->toUnix();
		
		// Start the clock!
		$clockStart = microtime(true);
		
		// Get and loop all subscription levels
		$levels = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->enabled(1)
			->getList();
		
		foreach($levels as $level)
		{
			// Load the notification thresholds and make sure they are sorted correctly!
			$notify1 = $level->notify1;
			$notify2 = $level->notify2;
			
			if($notify2 > $notify1) {
				$tmp = $notify2;
				$notify2 = $notify1;
				$notify1 = $tmp;
			}
			
			// Make sure we are asked to notify users, at all!
			if( ($notify1 <= 0) && ($notify2 <= 0) ) {
				continue;
			}
			
			// Get the subscriptions expiring within the next $notify1 days for
			// users which we have not contacted yet.
			$jFrom = new JDate($now + 1);
			$jTo = new JDate($now + $notify1 * 24 * 3600);
			
			$subs1 = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->contact_flag(0)
				->level($level->akeebasubs_level_id)
				->enabled(1)
				->expires_from($jFrom->toMySQL())
				->expires_to($jTo->toMySQL())
				->getList();

			// Get the subscriptions expiring within the next $notify2 days for
			// users which we have contacted only once
			$subs2 = array();
			
			if($notify2 > 0) {
				$jFrom = new JDate($now + 1);
				$jTo = new JDate($now + $notify2 * 24 * 3600);

				$subs2 = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->contact_flag(1)
					->level($level->akeebasubs_level_id)
					->enabled(1)
					->expires_from($jFrom->toMySQL())
					->expires_to($jTo->toMySQL())
					->getList();
			}
				
			// If there are no subscriptions, bail out
			if( (count($subs1) + count($subs2)) == 0 ) {
				continue;
			}
			
			// Check is some of those subscriptions have been renewed. If so, set their contactFlag to 2
			$realSubs = array();
			foreach(array($subs1, $subs2) as $subs)
			{
				foreach($subs as $sub) {

					// Get the user and level, load similar subscriptions with start date after this subscription's expiry date
					$renewals = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
						->enabled(1)
						->user_id($sub->user_id)
						->level($sub->akeebasubs_level_id)
						->publish_up($sub->publish_down)
						->getList();
					if(count($renewals)) {
						// The user has already renewed. Don't send him an email; just update the row
						FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
							->setId($sub->akeebasubs_subscription_id)
							->getItem()
							->save(array(
								'contact_flag'	=> 3
							));

						// Timeout check -- Only if we did make a modification!
						$clockNow = microtime(true);
						$elapsed = $clockNow - $clockStart;
						if($elapsed > 2) return;
					} else {
						// No renewals found. Let's nag our user.
						$realSubs[] = $sub;
					}
				}
			}
			
			// If there are no subscriptions, bail out
			if(empty($realSubs)) {
				continue;
			}
			
			// Loop through subscriptions and send out emails, checking for timeout
			$jNow = new JDate();
			$mNow = $jNow->toMySQL();
			foreach($realSubs as $sub) {
				// Is it the first or the second contact?
				if($sub->contact_flag == 0) {
					// First contact
					$data = array(
						'contact_flag'		=> 1,
						'first_contact'		=> $mNow
					);
					$this->sendEmail($sub, true);
				} elseif($sub->contact_flag == 1) {
					// Second and final contact
					$data = array(
						'contact_flag'		=> 2,
						'second_contact'	=> $mNow
					);
					$this->sendEmail($sub, false);
				}
				FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->setId($sub->akeebasubs_subscription_id)
					->getItem()
					->save($data);
				// Timeout check -- Only if we sent at least one email!
				$clockNow = microtime(true);
				$elapsed = $clockNow - $clockStart;
				if($elapsed > 2) return;
			}
		}

		// Finally, update the last run info and call it a day
		$this->setLastRunTimestamp();
	}
	
	/**
	 * Fetches the com_akeebasubs component's parameters as a JParameter instance
	 *
	 * @return JParameter The component parameters
	 */
	private function getComponentParameters()
	{
		jimport('joomla.html.parameter');
		$component = JComponentHelper::getComponent( 'com_akeebasubs' );
		
		if($component->params instanceof JRegistry) {
			$cparams = $component->params;
		} elseif(!empty($component->params)) {
			$cparams = new JParameter($component->params);
		} else {
			$cparams = new JParameter('');
		}
		return $cparams;
	}
	
	/**
	 * "Do I have to run?" - the age old question. Let it be answered by checking the
	 * last execution timestamp, stored in the component's configuration.
	 */
	private function doIHaveToRun()
	{
		$params = $this->getComponentParameters();
		$lastRunUnix = $params->get('plg_akeebasubs_asexpirationnotify_timestamp',0);
		$dateInfo = getdate($lastRunUnix);
		$nextRunUnix = mktime(0, 0, 0, $dateInfo['mon'], $dateInfo['mday'], $dateInfo['year']);
		$nextRunUnix += 24 * 3600;
		$now = time();
		return ($now >= $nextRunUnix);
	}
	
	/**
	 * Saves the timestamp of this plugin's last run
	 */
	private function setLastRunTimestamp()
	{
		$lastRun = time();
		$params = $this->getComponentParameters();
		$params->set('plg_akeebasubs_asexpirationnotify_timestamp', $lastRun);
		
		$db = JFactory::getDBO();
		
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			// Joomla! 1.6
			$data = $params->toString('JSON');
			$sql = 'UPDATE `#__extensions` SET `params` = '.$db->Quote($data).' WHERE '.
				"`element` = 'com_akeebasubs' AND `type` = 'component'";
		} else {
			// Joomla! 1.5
			$data = $params->toString('INI');
			$sql = 'UPDATE `#__components` SET `params` = '.$db->Quote($data).' WHERE '.
				"`option` = 'com_akeebasubs' AND `parent` = 0 AND `menuid` = 0";
		}
		
		$db->setQuery($sql);
		$db->query();
	}
	
	/**
	 * Sends a notification email to the user
	 * 
	 * @param AkeebasubsTableSubscription $row The subscription row
	 * @param bool $firstContact  Is this the first time we contact the user?
	 */
	private function sendEmail($row, $firstContact)
	{
		// Get the site name
		$config = JFactory::getConfig();
		$sitename = $config->getValue('config.sitename');
	
		// Get the user object
		$user = JFactory::getUser($row->user_id);
		
		// Load the language files
		// Load the language files and their overrides
		$jlang = JFactory::getLanguage();
		// -- English (default fallback)
		$jlang->load('plg_system_asexpirationnotify', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_system_asexpirationnotify.override', JPATH_ADMINISTRATOR, 'en-GB', true);
		// -- Default site language
		$jlang->load('plg_system_asexpirationnotify', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_system_asexpirationnotify.override', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		// -- Current site language
		$jlang->load('plg_system_asexpirationnotify', JPATH_ADMINISTRATOR, null, true);
		$jlang->load('plg_system_asexpirationnotify.override', JPATH_ADMINISTRATOR, null, true);
		// -- User's preferred language
		$uparams = is_object($user->params) ? $user->params : new JParameter($user->params);
		$userlang = $uparams->getValue('language','');
		if(!empty($userlang)) {
			$jlang->load('plg_system_asexpirationnotify', JPATH_ADMINISTRATOR, $userlang, true);
			$jlang->load('plg_system_asexpirationnotify.override', JPATH_ADMINISTRATOR, $userlang, true);
		}
		
		// Get the level
		$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->setId($row->akeebasubs_level_id)
			->getItem();
			
		// Get the from/to dates
		jimport('joomla.utilities.date');
		$jFrom = new JDate($row->publish_up);
		$jTo = new JDate($row->publish_down);
		
		// Get the "my subscriptions" URL
		$baseURL = JURI::base();
		$baseURL = str_replace('/administrator', '', $baseURL);
		$subpathURL = JURI::base(true);
		$subpathURL = str_replace('/administrator', '', $subpathURL);
		
		$url = str_replace('&amp;','&', JRoute::_('index.php?option=com_akeebasubs&view=subscriptions&layout=default'));
		if(substr($url,0,14) == '/administrator') $url = substr($url,14);
		$url = ltrim($url, '/');
		$subpathURL = ltrim($subpathURL, '/');
		if(substr($url,0,strlen($subpathURL)+1) == "$subpathURL/") $url = substr($url,strlen($subpathURL)+1);
		$url = rtrim($baseURL,'/').'/'.ltrim($url,'/');
		
		if($firstContact) {
			$subject_key = 'PLG_SYSTEM_ASEXPIRATIONNOTIFY_SUBJECT_FIRST';
			$body_key = 'PLG_SYSTEM_ASEXPIRATIONNOTIFY_BODY_FIRST';
		} else {
			$subject_key = 'PLG_SYSTEM_ASEXPIRATIONNOTIFY_SUBJECT_SECOND';
			$body_key = 'PLG_SYSTEM_ASEXPIRATIONNOTIFY_BODY_SECOND';
		}
		
		$substitution_vars = array(
			'name'				=> $user->name,
			'username'			=> $user->username,
			'email'				=> $user->email,
			'sitename'			=> $sitename,
			'level'				=> $level->title,
			'enabled'			=> $row->enabled ? JText::_('PLG_SYSTEM_ASEXPIRATIONNOTIFY_ENABLED') : JText::_('PLG_SYSTEM_ASEXPIRATIONNOTIFY_DISABLED'),
			'state'				=> JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$row->state),
			'from'				=> version_compare(JVERSION, '1.6', 'ge') ? $jFrom->format(JText::_('DATE_FORMAT_LC2')) : $jFrom->toFormat(JText::_('DATE_FORMAT_LC2')),
			'to'				=> version_compare(JVERSION, '1.6', 'ge') ? $jTo->format(JText::_('DATE_FORMAT_LC2')) : $jTo->toFormat(JText::_('DATE_FORMAT_LC2')),
			'url'				=> $url
		);
		
		$subject = JText::_($subject_key);
		$body = JText::_($body_key);
		
		foreach($substitution_vars as $key => $value) {
			$subject = str_ireplace('['.strtoupper($key).']', $value, $subject);
			$body = str_ireplace('['.strtoupper($key).']', $value, $body);
		}
		
		// DEBUG ---
		/**
		echo "<p><strong>From</strong>: ".$config->getvalue('config.fromname')." &lt;".$config->getvalue('config.mailfrom')."&gt;<br/><strong>To: </strong>".$user->email."</p><hr/><p>$subject</p><hr/><p>".nl2br($body)."</p>"; die();
		/**/
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