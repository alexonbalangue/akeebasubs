<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelCoupons extends FOFModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__akeebasubs_coupons'));
		
		$ordering = $this->getState('ordering',null,'int');
		if(is_numeric($ordering)) {
			$query->where($db->qn('ordering').' = '.(int)$ordering);
		}
		
		$enabled = $this->getState('enabled','','cmd');
		if($enabled !== '') $enabled = (int)$enabled;
		if(is_numeric($enabled)) {
			$query->where($db->qn('enabled').' = '.(int)$enabled);
		}
		
		$coupon = $this->getState('coupon','','cmd');
		if(!empty($coupon)) {
			$query->where($db->qn('coupon').' LIKE '.$db->q($coupon));
		}
		
		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where(
				'('.
				'('.$db->qn('title').' LIKE '.$db->q($search).') OR'.
				'('.$db->qn('coupon').' LIKE '.$db->q($search).')'.
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
		
		JLoader::import('joomla.utilities.date');
		$jNow = new JDate();
		$uNow = $jNow->toUnix();

		$table = $this->getTable($this->table);
		$k = $table->getKeyName();
		
		foreach($resultArray as $index => &$row) {
			$triggered = false;
			
			if(!property_exists($row, 'publish_down')) continue;
			if(!property_exists($row, 'publish_up')) continue;
			
			if($row->publish_down && ($row->publish_down != '0000-00-00 00:00:00')) {
				$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
				if(!preg_match($regex, $row->publish_down)) {
					$row->publish_down = '2037-01-01';
				}
				if(!preg_match($regex, $row->publish_up)) {
					$row->publish_up = '2001-01-01';
				}
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