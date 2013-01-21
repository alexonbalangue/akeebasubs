<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

// PHP version check
if(defined('PHP_VERSION')) {
	$version = PHP_VERSION;
} elseif(function_exists('phpversion')) {
	$version = phpversion();
} else {
	// No version info. I'll lie and hope for the best.
	$version = '5.0.0';
}
// Old PHP version detected. EJECT! EJECT! EJECT!
if(!version_compare($version, '5.3.0', '>=')) return;

// Make sure FOF is loaded, otherwise do not run
if(!defined('FOF_INCLUDED')) {
	include_once JPATH_LIBRARIES.'/fof/include.php';
}
if(!defined('FOF_INCLUDED') || !class_exists('FOFForm', true))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
jimport('joomla.application.component.helper');
if(!JComponentHelper::isEnabled('com_akeebasubs', true)) return;

class plgSystemAsexpirationnotify extends JPlugin
{
	/**
	 * Public constructor. Overridden to load the language strings.
	 */
	public function __construct(& $subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
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
		
		if (!class_exists('AkeebasubsHelperEmail'))
		{
			@include_once JPATH_ROOT . '/components/com_akeebasubs/helpers/email.php';
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
				->expires_from($jFrom->toSql())
				->expires_to($jTo->toSql())
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
					->expires_from($jFrom->toSql())
					->expires_to($jTo->toSql())
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
			$mNow = $jNow->toSql();
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
	 * Fetches the com_akeebasubs component's parameters as a JRegistry instance
	 *
	 * @return JRegistry The component parameters
	 */
	private function getComponentParameters()
	{
		jimport('joomla.registry.registry');
		$component = JComponentHelper::getComponent( 'com_akeebasubs' );
		
		if($component->params instanceof JRegistry) {
			$cparams = $component->params;
		} elseif(!empty($component->params)) {
			$cparams = new JRegistry($component->params);
		} else {
			$cparams = new JRegistry('{}');
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
		
		$data = $params->toString('JSON');
		$query = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params').' = '.$db->q($data))
			->where($db->qn('element').' = '.$db->q('com_akeebasubs'))
			->where($db->qn('type').' = '.$db->q('component'));
		$db->setQuery($query);
		$db->query();
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
			'title'			=> JText::_('PLG_SYSTEM_ASEXPIRATIONNOTIFY_EMAILSECTION'),
			'keys'			=> array(
				'first'					=> JText::_('PLG_SYSTEM_ASEXPIRATIONNOTIFY_EMAIL_FIRST'),
				'second'				=> JText::_('PLG_SYSTEM_ASEXPIRATIONNOTIFY_EMAIL_SECOND'),
			)
		);
	}
	
	/**
	 * Sends a notification email to the user
	 * 
	 * @param AkeebasubsTableSubscription $row The subscription row
	 * @param bool $firstContact  Is this the first time we contact the user?
	 */
	private function sendEmail($row, $firstContact)
	{
		// Get the user object
		$user = JFactory::getUser($row->user_id);
		
		$type = $firstContact ? 'first' : 'second';
		
		// Get a preloaded mailer
		$key = $this->_name . '_' . $type;
		$mailer = AkeebasubsHelperEmail::getPreloadedMailer($row, $key);
		
		if ($mailer === false)
		{
			return false;
		}
		
		$mailer->addRecipient($user->email);
		
		$result = $mailer->Send();
		
		return $result;
	}
}