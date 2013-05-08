<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
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
			'akeebasubs_level_id'
							=> $this->getState('akeebasubs_level_id',null,'cmd'),
			'country'		=> $this->getState('country',null,'cmd'),
			'vies'			=> $this->getState('vies','','cmd'),
			'state'			=> $this->getState('state',null,'cmd')
		);
	}

	public function buildQuery($overrideLimits = false) {
		$state = $this->getFilterValues();
		$db = $this->getDbo();
		$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__akeebasubs_taxrules'));

		if(is_numeric($state->ordering)) {
			$query->where($db->qn('ordering').'='.$db->q($state->ordering));
		}

		if(is_numeric($state->enabled)) {
			$query->where($db->qn('enabled').'='.$db->q($state->enabled));
		}

		if(is_numeric($state->vies)) {
			$query->where($db->qn('vies').'='.$db->q($state->vies));
		}

		if($state->akeebasubs_level_id) {
			$query->where($db->qn('akeebasubs_level_id') . '=' . $db->q($state->akeebasubs_level_id));
		}

		if($state->country) {
			$query->where($db->qn('country').'='.$db->q($state->country));
		}

		if($state->state) {
			$query->where($db->qn('state').'='.$db->q($state->state));
		}

		if($state->search)
		{
			$search = '%'.$state->search.'%';
			$query->where($db->qn('city').' LIKE '.$db->q($search));
		}

		$order = $this->getState('filter_order', 'akeebasubs_taxrule_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_taxrule_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}
}