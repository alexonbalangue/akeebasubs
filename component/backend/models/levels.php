<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelLevels extends FOFModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->nameQuote('#__akeebasubs_levels'));
		
		$ordering = $this->getState('ordering',null,'int');
		if(is_numeric($ordering)) {
			$query->where($db->nameQuote('ordering').' = '.(int)$ordering);
		}
		
		$enabled = $this->getState('enabled','','cmd');
		if($enabled !== '') $enabled = (int)$enabled;
		if(is_numeric($enabled)) {
			$query->where($db->nameQuote('enabled').' = '.(int)$enabled);
		}
		
		$slug = $this->getState('slug',null);
		if($slug) {
			$query->where($db->nameQuote('slug').' = '.$db->quote($slug));
		}
		
		$title = $this->getState('title',null);
		if($title) {
			$query->where($db->nameQuote('title').' = '.$db->quote($title));
		}
		
		$only_once = $this->getState('only_once', null);
		$user = JFactory::getUser();
		if($only_once && $user->id) {
			$mysubs = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->user_id($user->id)
				->paystate('C')
				->getItemList();
			$subIDs = array();
			if(!empty($mysubs)) foreach($mysubs as $sub) {
				$subIDs[] = $sub->akeebasubs_level_id;
			}
			
			if(count($subIDs)) {
				$subIDs = array_unique($subIDs);
				$query->where(
				'('.
					'('.
						$db->nameQuote('only_once').' = '.$db->quote(0)
					.')'.
					' OR '.
					'('.
						'('.$db->nameQuote('only_once').' = '.$db->quote(1).')'
						.' AND '.
						'('.$db->nameQuote('akeebasubs_level_id').' NOT IN '.'('.implode(',',$subIDs).')'.')'
					.')'.
				')'
				);
			}
		}
		
		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where($db->nameQuote('description').' LIKE '.$db->quote($search));
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
			$query->where($db->nameQuote('akeebasubs_level_id').' IN ('.$ids.')');
		}
		
		$order = $this->getState('filter_order', 'akeebasubs_level_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_level_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);
		
		return $query;
	}
}