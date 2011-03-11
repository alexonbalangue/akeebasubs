<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsModelJusers extends KModelTable
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		$this->_state
			->insert('block'	, 'int');
	}

	protected function _buildQueryWhere(KDatabaseQuery $query)
	{
		$state = $this->_state;

		if(is_numeric($state->block)) {
			$query->where('tbl.block','=', $state->block);
		}
		
		if($state->search)
		{
			$search = '%'.$state->search.'%';
			$query
				->where('name', 'LIKE',  $search)
				->where('username', 'LIKE',  $search, 'OR')
				->where('email', 'LIKE',  $search, 'OR');
		}
		
		parent::_buildQueryWhere($query);
	}
}