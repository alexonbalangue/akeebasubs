<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelTaxrules extends FOFModel
{
	private function getFilterValues()
	{
		return (object)array(
			'ordering'		=> $this->getState('ordering','','int'),
			'enabled'		=> $this->getState('enabled','','cmd'),
			'search'		=> $this->getState('search',null,'string'),
			'country'		=> $this->getState('country',null,'cmd'),
			'vies'			=> $this->getState('vies','','cmd'),
			'state'			=> $this->getState('state',null,'cmd')
		);
	}
	
	public function buildQuery($overrideLimits = false) {
		$state = $this->getFilterValues();
		$db = $this->getDbo();
		$query = FOFQueryAbstract::getNew($db)
				->select('*')
				->from($db->nameQuote('#__akeebasubs_taxrules'));
		
		if(is_numeric($state->ordering)) {
			$query->where($db->nameQuote('ordering').'='.$db->quote($state->ordering));
		}
		
		if(is_numeric($state->enabled)) {
			$query->where($db->nameQuote('enabled').'='.$db->quote($state->enabled));
		}
		
		if(is_numeric($state->vies)) {
			$query->where($db->nameQuote('vies').'='.$db->quote($state->vies));
		}
		
		if($state->country) {
			$query->where($db->nameQuote('country').'='.$db->quote($state->country));
		}
		
		if($state->state) {
			$query->where($db->nameQuote('state').'='.$db->quote($state->state));
		}
		
		if($state->search)
		{
			$search = '%'.$state->search.'%';
			$query->where($db->nameQuote('city').' LIKE '.$db->quote($search));
		}
		
		$order = $this->getState('filter_order', 'akeebasubs_taxrule_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_taxrule_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);
		
		return $query;
	}
}