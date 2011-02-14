<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsModelSubscriptions extends KModelTable
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		$this->_state
			->insert('title'			, 'string')
			->insert('enabled'			, 'int')
			->insert('level'			, 'int')
			->insert('publish_up'		, 'date')
			->insert('publish_down'		, 'date')
			->insert('user_id'			, 'int')
			->insert('paystate'			, 'string')
			->insert('since'			, 'date');
	}

	protected function _buildQueryWhere(KDatabaseQuery $query)
	{
		$state = $this->_state;

		if(is_numeric($state->enabled)) {
			$query->where('tbl.enabled','=', $state->enabled);
		}

		if($state->title) {
			$search = '%'.$state->title.'%';
			$query->where('tbl.title', 'LIKE', $search);
		}
		
		if($state->search)
		{
			$search = '%'.$state->search.'%';
			$query
				->where('name', 'LIKE',  $search, 'OR')
				->where('username', 'LIKE',  $search, 'OR')
				->where('email', 'LIKE',  $search, 'OR')
				->where('businessname', 'LIKE',  $search, 'OR')
				->where('vatnumber', 'LIKE',  $search, 'OR');
		}
		
		if(is_numeric($state->level)) {
			$query->where('tbl.akeebasubs_level_id','=',$state->level);
		}
		
		if(is_numeric($state->user_id)) {
			$query->where('tbl.user_id','=',$state->user_id);
		}
		
		if($state->paystate) {
			$states = explode(',', $state->paystate);
			$query->where('tbl.state','IN',$states);
		}
		
		// Filter the dates
		jimport('joomla.utilities.date');
		$from = trim($state->publish_up);
		if(empty($from)) {
			$from = '';
		} else {
			$jFrom = new JDate($from);
			$from = $jFrom->toUnix();
			if($from == 0) {
				$from = '';
			} else {
				$from = $jFrom->toMySQL();
			}
		}
		
		$to = trim($state->publish_down);
		if(empty($to)) {
			$to = '';
		} else {
			$jTo = new JDate($to);
			$to = $jTo->toUnix();
			if($to == 0) {
				$to = '';
			} else {
				$to = $jTo->toMySQL();
			}
		}
		
		if(!empty($from) && !empty($to)) {
			// Filter from-to dates
			$dateFilterString = "'$from' AND '$to'";
			$query->where('tbl.publish_up','BETWEEN',$dateFilterString);
		} elseif(!empty($from) && empty($to)) {
			// Filter after date
			$query->where('tbl.publish_up','>=',"'$from'");
		} elseif(empty($from) && !empty($to)) {
			// Filter up to a date
			$query->where('tbl.publish_down','<=',"'$to'");
		}
		
		$since = trim($state->since);
		if(empty($since)) {
			$since = '';
		} else {
			$jFrom = new JDate($since);
			$since = $jFrom->toUnix();
			if($since == 0) {
				$since = '';
			} else {
				$since = $jFrom->toMySQL();
			}
		}
		$query->where('tbl.created_on','>=',"$since");
		
		parent::_buildQueryWhere($query);
	}
	
	public function validate($data)
	{
		$ret = array();
		
		if(empty($data->user_id)) {
			$ret[] = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_USER_ID');
		}
		
		if(empty($data->akeebasubs_level_id)) {
			$ret[] = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_LEVEL_ID');
		}
		
		if(empty($data->publish_up)) {
			$ret[] = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_UP');
		} else {
			$test = new KDate(new KConfig(array('date'=>$data->publish_up)));
			if($test->format('%Y-%m-%d %H:%i%s') == '0000-00-00') {
				$ret[] = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_UP');
			}
		}

		if(empty($data->publish_down)) {
			$ret[] = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_DOWN');
		} else {
			$test = new KDate(new KConfig(array('date'=>$data->publish_down)));
			if($test->format('%Y-%m-%d %H:%i%s') == '0000-00-00') {
				$ret[] = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PUBLISH_DOWN');
			}
		}
		
		if(empty($data->processor)) {
			$ret[] = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PROCESSOR');
		}
		
		if(empty($data->processor_key)) {
			$ret[] = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_PROCESSOR_KEY');
		}
		
		if(!in_array($data->state, array('N','P','C','X'))) {
			$ret[] = JText::_('COM_AKEEBASUBS_SUBSCRIPTION_ERR_STATE');
		}
				
		return $ret;
	}	
}