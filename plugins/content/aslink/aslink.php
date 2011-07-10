<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgContentAslink extends JPlugin
{
	/**
	 * Gets the level ID out of a level title. If an ID was passed, it simply returns the ID.
	 * If a non-existent subscription level is passed, it returns -1.
	 *
	 * @param $title string|int The subscription level title or ID
	 *
	 * @return int The subscription level ID
	 */
	private static function getId($title, $slug = false)
	{
		static $levels = null;
		static $slugs = null;
		
		// Don't process invalid titles
		if(empty($title)) return -1;
		
		// Fetch a list of subscription levels if we haven't done so already
		if(is_null($levels)) {
			$levels = array();
			$list = KFactory::tmp('admin::com.akeebasubs.model.levels')
				->getList();
			if(count($list)) foreach($list as $level) {
				$thisTitle = strtoupper($level->title);
				$levels[$thisTitle] = $level->id;
				$slugs[$thisTitle] = $level->slug;
			}
		}
		
		$title = strtoupper($title);
		if(array_key_exists($title, $levels)) {
			// Mapping found
			return $slug ? $slugs[$title] : $levels[$title];
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			if($slug) {
				$id = 0;
				foreach($levels as $lt => $lid) {
					if($lid == $title) {
						$id = $lid;
						break;
					}
				}
			}
			if($id == 0) return -1;
			return $slug ? $slugs[$id] : (int)$title;
		} else {
			// No match!
			return $slug ? '' : -1;
		}
	}
	
	private static function process($match)
	{
		$ret = '';
		
		$slug = self::getId($match[1], true);
		if(!empty($slug)) {
			$root = rtrim(JURI::base(),'/');
			$subpath = JURI::base(true);
			if(!empty($subpath) && ($subpath != '/')) {
				$root = substr($root, 0, -1 * strlen($subpath));
			}
			//@ob_clean(); header('Content-type: text/plain'); var_dump(JURI::base(),JURI::base(true));die();
			$ret = rtrim($root,'/').JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&slug='.$slug);
		}
		
		return $ret;
	}
	
	public function onPrepareContent( &$article, &$params, $limitstart )
	{
		if(!defined('KOOWA')) return;
		
		// Check whether the plugin should process or not
		if ( JString::strpos( $article->text, 'aslink' ) === false )
		{
			return true;
		}
		
		// Search for this tag in the content
		$regex = "#{aslink (.*?)}#s";
		
		$article->text = preg_replace_callback( $regex, array('self', 'process'), $article->text );
	}
}