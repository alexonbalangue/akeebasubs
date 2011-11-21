<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
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
		$subquery1 = FOFQueryAbstract::getNew($db)
			->select(array(
				$db->nameQuote('akeebasubs_affiliate_id'),
				'SUM('.$db->nameQuote('affiliate_comission').') AS '.$db->nameQuote('owed'),
			))
			->from($db->nameQuote('#__akeebasubs_subscriptions'))
			->where($db->nameQuote('akeebasubs_affiliate_id').' > '.$db->quote(0))
			->where($db->nameQuote('state').' = '.$db->quote('C'))
			->group($db->nameQuote('akeebasubs_affiliate_id'))
		;
		if($affiliate_id > 0) $subquery1->where ($db->nameQuote ('akeebasubs_affiliate_id').' = '.$db->quote($affiliate_id));
		
		// Sub-query 2: total payments made to this affiliate
		$subquery2 = FOFQueryAbstract::getNew($db)
			->select(array(
				$db->nameQuote('akeebasubs_affiliate_id'),
				'SUM('.$db->nameQuote('amount').') AS '.$db->nameQuote('paid'),
			))
			->from($db->nameQuote('#__akeebasubs_affpayments'))
			->where($db->nameQuote('akeebasubs_affiliate_id').' > '.$db->quote(0))
			->group($db->nameQuote('akeebasubs_affiliate_id'))
		;
		if($affiliate_id > 0) $subquery2->where ($db->nameQuote ('akeebasubs_affiliate_id').' = '.$db->quote($affiliate_id));
		$subquery2 = (string)$subquery2;
		
		// Main query
		$query = FOFQueryAbstract::getNew($db)
			->select(array(
				$db->nameQuote('a').'.*',
				$db->nameQuote('tna').'.'.$db->nameQuote('owed'),
				$db->nameQuote('p').'.'.$db->nameQuote('paid'),
				$db->nameQuote('u').'.'.$db->nameQuote('username'),
				$db->nameQuote('u').'.'.$db->nameQuote('name'),
				$db->nameQuote('u').'.'.$db->nameQuote('email')
			))
			->from($db->nameQuote('#__akeebasubs_affiliates').' AS '.$db->nameQuote('a'))
			->join('LEFT OUTER', "($subquery1)".' AS '.$db->nameQuote('tna').' USING ('.$db->nameQuote('akeebasubs_affiliate_id').')')
			->join('LEFT OUTER', "($subquery2)".' AS '.$db->nameQuote('p').' USING ('.$db->nameQuote('akeebasubs_affiliate_id').')')
			->join('INNER', $db->nameQuote('#__users').' AS '.$db->nameQuote('u').' ON ('.$db->nameQuote('u').'.'.$db->nameQuote('id').'='.$db->nameQuote('a').'.'.$db->nameQuote('user_id').')')
		;
		
		// Filter by User ID
		$user_id = $this->getState('user_id',null,'int');
		if(is_numeric($user_id) && ($user_id > 0)) {
			$query->where($db->nameQuote('a').'.'.$db->nameQuote('user_id').' = '.$db->quote($user_id));
		}
		
		// Filter by Enabled status
		$enabled = $this->getState('enabled',null,'cmd');
		if(is_numeric($enabled)) {
			$query->where($db->nameQuote('a').'.'.$db->nameQuote('enabled').' = '.$db->quote($enabled));
		}
		
		// Search for username, fullname and/or email
		$search = $this->getState('search',null,'string');
		if(!empty($search)) {
			$search = '%'.$search.'%';
			$q1 = '('.$db->nameQuote('u').'.'.$db->nameQuote('username').' LIKE '.$db->quote($search).')';
			$q2 = '('.$db->nameQuote('u').'.'.$db->nameQuote('name').' LIKE '.$db->quote($search).')';
			$q3 = '('.$db->nameQuote('u').'.'.$db->nameQuote('email').' LIKE '.$db->quote($search).')';
			$query->where("($q1 OR $q2 OR $q3)");
		}
		
		// Filter by affiliate ID
		if($affiliate_id > 0) $query->where ($db->nameQuote('a').'.'.$db->nameQuote('akeebasubs_affiliate_id').' = '.$db->quote($affiliate_id));
		
		// Fix the ordering
		$order = $this->getState('filter_order', 'akeebasubs_affiliate_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_affiliate_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($db->nameQuote('a').'.'.$order.' '.$dir);
		
		return $query;
	}
}