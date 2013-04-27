<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelLevels extends FOFModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__akeebasubs_levels'));

		$ordering = $this->getState('ordering',null,'int');
		if(is_numeric($ordering)) {
			$query->where($db->qn('ordering').' = '.(int)$ordering);
		}

		$enabled = $this->getState('enabled','','cmd');
		if($enabled !== '') $enabled = (int)$enabled;
		if(is_numeric($enabled)) {
			$query->where($db->qn('enabled').' = '.(int)$enabled);
		}

		$slug = $this->getState('slug',null);
		if($slug) {
			$query->where($db->qn('slug').' = '.$db->q($slug));
		}

		$title = $this->getState('title',null);
		if($title) {
			$query->where($db->qn('title').' = '.$db->q($title));
		}

		$subIDs = array();

		$only_once = $this->getState('only_once', null);
		$user = JFactory::getUser();
		if($only_once && $user->id) {
			$mysubs = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->user_id($user->id)
				->paystate('C')
				->getItemList();
			if(!empty($mysubs)) foreach($mysubs as $sub) {
				$subIDs[] = $sub->akeebasubs_level_id;
			}
			$subIDs = array_unique($subIDs);
		}
		if($only_once && $user->id) {
			if(count($subIDs)) {
				$query->where(
				'('.
					'('.
						$db->qn('only_once').' = '.$db->q(0)
					.')'.
					' OR '.
					'('.
						'('.$db->qn('only_once').' = '.$db->q(1).')'
						.' AND '.
						'('.$db->qn('akeebasubs_level_id').' NOT IN '.'('.implode(',',$subIDs).')'.')'
					.')'.
				')'
				);
			}
		}

		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where($db->qn('description').' LIKE '.$db->q($search));
		}

		// Filter by IDs
		$ids = $this->getState('id',null);
		if(is_array($ids)) {
			$temp = '';
			foreach($ids as $id) {
				$id = (int)$id;
				if($id > 0) $temp .= $id.',';
			}
			if(empty($temp)) $temp = ' ';
			$ids = substr($temp,0,-1);
		} elseif(is_string($ids) && (strpos($ids,',') !== false)) {
			$ids = explode(',', $ids);
			$temp = '';
			foreach($ids as $id) {
				$id = (int)$id;
				if($id > 0) $temp .= $id.',';
			}
			if(empty($temp)) $temp = ' ';
			$ids = substr($temp,0,-1);
		} elseif(is_numeric($ids) || is_string($ids)) {
			$ids = (int)$ids;
		} else {
			$ids = '';
		}
		if($ids) {
			$query->where($db->qn('akeebasubs_level_id').' IN ('.$ids.')');
		}

		$levelgroup = $this->getState('levelgroup',null,'int');
		if(is_numeric($levelgroup)) {
			$query->where($db->qn('akeebasubs_levelgroup_id').' = '.(int)$levelgroup);
		}

		$order = $this->getState('filter_order', 'akeebasubs_level_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_level_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
}