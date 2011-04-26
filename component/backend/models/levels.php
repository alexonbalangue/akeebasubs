<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsModelLevels extends KModelTable
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		$this->_state
			->insert('ordering'	, 'int')
			->insert('enabled'	, 'int');
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
		
		if($state->search)
		{
			$search = '%'.$state->search.'%';
			$query->where('description', 'LIKE',  $search);
		}
		
		parent::_buildQueryWhere($query);
	}
	
	public function validate($data)
	{
		$ret = array();
		
		if($data->_visual != 1) return $ret;

		if(empty($data->title)) {
			$ret[] = JText::_('COM_AKEEBASUBS_LEVEL_ERR_TITLE');
		}
		
		if(empty($data->slug)) {
			$ret[] = JText::_('COM_AKEEBASUBS_LEVEL_ERR_SLUG');
		}
		
		$existingItems = KFactory::tmp('admin::com.akeebasubs.model.level')->slug($data->slug)->getList();
		if(!empty($existingItems)) {
			$count = 0;
			foreach($existingItems as $item) {
				if($item->id != $data->id) $count++;
			}
			if($count) {
				$ret[] = JText::_('COM_AKEEBASUBS_LEVEL_ERR_SLUGUNIQUE');
			}
		}
		
		if(empty($data->image)) {
			$ret[] = JText::_('COM_AKEEBASUBS_LEVEL_ERR_IMAGE');
		}
		
		if($data->duration < 1) {
			$ret[] = JText::_('COM_AKEEBASUBS_LEVEL_ERR_LENGTH');
		}
		
		return $ret;
	}
	
}