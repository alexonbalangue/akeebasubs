<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelUpgrades extends FOFModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__akeebasubs_upgrades'));

		$ordering = $this->getState('ordering',null,'int');
		if(is_numeric($ordering)) {
			$query->where($db->qn('ordering').' = '.(int)$ordering);
		}

		$to_id = $this->getState('to_id',null,'int');
		if(is_numeric($to_id) && ($to_id > 0)) {
			$query->where($db->qn('to_id').' = '.(int)$to_id);
		}

		$from_id = $this->getState('from_id',null,'int');
		if(is_numeric($from_id) && ($from_id > 0)) {
			$query->where($db->qn('from_id').' = '.(int)$from_id);
		}

		$enabled = $this->getState('enabled','','cmd');
		if($enabled !== '') $enabled = (int)$enabled;
		if(is_numeric($enabled)) {
			$query->where($db->qn('enabled').' = '.(int)$enabled);
		}

		$combine = $this->getState('combine','','cmd');
		if($combine !== '') $combine = (int)$combine;
		if(is_numeric($combine)) {
			$query->where($db->qn('combine').' = '.(int)$combine);
		}

		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where(
				$db->qn('title').' LIKE '.$db->q($search)
			);
		}

		$order = $this->getState('filter_order', 'akeebasubs_upgrade_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_upgrade_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
}