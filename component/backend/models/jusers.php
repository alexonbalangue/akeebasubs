<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelJusers extends FOFModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__users'));
		
		$username = $this->getState('username',null,'raw');
		if(!empty($username)) {
			$query->where($db->qn('username').' = '.$db->q($username));
		}
		
		$userid = $this->getState('user_id',null,'int');
		if(!empty($userid)) {
			$query->where($db->qn('id').' = '.$db->q($userid));
		}
		
		$email = $this->getState('email',null,'raw');
		if(!empty($email)) {
			$query->where($db->qn('email').' = '.$db->q($email));
		}
		
		$block = $this->getState('block',null,'int');
		if(!is_null($block)) {
			$query->where($db->qn('block').' = '.$db->q($block));
		}
		
		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where(
				'('.
				'('.$db->qn('username').' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('name').' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('email').' LIKE '.$db->q($search).') '.
				')'
			);
		}
		
		$order = $this->getState('filter_order', 'id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);
		
		return $query;
	}
}