<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
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
		$query = FOFQueryAbstract::getNew($db)
			->select(array(
				$db->nameQuote('p').'.*',
				$db->nameQuote('a').'.'.$db->nameQuote('user_id'),
				$db->nameQuote('u').'.'.$db->nameQuote('username'),
				$db->nameQuote('u').'.'.$db->nameQuote('name'),
				$db->nameQuote('u').'.'.$db->nameQuote('email')
			))
			->from($db->nameQuote('#__akeebasubs_affpayments').' AS '.$db->nameQuote('p'))
			->join('INNER', $db->nameQuote('#__akeebasubs_affiliates').' AS '.$db->nameQuote('a').' USING ('.$db->nameQuote('akeebasubs_affiliate_id').')')
			->join('INNER', $db->nameQuote('#__users').' AS '.$db->nameQuote('u').' ON ('.$db->nameQuote('u').'.'.$db->nameQuote('id').'='.$db->nameQuote('a').'.'.$db->nameQuote('user_id').')')
		;
		
		// Filter by User ID
		$user_id = $this->getState('user_id',null,'int');
		if(is_numeric($user_id) && ($user_id > 0)) {
			$query->where($db->nameQuote('a').'.'.$db->nameQuote('user_id').' = '.$db->quote($user_id));
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
		$affiliate_id = $this->getState('affiliate_id',null,'int');
		if($affiliate_id > 0) {
			$query->where ($db->nameQuote('a').'.'.$db->nameQuote('akeebasubs_affiliate_id').' = '.$db->quote($affiliate_id));
		}
		
		// Fix the ordering
		$order = $this->getState('filter_order', 'akeebasubs_affpayment_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_affpayment_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($db->nameQuote('p').'.'.$order.' '.$dir);
		
		return $query;
	}
}