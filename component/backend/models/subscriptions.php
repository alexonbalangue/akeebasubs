<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

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
			->insert('since'			, 'date')
			->insert('contact_flag'		, 'int')
			->insert('expires_from'		, 'date')
			->insert('expires_to'		, 'date')
			// When refresh=1 we run a GROUP BY query which returns user ID's linked to subscriptions
			->insert('refresh'			, 'int')
			// Nooku does some funky stuff with the offset when the limit changes. I don't want it to happen!
			->insert('forceoffset'		, 'int')
			->insert('forcelimit'		, 'int')
			;
	}
	
	public function getTotal()
	{
		if($this->_state->refresh == 1) {
			// When we're running a refresh, we must specialise this method in
			// order to take the GROUP BY clause into account
			if (!isset($this->_total)) {
				if($table = $this->getTable()) {
					$query = $table->getDatabase()->getQuery()->count();
					
					$this->_buildQueryFrom($query);
					$this->_buildQueryJoins($query);
					$this->_buildQueryWhere($query);
					$this->_buildQueryGroup($query);
					
					// $query retruns X rows, where X is the number of users. We need the count of users, so...
					$query2 = $table->getDatabase()->getQuery()->count();
					// Ugly, ugly, ugly hack...
					$query2->from = array( '('.(string)$query.') AS `tbl`' );

					$total = $table->count($query2);
					
					$this->_total = $total;
				}
			}
		}
		return parent::getTotal();
	}
	
	protected function _buildQueryJoins(KDatabaseQuery $query)
	{
		$query
			->join('INNER', 'akeebasubs_levels AS l', 'l.akeebasubs_level_id = tbl.akeebasubs_level_id')
			->join('INNER', 'users AS u', 'u.id = tbl.user_id')
			->join('LEFT OUTER', 'akeebasubs_users AS a', 'a.user_id = tbl.user_id');
	}
	
	protected function _buildQueryLimit(KDatabaseQuery $query)
	{
		if($this->_state->refresh == 1) {
			$limit = ($this->_state->forcelimit > 0) ? $this->_state->forcelimit : $this->_state->limit;
			$offset = ($this->_state->forceoffset > 0) ? $this->_state->forceoffset : $this->_state->offset;
			
			if($limit) {
				$query->limit($limit, $offset);
			}
		} else {
			parent::_buildQueryLimit($query);
		}
	
	}	
	
	protected function _buildQueryColumns(KDatabaseQuery $query)
	{
		if($this->_state->refresh == 1) {
			$query->select(array('tbl.akeebasubs_subscription_id', 'tbl.user_id'));
		} else {
			$query->select(array(
				'tbl.*',
				'l.title',
				'l.image',
				'u.name',
				'u.username',
				'u.email',
				'u.block',
				'a.isbusiness',
				'a.businessname',
				'a.occupation',
				'a.vatnumber',
				'a.viesregistered',
				'a.taxauthority',
				'a.address1',
				'a.address2',
				'a.city',
				'a.state AS userstate',
				'a.zip',
				'a.country',
				'a.params AS userparams',
				'a.notes AS usernotes'
			));
		}
	}
	
	protected function _buildQueryGroup(KDatabaseQuery $query)
	{
		if($this->_state->refresh == 1) {
			$query->group(array('tbl.user_id'));
		}
	}

	protected function _buildQueryWhere(KDatabaseQuery $query)
	{
		$state = $this->_state;
		
		if($state->refresh == 1) {
			return;
		}

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
		
		if(is_numeric($state->contact_flag)) {
			$query->where('tbl.contact_flag', '=', $state->contact_flag);
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
			$query->where('tbl.publish_up','>=',$from);
			$query->where('tbl.publish_up','<=',$to);
		} elseif(!empty($from) && empty($to)) {
			// Filter after date
			$query->where('tbl.publish_up','>=',$from);
		} elseif(empty($from) && !empty($to)) {
			// Filter up to a date
			$query->where('tbl.publish_down','<=',$to);
		}
		
		// "Since" queries
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
			$query->where('tbl.created_on','>=',$since);
		}
		
		// Expiration control queries
		jimport('joomla.utilities.date');
		$from = trim($state->expires_from);
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
		
		$to = trim($state->expires_to);
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
			$query->where('tbl.publish_down','>=',$from,'AND');
			$query->where('tbl.publish_down','<=',$to,'AND');
		} elseif(!empty($from) && empty($to)) {
			// Filter after date
			$query->where('tbl.publish_down','>=',$from);
		} elseif(empty($from) && !empty($to)) {
			// Filter up to a date
			$query->where('tbl.publish_down','<=',$to);
		}
		
		parent::_buildQueryWhere($query);
	}
	
	public function validate($data)
	{
		$ret = array();
		
		if($data->_visual != 1) return $ret;
		
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