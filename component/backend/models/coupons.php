<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelCoupons extends FOFModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->nameQuote('#__akeebasubs_coupons'));
		
		$ordering = $this->getState('ordering',null,'int');
		if(is_numeric($ordering)) {
			$query->where($db->nameQuote('ordering').' = '.(int)$ordering);
		}
		
		$enabled = $this->getState('enabled','','cmd');
		if($enabled !== '') $enabled = (int)$enabled;
		if(is_numeric($enabled)) {
			$query->where($db->nameQuote('enabled').' = '.(int)$enabled);
		}
		
		$coupon = $this->getState('coupon','','cmd');
		if(!empty($coupon)) {
			$query->where($db->nameQuote('coupon').' LIKE '.$db->quote($coupon));
		}
		
		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where(
				'('.
				'('.$db->nameQuote('title').' LIKE '.$db->quote($search).') OR'.
				'('.$db->nameQuote('coupon').' LIKE '.$db->quote($search).')'.
				')'
			);
		}
		
		$order = $this->getState('filter_order', 'akeebasubs_coupon_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_coupon_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
	
	public function onProcessList(&$resultArray) {
		// Implement the coupon automatic expiration
		if(empty($resultArray)) return;
		
		if($this->getState('skipOnProcessList',0)) return;
		
		jimport('joomla.utilities.date');
		$jNow = new JDate();
		$uNow = $jNow->toUnix();

		$table = $this->getTable($this->table);
		$k = $table->getKeyName();
		
		foreach($resultArray as $index => &$row) {
			$triggered = false;
			
			if(!property_exists($row, 'publish_down')) continue;
			if(!property_exists($row, 'publish_up')) continue;
			
			if($row->publish_down && ($row->publish_down != '0000-00-00 00:00:00')) {
				$jDown = new JDate($row->publish_down);
				$jUp = new JDate($row->publish_up);
				if( ($uNow >= $jDown->toUnix()) && $row->enabled ) {
					$row->enabled = 0;
					$triggered = true;
				} elseif(($uNow >= $jUp->toUnix()) && !$row->enabled && ($uNow < $jDown->toUnix())) {
					$row->enabled = 1;
					$triggered = true;
				}
			}
			
			if($triggered) {
				$table->reset();
				$table->load($row->$k);
				$table->save($row);
			}
		}
	}
}