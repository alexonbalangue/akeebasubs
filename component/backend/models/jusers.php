<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelJusers extends FOFModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->nameQuote('#__users'));
		
		$username = $this->getState('username',null,'raw');
		if(!empty($username)) {
			$query->where($db->nameQuote('username').' = '.$db->quote($username));
		}
		
		$userid = $this->getState('user_id',null,'int');
		if(!empty($userid)) {
			$query->where($db->nameQuote('id').' = '.$db->quote($userid));
		}
		
		$email = $this->getState('email',null,'raw');
		if(!empty($email)) {
			$query->where($db->nameQuote('email').' = '.$db->quote($email));
		}
		
		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where(
				'('.
				'('.$db->nameQuote('username').' LIKE '.$db->quote($search).') OR '.
				'('.$db->nameQuote('name').' LIKE '.$db->quote($search).') OR '.
				'('.$db->nameQuote('email').' LIKE '.$db->quote($search).') '.
				')'
			);
		}
		
		$order = $this->getState('filter_order', 'id', 'cmd');
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);
		
		return $query;
	}
}