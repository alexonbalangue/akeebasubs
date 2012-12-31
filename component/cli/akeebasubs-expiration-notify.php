<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  --
 * 
 *  Command-line script to schedule the expiration notification emails
 */

// Define ourselves as a parent file
define( '_JEXEC', 1 );
// Required by the CMS
define('DS', DIRECTORY_SEPARATOR);

// Load system defines
if (file_exists(dirname(__FILE__).'/defines.php')) {
        require_once dirname(__FILE__).'/defines.php';
}

if (!defined('_JDEFINES')) {
        define('JPATH_BASE', dirname(__FILE__).'/../');
        require_once JPATH_BASE.'/includes/defines.php';
}

// Load the rest of the necessary files
include_once JPATH_LIBRARIES.'/import.php';
// Load the rest of the necessary files
include_once JPATH_LIBRARIES.'/import.php';
if(file_exists(JPATH_BASE.'/includes/version.php')) {
	require_once JPATH_BASE.'/includes/version.php';
} else {
	require_once JPATH_LIBRARIES.'/cms.php';
}

jimport('joomla.application.cli');
jimport('joomla.application.component.helper');

class AkeebaSubscriptionsExpirationNotifyApp extends JApplicationCli
{
	/**
	 * The main entry point of the application
	 */
	public function execute()
	{
		// Set all errors to output the messages to the console, in order to
		// avoid infinite loops in JError ;)
		restore_error_handler();
		JError::setErrorHandling(E_ERROR, 'die');
		JError::setErrorHandling(E_WARNING, 'echo');
		JError::setErrorHandling(E_NOTICE, 'echo');
		
		// Required by Joomla!
		jimport('joomla.environment.request');
		
		// Set the root path to Akeeba Subscriptions
		define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR.'/components/com_akeebasubs');
		
		// Allow inclusion of Joomla! files
		if(!defined('_JEXEC')) define('_JEXEC', 1 );
		
		// Load FOF
		jimport('fof.include');
		
		// Load the version.php file
		include_once JPATH_COMPONENT_ADMINISTRATOR.'/version.php';

		// Display banner
		$year = gmdate('Y');
		$phpversion = PHP_VERSION;
		$phpenvironment = PHP_SAPI;
		$phpos = PHP_OS;
		
		$this->out("Akeeba Subscriptions Expiration Notification Emails CLI ".AKEEBASUBS_VERSION." (".AKEEBASUBS_DATE.")");
		$this->out("Copyright (C) 2010-$year Nicholas K. Dionysopoulos");
		$this->out(str_repeat('-', 79));
		$this->out("Akeeba Subscriptions is Free Software, distributed under the terms of the GNU General");
		$this->out("Public License version 3 or, at your option, any later version.");
		$this->out("This program comes with ABSOLUTELY NO WARRANTY as per sections 15 & 16 of the");
		$this->out("license. See http://www.gnu.org/licenses/gpl-3.0.html for details.");
		$this->out(str_repeat('-', 79));
		$this->out("You are using PHP $phpversion ($phpenvironment)");
		$this->out("");
		
		// Unset time limits
		$safe_mode = true;
		if(function_exists('ini_get')) {
			$safe_mode = ini_get('safe_mode');
		}
		if(!$safe_mode && function_exists('set_time_limit')) {
			$this->out("Unsetting time limit restrictions");
			@set_time_limit(0);
		}
		
		// ===== START
		
		// Get today's date
		jimport('joomla.utilities.date');
		$jNow = new JDate();
		$now = $jNow->toUnix();
		
		// Get and loop all subscription levels
		$levels = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->enabled(1)
			->getList();

		foreach($levels as $level)
		{
			$this->out("Checking for subscriptions in the \"{$level->title}\" subscription level");
			
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
				$this->out("\t!! This level specifies the users should not be notified");
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
				$this->out("\tNo subscriptions to notify were found in this level");
				continue;
			}
			
			// Check is some of those subscriptions have been renewed. If so, set their contactFlag to 2
			$realSubs = array();
			$this->out("\tGetting list of subscriptions");
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
						$this->out("\t\t#{$sub->akeebasubs_subscription_id}: will not be notified (already renewed)");
						// The user has already renewed. Don't send him an email; just update the row
						FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
							->setId($sub->akeebasubs_subscription_id)
							->getItem()
							->save(array(
								'contact_flag'	=> 3
							));

					} else {
						// No renewals found. Let's nag our user.
						$this->out("\t\t#{$sub->akeebasubs_subscription_id}: will be notified");
						$realSubs[] = $sub;
					}
				}
			}
			
			// If there are no subscriptions, bail out
			if(empty($realSubs)) {
				$this->out("\tNo subscriptions to be notified in this level");
				continue;
			}
			
			// Loop through subscriptions and send out emails
			$jNow = new JDate();
			$mNow = $jNow->toSql();
			$this->out("\tProcessing notifications");
			foreach($realSubs as $sub) {
				// Is it the first or the second contact?
				$this->out("\t\t#{$sub->akeebasubs_subscription_id}", false);
				if($sub->contact_flag == 0) {
					// First contact
					$data = array(
						'akeebasubs_subscription_id' => $sub->akeebasubs_subscription_id,
						'contact_flag'		=> 1,
						'first_contact'		=> $mNow
					);
					$result = $this->sendEmail($sub, true);
				} elseif($sub->contact_flag == 1) {
					// Second and final contact
					$data = array(
						'akeebasubs_subscription_id' => $sub->akeebasubs_subscription_id,
						'contact_flag'		=> 2,
						'second_contact'	=> $mNow
					);
					$result = $this->sendEmail($sub, false);
				}
				if ($result)
				{
					$db = JFactory::getDbo();
					$data = (object)$data;
					$db->updateObject('#__akeebasubs_subscriptions', $data, 'akeebasubs_subscription_id');
					/*
					FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
						->setId($sub->akeebasubs_subscription_id)
						->getItem()
						->save($data);
					*/
				}
			}
		}
		
		// ===== END
		
		$this->out("Peak memory usage: ".$this->peakMemUsage());
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
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$sitename = $config->get('sitename');
		} else {
			$sitename = $config->getValue('config.sitename');
		}
	
		// Get the user object
		$user = JFactory::getUser($row->user_id);
		$this->out(" {$user->email} ", false);
		
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
		jimport('joomla.registry.registry');
		$uparams = is_object($user->params) ? $user->params : new JRegistry($user->params);
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$userlang = $uparams->get('language','');
		} else {
			$userlang = $uparams->getValue('language','');
		}
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
		jimport('joomla.environment.uri');
		jimport('joomla.application.component.helper');
		$componentParams = JComponentHelper::getParams('com_akeebasubs');
		$baseURL = $componentParams->get('siteurl' ,'http://www.example.com');
		
		$url = rtrim($baseURL, '/') . '/index.php?option=com_akeebasubs&view=subscriptions&layout=default';
		
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
			'from'				=> $jFrom->format(JText::_('DATE_FORMAT_LC2'), true),
			'to'				=> $jTo->format(JText::_('DATE_FORMAT_LC2'), true),
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
		echo "<p><strong>From</strong>: ".$config->getvalue('config.fromname')." &lt;".$config->getvalue('config.mailfrom')."&gt;<br/><strong>To: </strong>".$user->email."</p><hr/><p>$subject</p><hr/><p>".nl2br($body)."</p>";die();
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
		$mailer->addRecipient($user->email);
		$mailer->setSubject($subject);
		$mailer->setBody($body);
		$result = $mailer->Send();
		
		if ($result instanceof Exception)
		{
			$this->out(" FAILED");
			return false;
		}
		else
		{
			$this->out(" SENT");
			return true;
		}
	}
    
    function memUsage()
	{
		if(function_exists('memory_get_usage')) {
			$size = memory_get_usage();
			$unit=array('b','Kb','Mb','Gb','Tb','Pb');
	    	return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
		} else {
			return "(unknown)";
		}
	}
	
	function peakMemUsage()
	{
		if(function_exists('memory_get_peak_usage')) {
			$size = memory_get_peak_usage();
			$unit=array('b','Kb','Mb','Gb','Tb','Pb');
	    	return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
		} else {
			return "(unknown)";
		}
	}
}
 
JCli::getInstance( 'AkeebaSubscriptionsExpirationNotifyApp' )->execute( );
