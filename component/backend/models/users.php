<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
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

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__akeebasubs_users').' AS '.$db->qn('tbl'))
			->join('INNER', $db->qn('#__users').' AS '.$db->qn('u').' ON '.
				$db->qn('u').'.'.$db->qn('id').' = '.
				$db->qn('tbl').'.'.$db->qn('user_id')
			);

		$this->_buildQueryWhere($query);

		return $query;
	}

	protected function _buildQueryColumns($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		$query->select(array(
			$db->qn('tbl').'.*',
			$db->qn('u').'.'.$db->qn('name'),
			$db->qn('u').'.'.$db->qn('username'),
			$db->qn('u').'.'.$db->qn('email'),
		));

	}

	protected function _buildQueryWhere($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();

		if(is_numeric($state->ordering)) {
			$query->where($db->qn('tbl').'.'.$db->qn('ordering').
				'='.$state->ordering);
		}

		if(is_numeric($state->enabled)) {
			$query->where($db->qn('tbl').'.'.$db->qn('enabled').
				'='.$state->enabled);
		}

		if(is_numeric($state->user_id) && ($state->user_id > 0)) {
			$query->where($db->qn('tbl').'.'.$db->qn('user_id').
				'='.$state->user_id);
		}

		if($state->username) {
			$query->where($db->qn('u').'.'.$db->qn('username').
				' LIKE '.$db->q('%'.$state->username.'%'));
		}

		if($state->name) {
			$query->where($db->qn('u').'.'.$db->qn('name').
				' LIKE '.$db->q('%'.$state->name.'%'));
		}

		if($state->email) {
			$query->where($db->qn('u').'.'.$db->qn('email').
				' LIKE '.$db->q('%'.$state->email.'%'));
		}

		if($state->businessname) {
			$query->where($db->qn('tbl').'.'.$db->qn('businessname').
				' LIKE '.$db->q('%'.$state->businessname.'%'));
		}

		if($state->occupation) {
			$query->where($db->qn('tbl').'.'.$db->qn('occupation').
				' LIKE '.$db->q('%'.$state->occupation.'%'));
		}

		if($state->vatnumber) {
			$query->where($db->qn('tbl').'.'.$db->qn('vatnumber').
				' LIKE '.$db->q('%'.$state->vatnumber.'%'));
		}

		if($state->address1) {
			$query->where($db->qn('tbl').'.'.$db->qn('address1').
				' LIKE '.$db->q('%'.$state->address1.'%'));
		}

		if($state->address2) {
			$query->where($db->qn('tbl').'.'.$db->qn('address2').
				' LIKE '.$db->q('%'.$state->address2.'%'));
		}

		if($state->city) {
			$query->where($db->qn('tbl').'.'.$db->qn('city').
				' LIKE '.$db->q('%'.$state->city.'%'));
		}

		if($state->state) {
			$query->where($db->qn('tbl').'.'.$db->qn('state').
				' LIKE '.$db->q('%'.$state->state.'%'));
		}

		if($state->zip) {
			$query->where($db->qn('tbl').'.'.$db->qn('zip').
				' LIKE '.$db->q('%'.$state->zip.'%'));
		}

		if($state->country) {
			$query->where($db->qn('tbl').'.'.$db->qn('country').
				' = '.$db->q($state->country));
		}

		if($state->search) {
			$search = '%'.$state->search.'%';
			$query->where(
				'('.
				'('.$db->qn('tbl').'.'.$db->qn('businessname').
				' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('tbl').'.'.$db->qn('occupation').
				' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('tbl').'.'.$db->qn('vatnumber').
				' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('tbl').'.'.$db->qn('address1').
				' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('tbl').'.'.$db->qn('address2').
				' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('tbl').'.'.$db->qn('city').
				' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('tbl').'.'.$db->qn('state').
				' LIKE '.$db->q($search).') OR '.
				'('.$db->qn('tbl').'.'.$db->qn('zip').
				' LIKE '.$db->q($search).')'
				.')'
			);
		}
	}

	public function buildQuery($overrideLimits = false) {
		$db = $this->getDbo();
		$query = $db->getQuery(true)
				->from($db->qn('#__akeebasubs_users').' AS '.$db->qn('tbl'))
				->join('INNER', $db->qn('#__users').' AS '.$db->qn('u').' ON '.
					$db->qn('u').'.'.$db->qn('id').' = '.
					$db->qn('tbl').'.'.$db->qn('user_id')
				);

		$this->_buildQueryColumns($query);
		$this->_buildQueryWhere($query);

		$order = $this->getState('filter_order', 'akeebasubs_user_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_user_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);

		return $query;
	}

	public function getMergedData($user_id = null)
	{
		if (is_null($user_id))
		{
			$user_id = $state->user_id;
		}

		$state = $this->getFilterValues();
		// Get a legacy data set from the user parameters
		$userRow = FOFTable::getAnInstance('Juser','AkeebasubsTable');
		$userRow->load($user_id);
		if(!($userRow->params instanceof JRegistry)) {
			JLoader::import('joomla.registry.registry');
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

		if($user_id > 0) {
			$row = FOFModel::getTmpInstance('Users','AkeebasubsModel')
				->user_id($user_id)
				->getFirstItem();
			if($row->user_id == $user_id) {
				$myData = array_merge($myData, $row->getData());
				if(is_string($myData['params'])) {
					$myData['params'] = json_decode($myData['params'], true);
					if(is_null($myData['params'])) $myData['params'] = array();
				}
			}
		} else {
			$myData = array();
		}

		// Finally, merge data coming from the plugins. Note that the
		// plugins only run when a new subscription is in progress, not
		// every time the user data loads.
		JLoader::import('joomla.plugin.helper');
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
		if (!isset($myData['params']))
		{
			$myData['params'] = array();
		}
		$myData['params'] = (object)$myData['params'];

		return (object)$myData;
	}

	/**
	 * Parses a CSV file, importing every row as a new user
	 *
	 * @param   string  $file           Uploaded file
	 * @param   int     $delimiter      Delimiter index, chosen by the user
	 * @param   int     $skipfirst      Should I skip the first row?
	 *
	 * @return bool|int     False if there is an error, otherwise the number of imported users.
	 */
	public function import($file, $delimiter, $skipfirst = 0)
	{
		$delimiters = array(1,2,3);
		$result     = 0;
		$i          = 0;

		if(!$file)
		{
			$this->setError(JText::_('COM_AKEEBASUBS_USERS_IMPORT_ERR_FILE'));
			return false;
		}

		if(!$delimiter || !in_array($delimiter, $delimiters))
		{
			$this->setError(JText::_('COM_AKEEBASUBS_USERS_IMPORT_ERR_DELIMITER'));
			return false;
		}

		// At the moment I don't need the enclosure, it seems that fgetcsv works with or without it
		list($field, ) = $this->decodeDelimiterOptions($delimiter);

		$handle = fopen($file, 'r');
		while (($data = fgetcsv($handle, 0, $field)) !== false)
		{
			$i++;

			// Should I skip first line (ie there are headers in the file)?
			if($skipfirst && $i == 1)
			{
				continue;
			}

			// Perform integrity checks on current line (required fields, existing subscription etc etc)
			$check = $this->performImportChecks($data);
			if(!$check)
			{
				$this->setError(JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i));
				continue;
			}
		}

		fclose($handle);

		return $result;
	}

	/**
	 * Decodes a single value (1,2,3) to an array containing the field delimiter and enclosure
	 *
	 * @param   int     $delimiter
	 *
	 * @return  array    [0] => field delimiter, [1] => enclosure char
	 */
	protected function decodeDelimiterOptions($delimiter)
	{
		if($delimiter == 1)
		{
			return array(',', '');
		}
		elseif($delimiter == 2)
		{
			return array(';', '');
		}
		else
		{
			return array(';', '"');
		}
	}

	/**
	 * Performs checks on current columns got from the CSV, controlling that everything is alright
	 *
	 * @param   array   $data   Columns got from parsing a CSV line
	 *
	 * @return  bool    Is everything alright?
	 */
	protected function performImportChecks(array $data)
	{
		if(count($data) != 32)
		{
			return false;
		}

		if(!$data[0] || !$data[1] || !$data[2] || !$data[3] || !$data[15] || !$data[16])
		{
			return false;
		}

		return true;
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