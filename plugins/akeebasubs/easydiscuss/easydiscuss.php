<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c) 2012 Roland Dalmulder / csvimproved.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsEasyDiscuss extends JPlugin
{
	/** @var array Levels to Ranks to Add mapping */
	private $addRanks = array();
	
	/** @var array Levels to Ranks to Remove mapping */
	private $removeRanks = array();
	
	/** @var array Levels to Badges to Add mapping */
	private $addBadges = array();
	
	/** @var array Levels to Badges to Remove mapping */
	private $removeBadges = array();

	public function __construct(& $subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);

		// Load level to ranks mapping from plugin parameters		
		$strAddRanks = $this->params->get('addranks','');
		$this->addRanks = $this->parseMapping($strAddRanks, 'RANKS');
		
		$strRemoveRanks = $this->params->get('removeranks','');
		$this->removeRanks = $this->parseMapping($strRemoveRanks, 'RANKS');

		// Load level to badges mapping from plugin parameters		
		$strAddBadges = $this->params->get('addbadges','');
		$this->addBadges = $this->parseMapping($strAddBadges, 'BADGES');
		
		$strRemoveBadges = $this->params->get('removebadges','');
		$this->removeBadges = $this->parseMapping($strRemoveBadges, 'BADGES');
	}
	
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;
		if(array_key_exists('enabled', (array)$info['modified'])) {
			$this->onAKUserRefresh($row->user_id);
		}
	}
	
	/**
	 * Called whenever the administrator asks to refresh integration status.
	 * 
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Make sure we're configured
		if(empty($this->addRanks) && empty($this->removeRanks)) return;
	
		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();
			
		// Make sure there are subscriptions set for the user
		if(!count($subscriptions)) return;
		
		// Get the initial list of ranks to add/remove from
		$addRanks = array();
		$removeRanks = array();
		$addBadges = array();
		$removeBadges = array();
		foreach($subscriptions as $sub) {
			$level = $sub->akeebasubs_level_id;
			if($sub->enabled) {
				// Add ranks
				if(!empty($this->addRanks)
						&& (array_key_exists($level, $this->addRanks))) {
					$ranks = $this->addRanks[$level];
					foreach($ranks as $rank) {
						if(!in_array($rank, $addRanks) && ($rank > 0)) {
							$addRanks[] = $rank;
						}
					}
				}
				// Add badges
				if(!empty($this->addBadges)
						&& (array_key_exists($level, $this->addBadges))) {
					$badges = $this->addBadges[$level];
					foreach($badges as $badge) {
						if(!in_array($badge, $addBadges) && ($badge > 0)) {
							$addBadges[] = $badge;
						}
					}
				}
			} else {
				// Remove ranks
				if(!empty($this->removeRanks)
						&& array_key_exists($level, $this->removeRanks)) {
					$ranks = $this->removeRanks[$level];
					foreach($ranks as $rank) {
						if(!in_array($rank, $removeRanks) && ($rank > 0)) {
							$removeRanks[] = $rank;
						}
					}
				}
				// Remove badges
				if(!empty($this->removeBadges)
						&& array_key_exists($level, $this->removeBadges)) {
					$badges = $this->removeBadges[$level];
					foreach($badges as $badge) {
						if(!in_array($badge, $removeBadges) && ($badge > 0)) {
							$removeBadges[] = $badge;
						}
					}
				}
			}
		}
		
		// Sort the lists
		asort($addRanks);
		asort($removeRanks);
		asort($addBadges);
		asort($removeBadges);
		
		// Get DB connection
		$db = JFactory::getDBO();
		
		$time = date('Y-m-d G:i:s');
		// Add rank
		if(! empty($addRanks)) {
			// Use the last rank if there are several, because the user can only have one rank
			$rank_id = end($addRanks);
			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->qn('#__discuss_ranks_users'))
				->where($db->qn('user_id') . ' = ' . $db->q($user_id));
			$db->setQuery($query);
			$rank_count = $db->loadResult();
			if($rank_count > 0) {
				// Update existing rank
				$query = $db->getQuery(true)
					->update($db->qn('#__discuss_ranks_users'))
					->set($db->qn('rank_id').' = '.$db->q($rank_id))
					->set($db->qn('created').' = '.$db->q($time))
					->where($db->qn('user_id').' = '.$db->q($user_id))
					->where($db->qn('rank_id').' != '.$db->q($rank_id));
				$db->setQuery($query);
				$db->query();
			} else {
				// Insert new rank
				$query = $db->getQuery(true)
					->insert($db->qn('#__discuss_ranks_users'))
					->columns(array(
						$db->qn('rank_id'),
						$db->qn('user_id'),
						$db->qn('created')
					))
					->values($db->q($rank_id) . ', '
						. $db->q($user_id) . ', '
						. $db->q($date)
					);
				$db->setQuery($query);
				$db->query();
			}
		}

		// Remove ranks
		if(empty($addRanks)) {
			foreach($removeRanks as $rank_id) {
				$query = $db->getQuery(true)
					->delete($db->qn('#__discuss_ranks_users'))
					->where($db->qn('rank_id') . ' = ' . $db->q($rank_id))
					->where($db->qn('user_id') . ' = ' . $db->q($user_id));
				$db->setQuery($query);
				$db->query();
			}	
		}
		
		// Add badges
		foreach($addBadges as $badge_id) {
			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->qn('#__discuss_badges_users'))
				->where($db->qn('badge_id') . ' = ' . $db->q($badge_id))
				->where($db->qn('user_id') . ' = ' . $db->q($user_id));
			$db->setQuery($query);
			$badge_count = $db->loadResult();
			if(! ($badge_count > 0)) {
				// Give new badge
				$query = $db->getQuery(true)
					->insert($db->qn('#__discuss_badges_users'))
					->columns(array(
						$db->qn('badge_id'),
						$db->qn('user_id'),
						$db->qn('created'),
						$db->qn('published')
					))
					->values($db->q($badge_id) . ', '
						. $db->q($user_id) . ', '
						. $db->q($time) . ', '
						. $db->q('1')
					);
				$db->setQuery($query);
				$db->query();
			}
		}

		// Remove badges
		foreach($removeBadges as $badge_id) {
			if(in_array($badge_id, $addBadges)) continue;
			$query = $db->getQuery(true)
				->delete($db->qn('#__discuss_badges_users'))
				->where($db->qn('badge_id') . ' = ' . $db->q($badge_id))
				->where($db->qn('user_id') . ' = ' . $db->q($user_id));
			$db->setQuery($query);
			$db->query();
		}
		
	}
	
	/**
	 * Converts an Akeeba Subscriptions level to a numeric ID
	 * 
	 * @param $title string The level's name to be converted to an ID
	 *
	 * @return int The subscription level's ID or -1 if no match is found
	 */
	private function ASLevelToId($title)
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
	
	private function EasyDiscussRankToId($title)
	{
		static $ranks = null;
		
		if(empty($title)) return -1;
		
		if(is_null($ranks)) {
			$this->ListToId('#__discuss_ranks', 'title', 'id', $ranks);
		}
		
		$title = strtoupper(trim($title));
		if(array_key_exists($title, $ranks)) {
			// Mapping found
			return($ranks[$title]);
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}
	
	private function EasyDiscussBadgeToId($title)
	{
		static $badges = null;
		
		if(empty($title)) return -1;
		
		if(is_null($badges)) {
			$this->ListToId('#__discuss_badges', 'title', 'id', $badges);
		}
		
		$title = strtoupper(trim($title));
		if(array_key_exists($title, $badges)) {
			// Mapping found
			return($badges[$title]);
		} elseif( (int)$title == $title ) {
			// Numeric ID passed
			return (int)$title;
		} else {
			// No match!
			return -1;
		}
	}
	
	private function ListToId($table, $title_col, $id_col, &$lists)
	{
		$lists = array();

		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select(array(
				$db->qn($title_col),
				$db->qn($id_col),
			))
			->from($db->qn($table));
		$db->setQuery($query);
		$res = $db->loadObjectList();

		if(!empty($res)) {
			foreach($res as $item) {
				$t = strtoupper(trim($item->$title_col));
				$lists[$t] = $item->$id_col;
			}
		}
	}

	private function parseMapping($rawData, $type)
	{
		if(empty($rawData)) return array();
		
		$ret = array();
		
		// Just in case something funky happened...
		$rawData = str_replace("\\n", "\n", $rawData);
		$rawData = str_replace("\r", "\n", $rawData);
		$rawData = str_replace("\n\n", "\n", $rawData);
		
		$lines = explode("\n", $rawData);
		
		foreach($lines as $line) {
			$line = trim($line);
			$parts = explode('=', $line, 2);
			if(count($parts) != 2) continue;
			
			$level = $parts[0];
			$rawItems = $parts[1];
			
			$items = explode(',', $rawItems);
			if(empty($items)) continue;
			if(!is_array($items)) $items = array($items);
			
			$levelId = $this->ASLevelToId($level);
			$itemIds = array();
			foreach($items as $itemTitle) {
				if($type == 'RANKS') {
					$itemIds[] = $this->EasyDiscussRankToId($itemTitle);	
				} elseif ($type == 'BADGES') {
					$itemIds[] = $this->EasyDiscussBadgeToId($itemTitle);
				}
			}
			
			$ret[$levelId] = $itemIds;
		}
		
		return $ret;
	}
}