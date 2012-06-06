<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelUsers extends FOFModel
{
	private function getFilterValues()
	{
		return (object)array(
			'enabled'		=> $this->getState('enabled','','cmd'),
			'ordering'		=> $this->getState('ordering','','int'),
			'user_id'		=> $this->getState('user_id','','int'),
			'groupbydate'	=> $this->getState('groupbydate','','int'),
			'search'		=> $this->getState('search',null,'string'),
			'username'		=> $this->getState('username',null,'string'),
			'name'			=> $this->getState('name',null,'string'),
			'email'			=> $this->getState('email',null,'string'),
			'businessname'	=> $this->getState('businessname',null,'string'),
			'vatnumber'		=> $this->getState('vatnumber',null,'string'),
			'occupation'	=> $this->getState('occupation',null,'string'),
			'isbusiness'	=> $this->getState('isbusiness',null,'int'),
			'viesregistered'=> $this->getState('viesregistered',null,'int'),
			'taxauthority'	=> $this->getState('taxauthority',null,'string'),
			'address1'		=> $this->getState('address1',null,'string'),
			'address2'		=> $this->getState('address2',null,'string'),
			'city'			=> $this->getState('city',null,'string'),
			'state'			=> $this->getState('state',null,'string'),
			'zip'			=> $this->getState('zip',null,'string'),
			'country'		=> $this->getState('country',null,'string')
		);
	}
	
	public function buildCountQuery() {
		$db = $this->getDbo();
		$state = $this->getFilterValues();
		
		$query = FOFQueryAbstract::getNew($db)
			->select('COUNT(*)')
			->from($db->nameQuote('#__akeebasubs_users').' AS '.$db->nameQuote('tbl'))
			->join('INNER', $db->nameQuote('#__users').' AS '.$db->nameQuote('u').' ON '.
				$db->nameQuote('u').'.'.$db->nameQuote('id').' = '.
				$db->nameQuote('tbl').'.'.$db->nameQuote('user_id')
			);

		$this->_buildQueryWhere($query);
		
		return $query;
	}
	
	protected function _buildQueryColumns(FOFQueryAbstract $query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();
		
		$query->select(array(
			$db->nameQuote('tbl').'.*',
			$db->nameQuote('u').'.'.$db->nameQuote('name'),
			$db->nameQuote('u').'.'.$db->nameQuote('username'),
			$db->nameQuote('u').'.'.$db->nameQuote('email'),
		));
		
	}
	
	protected function _buildQueryWhere(FOFQueryAbstract $query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();
		
		if(is_numeric($state->ordering)) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('ordering').
				'='.$state->ordering);
		}
		
		if(is_numeric($state->enabled)) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('enabled').
				'='.$state->enabled);
		}
		
		if(is_numeric($state->user_id) && ($state->user_id > 0)) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('user_id').
				'='.$state->user_id);
		}
		
		if($state->username) {
			$query->where($db->nameQuote('u').'.'.$db->nameQuote('username').
				' LIKE '.$db->quote('%'.$state->username.'%'));
		}
		
		if($state->name) {
			$query->where($db->nameQuote('u').'.'.$db->nameQuote('name').
				' LIKE '.$db->quote('%'.$state->name.'%'));
		}
		
		if($state->email) {
			$query->where($db->nameQuote('u').'.'.$db->nameQuote('email').
				' LIKE '.$db->quote('%'.$state->email.'%'));
		}
		
		if($state->businessname) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('businessname').
				' LIKE '.$db->quote('%'.$state->businessname.'%'));
		}
		
		if($state->occupation) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('occupation').
				' LIKE '.$db->quote('%'.$state->occupation.'%'));
		}
		
		if($state->vatnumber) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('vatnumber').
				' LIKE '.$db->quote('%'.$state->vatnumber.'%'));
		}
		
		if($state->address1) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('address1').
				' LIKE '.$db->quote('%'.$state->address1.'%'));
		}
		
		if($state->address2) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('address2').
				' LIKE '.$db->quote('%'.$state->address2.'%'));
		}
		
		if($state->city) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('city').
				' LIKE '.$db->quote('%'.$state->city.'%'));
		}
		
		if($state->state) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('state').
				' LIKE '.$db->quote('%'.$state->state.'%'));
		}
		
		if($state->zip) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('zip').
				' LIKE '.$db->quote('%'.$state->zip.'%'));
		}
		
		if($state->country) {
			$query->where($db->nameQuote('tbl').'.'.$db->nameQuote('country').
				' = '.$db->quote($state->country));
		}
		
		if($state->search) {
			$search = '%'.$state->search.'%';
			$query->where(
				'('.
				'('.$db->nameQuote('tbl').'.'.$db->nameQuote('businessname').
				' LIKE '.$db->quote($search).') OR '.
				'('.$db->nameQuote('tbl').'.'.$db->nameQuote('occupation').
				' LIKE '.$db->quote($search).') OR '.
				'('.$db->nameQuote('tbl').'.'.$db->nameQuote('vatnumber').
				' LIKE '.$db->quote($search).') OR '.
				'('.$db->nameQuote('tbl').'.'.$db->nameQuote('address1').
				' LIKE '.$db->quote($search).') OR '.
				'('.$db->nameQuote('tbl').'.'.$db->nameQuote('address2').
				' LIKE '.$db->quote($search).') OR '.
				'('.$db->nameQuote('tbl').'.'.$db->nameQuote('city').
				' LIKE '.$db->quote($search).') OR '.
				'('.$db->nameQuote('tbl').'.'.$db->nameQuote('state').
				' LIKE '.$db->quote($search).') OR '.
				'('.$db->nameQuote('tbl').'.'.$db->nameQuote('zip').
				' LIKE '.$db->quote($search).')'
				.')'
			);
		}
	}
	
	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		$query = FOFQueryAbstract::getNew($db)
				->from($db->nameQuote('#__akeebasubs_users').' AS '.$db->nameQuote('tbl'))
				->join('INNER', $db->nameQuote('#__users').' AS '.$db->nameQuote('u').' ON '.
					$db->nameQuote('u').'.'.$db->nameQuote('id').' = '.
					$db->nameQuote('tbl').'.'.$db->nameQuote('user_id')
				);
		
		$this->_buildQueryColumns($query);
		$this->_buildQueryWhere($query);
		
		$order = $this->getState('filter_order', 'akeebasubs_user_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_user_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);
		
		return $query;
	}
	
	public function getMergedData()
	{
		$state = $this->getFilterValues();
		// Get a legacy data set from the user parameters
		$userRow = FOFTable::getAnInstance('Juser','AkeebasubsTable');
		$userRow->load($state->user_id);
		if(!($userRow->params instanceof JRegistry)) {
			jimport('joomla.registry.registry');
			$params = new JRegistry($userRow->params);
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
		
		$userData = $userRow->getData();		
		$myData = $nativeData;
		foreach(array('name','username','email') as $key) $myData[$key] = $userData[$key];
		$myData['email2'] = $userData['email'];
		unset($userData);
				
		if($state->user_id > 0) {
			$row = FOFModel::getTmpInstance('Users','AkeebasubsModel')
				->user_id($state->user_id)
				->getFirstItem();
			if($row->user_id == $state->user_id) {
				$myData = array_merge($myData, $row->getData());
				if(is_string($myData['params'])) {
					$myData['params'] = json_decode($myData['params'], true);
					if(is_null($myData['params'])) $myData['params'] = array();
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
				if(array_key_exists('params', $pResponse)) {
					if(!empty($pResponse['params'])) foreach($pResponse['params'] as $k => $v) {
						$myData['params'][$k] = $v;
					}
					unset($pResponse['params']);
				}
				$myData = array_merge($myData, $pResponse);
			}
		}
		$myData['params'] = (object)$myData['params'];

		return (object)$myData;
	}
	
	protected function onBeforeSave(&$data, &$table)
	{
		if(array_key_exists('custom', $data)) {
			$params = json_encode($data['custom']);
			unset($data['custom']);
			$data['params'] = $params;
		}
		
		return true;
	}
}