<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

JLoader::import('joomla.plugin.plugin');

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
if(!defined('FOF_INCLUDED') || !class_exists('FOFLess', true))
{
	return;
}

// Do not run if Akeeba Subscriptions is not enabled
JLoader::import('joomla.application.component.helper');
if(!JComponentHelper::isEnabled('com_akeebasubs', true)) return;

class plgContentAsrestricted extends JPlugin
{
	/**
	 * Gets the level ID out of a level title. If an ID was passed, it simply returns the ID.
	 * If a non-existent subscription level is passed, it returns -1.
	 *
	 * @param $title string|int The subscription level title or ID
	 *
	 * @return int The subscription level ID
	 */
	private static function getId($title)
	{
		static $levels = null;
		
		// Don't process invalid titles
		if(empty($title)) return -1;
		
		// Fetch a list of subscription levels if we haven't done so already
		if(is_null($levels)) {
			$levels = array();
			$list = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->getList();
			if(count($list)) foreach($list as $level) {
				$thisTitle = strtoupper($level->title);
				$levels[$thisTitle] = $level->akeebasubs_level_id;
			}
		}
		
		$title = strtoupper($title);
		if(array_key_exists($title, $levels)) {
			// Mapping found
			return($levels[$title]);
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}
	
	/**
	 * Checks if a user has a valid, active subscription by that particular ID
	 * 
	 * @param $id int The subscription level ID
	 *
	 * @return bool True if there is such a subscription
	 */
	private static function isTrue($id)
	{
		static $subscriptions = null;
				
		// Don't process empty or invalid IDs
		$id = trim($id);
		if(empty($id) || (($id <= 0) && ($id != '*'))) return false;
		
		// Don't process for guests
		$user = JFactory::getUser();
		if($user->guest) {
			$subscriptions = array();
		} elseif(is_null($subscriptions)) {
			$subscriptions = array();
			JLoader::import('joomla.utilities.date');
			$jNow = new JDate();
			$list = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->user_id($user->id)
				->expires_from($jNow->toSql())
				->paystate('C')
				//->publish_down($jNow->toSql())
				->getList();
			
			if(count($list)) foreach($list as $sub) {
				if($sub->enabled)
					if(!in_array($sub->akeebasubs_level_id, $subscriptions))
						$subscriptions[] = $sub->akeebasubs_level_id;
			}
		}
		
		if($id == '*') {
			return !empty($subscriptions);
		} else {
			return in_array($id, $subscriptions);
		}
	}
	
	/**
	 * preg_match callback to process each match
	 */
	private static function process($match)
	{
		$ret = '';
		
		if (self::analyze($match[1])) {
			$ret = $match[2];
		}
		
		return $ret;
	}
	
	/**
	 * Analyzes a filter statement and decides if it's true or not
	 * 
	 * @return boolean
	 */
	private static function analyze($statement)
	{
		$ret = false;
		
		if ($statement) {
			// Stupid, stupid crap... ampersands replaced by &amp;...
			$statement = str_replace('&amp;&amp;', '&&', $statement);
			// First, break down to OR statements
			$items = explode("||", trim($statement) );
			for ($i=0; $i<count($items) && !$ret; $i++) {
				// Break down AND statements
				$expression = trim($items[$i]);
				$subitems = explode('&&', $expression);
				$ret = true;
				
				foreach($subitems as $item)
				{
					$item = trim($item);
					$negate = false;
					if(substr($item,0,1) == '!') {
						$negate = true;
						$item = substr($item,1);
						$item = trim($item);
					}
					$id = trim($item);
					if($id != '*') $id = self::getId($id);
					$result = self::isTrue($id);
					$ret = $ret && ($negate ? !$result : $result);
				}
			}
		}

		return $ret;
	}
	
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		if(is_object($row)) {
			// Check whether the plugin should process or not
			if ( JString::strpos( $row->text, 'akeebasubs' ) === false )
			{
				return true;
			}
			
			// Search for this tag in the content
			$regex = "#{akeebasubs(.*?)}(.*?){/akeebasubs}#s";
			
			$row->text = preg_replace_callback( $regex, array('self', 'process'), $row->text );
		} else {
			if ( JString::strpos( $row, 'akeebasubs' ) === false ) {
				return true;
			}
			$regex = "#{akeebasubs(.*?)}(.*?){/akeebasubs}#s";
			$row = preg_replace_callback( $regex, array('self', 'process'), $row );
		}
		
		return true;
	}
}