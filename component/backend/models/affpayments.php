<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelAffpayments extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		// Main query
		$query = $db->getQuery(true)
			->select(array(
				$db->qn('p').'.*',
				$db->qn('a').'.'.$db->qn('user_id'),
				$db->qn('u').'.'.$db->qn('username'),
				$db->qn('u').'.'.$db->qn('name'),
				$db->qn('u').'.'.$db->qn('email')
			))
			->from($db->qn('#__akeebasubs_affpayments').' AS '.$db->qn('p'))
			->join('INNER', $db->qn('#__akeebasubs_affiliates').' AS '.$db->qn('a').' USING ('.$db->qn('akeebasubs_affiliate_id').')')
			->join('INNER', $db->qn('#__users').' AS '.$db->qn('u').' ON ('.$db->qn('u').'.'.$db->qn('id').'='.$db->qn('a').'.'.$db->qn('user_id').')')
		;
		
		// Filter by User ID
		$user_id = $this->getState('user_id',null,'int');
		if(is_numeric($user_id) && ($user_id > 0)) {
			$query->where($db->qn('a').'.'.$db->qn('user_id').' = '.$db->q($user_id));
		}
		
		// Search for username, fullname and/or email
		$search = $this->getState('search',null,'string');
		if(!empty($search)) {
			$search = '%'.$search.'%';
			$q1 = '('.$db->qn('u').'.'.$db->qn('username').' LIKE '.$db->q($search).')';
			$q2 = '('.$db->qn('u').'.'.$db->qn('name').' LIKE '.$db->q($search).')';
			$q3 = '('.$db->qn('u').'.'.$db->qn('email').' LIKE '.$db->q($search).')';
			$query->where("($q1 OR $q2 OR $q3)");
		}
		
		// Filter by affiliate ID
		$affiliate_id = $this->getState('affiliate_id',null,'int');
		if($affiliate_id > 0) {
			$query->where ($db->qn('a').'.'.$db->qn('akeebasubs_affiliate_id').' = '.$db->q($affiliate_id));
		}
		
		// Fix the ordering
		$order = $this->getState('filter_order', 'akeebasubs_affpayment_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_affpayment_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($db->qn('p').'.'.$order.' '.$dir);
		
		return $query;
	}
}