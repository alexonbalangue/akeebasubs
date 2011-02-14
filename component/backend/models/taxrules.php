<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsModelTaxrules extends KModelTable
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		$this->_state
			->insert('ordering'	, 'int')
			->insert('enabled'	, 'int')
			->insert('country'	, 'cmd')
			->insert('vies'		, 'int')
			->insert('state'	, 'cmd');
	}

	protected function _buildQueryWhere(KDatabaseQuery $query)
	{
		$state = $this->_state;

		if(is_numeric($state->ordering)) {
			$query->where('tbl.ordering','=', $state->ordering);
		}
		
		if(is_numeric($state->enabled)) {
			$query->where('tbl.enabled','=', $state->enabled);
		}

		if(is_numeric($state->vies)) {
			$query->where('tbl.vies','=', $state->vies);
		}
		
		if($state->country) {
			$query->where('tbl.country','=', $state->country);
		}

		if($state->state) {
			$query->where('tbl.state','=', $state->state);
		}

		if($state->search)
		{
			$search = '%'.$state->search.'%';
			$query->where('city', 'LIKE',  $search);
		}
		
		parent::_buildQueryWhere($query);
	}
}