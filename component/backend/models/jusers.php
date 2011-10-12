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
		
		return $query;
	}
}