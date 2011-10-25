<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/fof/include.php';
if(!defined('FOF_INCLUDED')) return;

class plgSystemAsexpirationcontrol extends JPlugin
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
		
		// Load the language files		
		$jlang =& JFactory::getLanguage();
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);
		
		$jlang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
		$jlang->load('com_akeebasubs', JPATH_SITE, $jlang->getDefault(), true);
		$jlang->load('com_akeebasubs', JPATH_SITE, null, true);
	}

	/**
	 * Called when Joomla! is booting up and checks for expired subscriptions.
	 */
	public function onAfterInitialise()
	{
		// Check if we need to run
		if(!$this->doIHaveToRun()) return;
	
		// Get today's date
		jimport('joomla.utilities.date');
		$jNow = new JDate();
		$now = $jNow->toUnix();
		
		// Load a list of subscriptions which have to expire -- Nooku does the rest magically!
		$subs = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->enabled(1)
			->expires_to($jNow->toMySQL())
			->getList();
		
		// Update the last run info and quit
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
		$component =& JComponentHelper::getComponent( 'com_akeebasubs' );
		if(!empty($component->params)) {
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
		$lastRunUnix = $params->get('plg_akeebasubs_asexpirationcontrol_timestamp',0);
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
		$params->set('plg_akeebasubs_asexpirationcontrol_timestamp', $lastRun);
		
		$db =& JFactory::getDBO();
		$data = $params->toString();
		
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			// Joomla! 1.6
			$sql = 'UPDATE `#__extensions` SET `params` = '.$db->Quote($data).' WHERE '.
				"`element` = 'com_akeebasubs' AND `type` = 'component'";
		} else {
			// Joomla! 1.5
			$sql = 'UPDATE `#__components` SET `params` = '.$db->Quote($data).' WHERE '.
				"`option` = 'com_akeebasubs' AND `parent` = 0 AND `menuid` = 0";
		}
		
		$db->setQuery($sql);
		$db->query();
	}
	
}