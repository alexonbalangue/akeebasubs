<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsModelUpgrades extends KModelTable
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		$this->_state
			->insert('search'			, 'string')
			->insert('to_id'			, 'int')
			->insert('enabled'			, 'int');
	}

	protected function _buildQueryWhere(KDatabaseQuery $query)
	{
		$state = $this->_state;

		if($state->to_id) {
			$query->where('tbl.to_id', '=', $state->to_id);
		}

		if(is_numeric($state->enabled)) {
			$query->where('tbl.enabled', '=', $state->enabled);
		}

		if($state->search)
		{
			$search = '%'.$state->search.'%';
			$query
				->where('tbl.title', 'LIKE',  $search);
		}
		
		parent::_buildQueryWhere($query);
	}
	
	public function validate($data)
	{
		$ret = array();
		
		if($data->_visual != 1) return $ret;
		
		if(empty($data->title)) {
			$ret[] = JText::_('COM_AKEEBASUBS_UPGRADE_ERR_TITLE');
		}
				
		if(empty($data->from_id)) {
			$ret[] = JText::_('COM_AKEEBASUBS_UPGRADE_ERR_FROM_ID');
		}

		if(empty($data->to_id)) {
			$ret[] = JText::_('COM_AKEEBASUBS_UPGRADE_ERR_TO_ID');
		}
		
		if(empty($data->min_presence)) {
			$data->min_presence = 0;
		}

		if(empty($data->max_presence)) {
			$data->max_presence = 36500;
		}
		
		if(empty($data->type)) {
			$ret[] = JText::_('COM_AKEEBASUBS_UPGRADE_ERR_TYPE');
		}
		
		if(empty($data->value)) {
			$ret[] = JText::_('COM_AKEEBASUBS_UPGRADE_ERR_VALUE');
		}
		
		return $ret;
	}	
}