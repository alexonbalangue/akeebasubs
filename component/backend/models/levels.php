<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
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
		if($order == 'id') $order = 'akeebasubs_level_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);
		
		return $query;
	}
}