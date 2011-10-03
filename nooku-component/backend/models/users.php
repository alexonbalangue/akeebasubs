<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsModelUsers extends KModelTable
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		$this->_state
			->insert('ordering'	, 'int')
			->insert('enabled'	, 'int')
			->insert('username'	, 'string')
			->insert('name'		, 'string')
			->insert('email'	, 'string')
			->insert('businessname', 'string')
			->insert('vatnumber', 'string')
			// The user_id column is part of a unique index, causing invalid SQL to be output
			// when only searching by user_id. Bummer. I fscked up the data modelling on that
			// table :(
			->remove('user_id')
			->insert('user_id'	, 'int', null, false)
			;
	}
	
	protected function _buildQueryJoins(KDatabaseQuery $query)
	{
		if($this->_state->groupbydate == 1) return;
		$query
			->join('INNER', 'users AS u', 'u.id = tbl.user_id');
	}
	
	protected function _buildQueryColumns(KDatabaseQuery $query)
	{
		$query->select(array(
			'tbl.*',
			'u.name',
			'u.username',
			'u.email'
		));
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

		if(is_numeric($state->user_id) && ($state->user_id > 0)) {
			$query->where('tbl.user_id','=',$state->user_id);
		}
		
		if($state->username) {
			$query->where('u.username', 'LIKE',  '%'.$state->username.'%');
		}
		
		if($state->name) {
			$query->where('u.name', 'LIKE',  '%'.$state->name.'%');
		}
		
		if($state->email) {
			$query->where('u.email', 'LIKE',  '%'.$state->email.'%');
		}
		
		if($state->businessname) {
			$query->where('tbl.businessname', 'LIKE',  '%'.$state->businessname.'%');
		}
		
		if($state->vatnumber) {
			$query->where('tbl.vatnumber', 'LIKE',  '%'.$state->vatnumber.'%');
		}
		
		if($state->search)
		{
			$search = '%'.$state->search.'%';
			$query->where('tbl.businessname', 'LIKE',  $search, 'OR');
			$query->where('tbl.occupation', 'LIKE',  $search, 'OR');
			$query->where('tbl.vatnumber', 'LIKE',  $search, 'OR');
			$query->where('tbl.address1', 'LIKE',  $search, 'OR');
			$query->where('tbl.address2', 'LIKE',  $search, 'OR');
			$query->where('tbl.city', 'LIKE',  $search, 'OR');
			$query->where('tbl.state', 'LIKE',  $search, 'OR');
			$query->where('tbl.zip', 'LIKE',  $search, 'OR');
		}
		
		parent::_buildQueryWhere($query);
	}
	
	public function getMergedData()
	{
		// Get a legacy data set from the user parameters
		$userRow = KFactory::get('com://admin/akeebasubs.model.jusers')->id($this->_state->user_id)->getItem();
		if(!($userRow->params instanceof JRegistry)) {
			$params = new JParameter($userRow->params);
		} else {
			$params = $userRow->params;
		}
		$businessname = $params->get('business_name','');
		$nativeData = array(
			'isbusiness' => empty($businessname) ? 0 : 1,
			'businessname' => $params->get('business_name',''),
			'occupation' => $params->get('occupation',''),
			'vatnumber' => $params->get('vat_number',''),
			'viesregistered' => 0,
			'taxauthority' => '',
			'address1' => $params->get('address',''),
			'address2' => $params->get('address2',''),
			'city' => $params->get('city',''),
			'state' => $params->get('state',''),
			'zip' => $params->get('zip',''),
			'country' => $params->get('country',''),
			'params' => array()
		);
		
		$nativeData = array_merge($nativeData, $userRow->getData());
		$myData = $nativeData;
				
		if($this->_state->user_id > 0) {
			$rows = KFactory::get('com://admin/akeebasubs.model.users')
				->user_id($this->_state->user_id)
				->getList();
			if($rows instanceof KDatabaseRowsetInterface) {
				$plainData = $rows->getData();
				if(!empty($plainData)) {
					$row = array_shift($plainData);
					unset($plainData);
					if($row['user_id'] == $this->_state->user_id) {
						$myData = array_merge($nativeData, $row);
						if(is_string($myData['params'])) {
							$myData['params'] = json_decode($myData['params']);
							if(is_null($myData['params'])) $myData['params'] = array();
						}
					}
				}
			}
			
			// Finally, merge data coming from the plugins. Note that the
			// plugins only run when a new subscription is in progress, not
			// every time the user data loads.
			jimport('joomla.plugin.helper');
			JPluginHelper::importPlugin('akeebasubs');
			$app = JFactory::getApplication();
			$jResponse = $app->triggerEvent('onAKUserGetData', array((object)$myData));
			if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $pResponse) {
				if(!is_array($pResponse)) continue;
				if(empty($pResponse)) continue;
				$myData = array_merge($myData, $pResponse);
			}
		}
		
		return (object)$myData;
	}
}