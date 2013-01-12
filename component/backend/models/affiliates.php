<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelAffiliates extends FOFModel
{
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		
		$affiliate_id = $this->getState('affiliate_id',null,'int');
		
		// Sub-query 1: total net amount of subscriptions attributed to this affiliate
		$subquery1 = $db->getQuery(true)
			->select(array(
				$db->qn('akeebasubs_affiliate_id'),
				'SUM('.$db->qn('affiliate_comission').') AS '.$db->qn('owed'),
			))
			->from($db->qn('#__akeebasubs_subscriptions'))
			->where($db->qn('akeebasubs_affiliate_id').' > '.$db->q(0))
			->where($db->qn('state').' = '.$db->q('C'))
			->group($db->qn('akeebasubs_affiliate_id'))
		;
		if($affiliate_id > 0) $subquery1->where ($db->nameQuote ('akeebasubs_affiliate_id').' = '.$db->q($affiliate_id));
		
		// Sub-query 2: total payments made to this affiliate
		$subquery2 = $db->getQuery(true)
			->select(array(
				$db->qn('akeebasubs_affiliate_id'),
				'SUM('.$db->qn('amount').') AS '.$db->qn('paid'),
			))
			->from($db->qn('#__akeebasubs_affpayments'))
			->where($db->qn('akeebasubs_affiliate_id').' > '.$db->q(0))
			->group($db->qn('akeebasubs_affiliate_id'))
		;
		if($affiliate_id > 0) $subquery2->where ($db->nameQuote ('akeebasubs_affiliate_id').' = '.$db->q($affiliate_id));
		$subquery2 = (string)$subquery2;
		
		// Main query
		$query = $db->getQuery(true)
			->select(array(
				$db->qn('a').'.*',
				$db->qn('tna').'.'.$db->qn('owed'),
				$db->qn('p').'.'.$db->qn('paid'),
				$db->qn('u').'.'.$db->qn('username'),
				$db->qn('u').'.'.$db->qn('name'),
				$db->qn('u').'.'.$db->qn('email')
			))
			->from($db->qn('#__akeebasubs_affiliates').' AS '.$db->qn('a'))
			->join('LEFT OUTER', "($subquery1)".' AS '.$db->qn('tna').' USING ('.$db->qn('akeebasubs_affiliate_id').')')
			->join('LEFT OUTER', "($subquery2)".' AS '.$db->qn('p').' USING ('.$db->qn('akeebasubs_affiliate_id').')')
			->join('INNER', $db->qn('#__users').' AS '.$db->qn('u').' ON ('.$db->qn('u').'.'.$db->qn('id').'='.$db->qn('a').'.'.$db->qn('user_id').')')
		;
		
		// Filter by User ID
		$user_id = $this->getState('user_id',null,'int');
		if(is_numeric($user_id) && ($user_id > 0)) {
			$query->where($db->qn('a').'.'.$db->qn('user_id').' = '.$db->q($user_id));
		}
		
		// Filter by Enabled status
		$enabled = $this->getState('enabled',null,'cmd');
		if(is_numeric($enabled)) {
			$query->where($db->qn('a').'.'.$db->qn('enabled').' = '.$db->q($enabled));
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
		if($affiliate_id > 0) $query->where ($db->qn('a').'.'.$db->qn('akeebasubs_affiliate_id').' = '.$db->q($affiliate_id));
		
		// Fix the ordering
		$order = $this->getState('filter_order', 'akeebasubs_affiliate_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_affiliate_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($db->qn('a').'.'.$order.' '.$dir);
		
		return $query;
	}
}