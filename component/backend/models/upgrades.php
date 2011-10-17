<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelUpgrades extends FOFModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->nameQuote('#__akeebasubs_upgrades'));
		
		$ordering = $this->getState('ordering',null,'int');
		if(is_numeric($ordering)) {
			$query->where($db->nameQuote('ordering').' = '.(int)$ordering);
		}
		
		$to_id = $this->getState('to_id',null,'int');
		if(is_numeric($to_id)) {
			$query->where($db->nameQuote('to_id').' = '.(int)$to_id);
		}
		
		$enabled = $this->getState('enabled','','cmd');
		if($enabled !== '') $enabled = (int)$enabled;
		if(is_numeric($enabled)) {
			$query->where($db->nameQuote('enabled').' = '.(int)$enabled);
		}
		
		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where(
				$db->nameQuote('title').' LIKE '.$db->quote($search)
			);
		}
		
		$order = $this->getState('filter_order', 'akeebasubs_upgrade_id', 'cmd');
		if($order == 'id') $order = 'akeebasubs_upgrade_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);
		
		return $query;
	}
}