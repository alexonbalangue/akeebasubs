<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelLevels extends FOFModel
{
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('*')
			->from($db->nameQuote('#__akeebasubs_levels'));
		
		$ordering = $this->getState('ordering',null,'int');
		if(is_numeric($ordering)) {
			$query->where($db->nameQuote('ordering').' = '.(int)$ordering);
		}
		
		$enabled = $this->getState('enabled','','cmd');
		if($enabled !== '') $enabled = (int)$enabled;
		if(is_numeric($enabled)) {
			$query->where($db->nameQuote('enabled').' = '.(int)$enabled);
		}
		
		$slug = $this->getState('slug',null);
		if($slug) {
			$query->where($db->nameQuote('slug').' = '.$db->quote($slug));
		}
		
		$search = $this->getState('search',null);
		if($search)
		{
			$search = '%'.$search.'%';
			$query->where($db->nameQuote('description').' LIKE '.$db->quote($search));
		}
		
		return $query;
	}
}