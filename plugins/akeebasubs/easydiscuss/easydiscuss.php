<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsEasyDiscuss extends plgAkeebasubsAbstract
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
		$templatePath = dirname(__FILE__);
		$name = 'easydiscuss';

		parent::__construct($subject, $name, $config, $templatePath, false);

		// Do we have values from the Olden Days?
		if(isset($config['params']) && false) {
			$configParams = @json_decode($config['params']);
			// Ranks
			if(property_exists($configParams, 'addranks'))
			{
				$strAddRanks = $configParams->addranks;
			}
			else
			{
				$strAddRanks = null;
			}
			if(property_exists($configParams, 'removeranks'))
			{
				$strRemoveRanks = $configParams->removeranks;
			}
			else
			{
				$strRemoveRanks =  null;
			}
			// Badges
			if(property_exists($configParams, 'addbadges'))
			{
				$strAddBadges = $configParams->addbadges;
			}
			else
			{
				$strAddBadges = null;
			}
			if(property_exists($configParams, 'removebadges'))
			{
				$strRemoveBadges = $configParams->removebadges;
			}
			else
			{
				$strRemoveBadges =  null;
			}
		}

		if(!empty($strAddRanks) || !empty($strRemoveRanks)
				|| !empty($strAddRanks) || !empty($strRemoveRanks)) {
			// Load level to ranks mapping from plugin parameters
			$this->addRanks = $this->parseMapping($strAddRanks, 'RANKS');
			$this->removeRanks = $this->parseMapping($strRemoveRanks, 'RANKS');
			// Load level to badges mapping from plugin parameters
			$this->addBadges = $this->parseMapping($strAddBadges, 'BADGES');
			$this->removeBadges = $this->parseMapping($strRemoveBadges, 'BADGES');
			// Do a transparent upgrade
			$this->upgradeSettings($config);
		} else {
			$this->loadGroupAssignments();
		}
	}

	protected function loadGroupAssignments()
	{
		$this->addRanks = array();
		$this->removeRanks = array();
		$this->addBadges = array();
		$this->removeBadges = array();

		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$addranksKey = 'easydiscuss_addranks';
		$removeranksKey = 'easydiscuss_removeranks';
		$addbadgesKey = 'easydiscuss_addbadges';
		$removebadgesKey = 'easydiscuss_removebadges';
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					continue;
				}
				// Ranks
				if(property_exists($level->params, $addranksKey))
				{
					$this->addRanks[$level->akeebasubs_level_id] = array_filter($level->params->$addranksKey);
				}
				if(property_exists($level->params, $removeranksKey))
				{
					$this->removeRanks[$level->akeebasubs_level_id] = array_filter($level->params->$removeranksKey);
				}
				// Badges
				if(property_exists($level->params, $addbadgesKey))
				{
					$this->addBadges[$level->akeebasubs_level_id] = array_filter($level->params->$addbadgesKey);
				}
				if(property_exists($level->params, $removebadgesKey))
				{
					$this->removeBadges[$level->akeebasubs_level_id] = array_filter($level->params->$removebadgesKey);
				}
			}
		}
	}

	public function onAKUserRefresh($user_id)
	{
		// Make sure we're configured
		if(empty($this->addRanks)  && empty($this->removeRanks) &&
		   empty($this->addBadges) && empty($this->removeBadges)   )
		{
			return;
		}

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

		$time = JFactory::getDate()->toSql();
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
				$db->execute();
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
						. $db->q($time)
					);
				$db->setQuery($query);
				$db->execute();
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
				$db->execute();
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
				$db->execute();
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
			$db->execute();
		}

	}

	/**
	 * Not used in this plugin
	 */
	protected function getGroups() {
		return array();
	}

	protected function getSelectField($level, $type)
	{
		static $ranks = null;
		static $badges = null;

		$parts = explode('-', $type, 2);
		$type = $parts[0];
		$group = $parts[1];

		if(! in_array($type, array('add', 'remove'))) return '';
		if(! in_array($group, array('RANKS', 'BADGES'))) return '';
		$options = array();
		$options[] = JHTML::_('select.option','',JText::_('PLG_AKEEBASUBS_' . strtoupper($this->name) . '_NONE'));
		if($group == 'RANKS') {
			// Get ranks
			if(is_null($ranks)) {
				$this->ListToId('#__discuss_ranks', 'title', 'id', $ranks);
			}
			foreach($ranks as $title => $id) {
				$options[] = JHTML::_('select.option',$id,$title);
			}
			// Set pre-selected values
			$selected = array();
			if($type == 'add') {
				if(! empty($this->addRanks[$level->akeebasubs_level_id])) {
					$selected = $this->addRanks[$level->akeebasubs_level_id];
				}
			} else {
				if(! empty($this->removeRanks[$level->akeebasubs_level_id])) {
					$selected = $this->removeRanks[$level->akeebasubs_level_id];
				}
			}
			// Create the select field
			return JHtmlSelect::genericlist($options, 'params[easydiscuss_' . $type . 'ranks][]', 'multiple="multiple" size="8" class="input-large"', 'value', 'text', $selected);
		} else {
			// Get badges
			if(is_null($badges)) {
				$this->ListToId('#__discuss_badges', 'title', 'id', $badges);
			}
			foreach($badges as $title => $id) {
				$options[] = JHTML::_('select.option',$id,$title);
			}
			// Set pre-selected values
			$selected = array();
			if($type == 'add') {
				if(! empty($this->addBadges[$level->akeebasubs_level_id])) {
					$selected = $this->addBadges[$level->akeebasubs_level_id];
				}
			} else {
				if(! empty($this->removeBadges[$level->akeebasubs_level_id])) {
					$selected = $this->removeBadges[$level->akeebasubs_level_id];
				}
			}
			// Create the select field
			return JHtmlSelect::genericlist($options, 'params[easydiscuss_' . $type . strtolower($group) . '][]', 'multiple="multiple" size="8" class="input-large"', 'value', 'text', $selected);
		}
	}

	/**
	 * =========================================================================
	 * !!! CRUFT WARNING !!!
	 * =========================================================================
	 *
	 * The following methods are leftovers from the Olden Days (before 2.4.5).
	 * At some point (most likely 2.6) they will be removed. For now they will
	 * stay here so that we can do a transparent migration.
	 */

	/**
	 * Moves this plugin's settings from the plugin into each subscription
	 * level's configuration parameters.
	 */
	protected function upgradeSettings($config = array())
	{
		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$addranksKey = 'easydiscuss_addranks';
		$removeranksKey = 'easydiscuss_removeranks';
		$addbadgesKey = 'easydiscuss_addbadges';
		$removebadgesKey = 'easydiscuss_removebadges';
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				$save = false;
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					$level->params = new stdClass();
				}
				// Ranks
				if(array_key_exists($level->akeebasubs_level_id, $this->addRanks)) {
					if(empty($level->params->$addranksKey)) {
						$level->params->$addranksKey = $this->addRanks[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				if(array_key_exists($level->akeebasubs_level_id, $this->removeRanks)) {
					if(empty($level->params->$removeranksKey)) {
						$level->params->$removeranksKey = $this->removeRanks[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				// Badges
				if(array_key_exists($level->akeebasubs_level_id, $this->addBadges)) {
					if(empty($level->params->$addbadgesKey)) {
						$level->params->$addbadgesKey = $this->addBadges[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				if(array_key_exists($level->akeebasubs_level_id, $this->removeBadges)) {
					if(empty($level->params->$removebadgesKey)) {
						$level->params->$removebadgesKey = $this->removeBadges[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				if($save) {
					$level->params = json_encode($level->params);
					$result = $model->setId($level->akeebasubs_level_id)->save( $level );
				}
			}
		}

		// Remove the plugin parameters
		if(isset($config['params'])) {
			$configParams = @json_decode($config['params']);
			unset($configParams->addranks);
			unset($configParams->removeranks);
			unset($configParams->addbadges);
			unset($configParams->removebadges);
			$param_string = @json_encode($configParams);

			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->update($db->qn('#__extensions'))
				->where($db->qn('type').'='.$db->q('plugin'))
				->where($db->qn('element').'='.$db->q(strtolower($this->name)))
				->where($db->qn('folder').'='.$db->q('akeebasubs'))
				->set($db->qn('params').' = '.$db->q($param_string));
			$db->setQuery($query);
			$db->execute();
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