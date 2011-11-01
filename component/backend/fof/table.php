<?php
/**
 *  @package FrameworkOnFramework
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

jimport('joomla.database.table');

require_once(dirname(__FILE__).'/input.php');

/**
 * FrameworkOnFramework table class
 * 
 * FrameworkOnFramework is a set of classes whcih extend Joomla! 1.5 and later's
 * MVC framework with features making maintaining complex software much easier,
 * without tedious repetitive copying of the same code over and over again.
 */
class FOFTable extends JTable
{
	public static function &getAnInstance($type = null, $prefix = 'JTable', $config = array())
	{
		static $instances = array();
		
		// Guess the component name
		if(array_key_exists('input', $config)) {
			$option = FOFInput::getCmd('option','',$config['input']);
			FOFInput::setVar('option',$option,$config['input']);
		}
		
		if(!in_array($prefix,array('Table','JTable'))) {
			preg_match('/(.*)Table$/', $prefix, $m);
			$option = 'com_'.strtolower($m[1]);
		}

		if(array_key_exists('option', $config)) $option = $config['option'];
		$config['option'] = $option;
		
		if(!array_key_exists('view', $config)) $config['view'] = JRequest::getCmd('view','cpanel');
		if(is_null($type)) {
			if($prefix == 'JTable') $prefix = 'Table';
			$type = $config['view'];
		}
		
		$type = preg_replace('/[^A-Z0-9_\.-]/i', '', $type);
		$tableClass = $prefix.ucfirst($type);
		
		if(!array_key_exists($tableClass, $instances)) {
			if (!class_exists( $tableClass )) {
				$app = JFactory::getApplication();
				if($app->isSite()) {
					$basePath = JPATH_SITE;
				} else {
					$basePath = JPATH_ADMINISTRATOR;
				}
				
				$searchPaths = array(
					$basePath.'/components/'.$config['option'].'/tables',
					JPATH_ADMINISTRATOR.'/components/'.$config['option'].'/tables'
				);
				if(array_key_exists('tablepath', $config)) {
					array_unshift($searchPaths, $config['tablepath']);
				}
				
				jimport('joomla.filesystem.path');
				$path = JPath::find(
					$searchPaths,
					strtolower($type).'.php'
				);
				
				if ($path) {
					require_once $path;
				}
			}
			
			if (!class_exists( $tableClass )) {
				$tableClass = 'FOFTable';
			}
			
			$tbl_common = str_replace('com_', '', $config['option']).'_';
			if(!array_key_exists('tbl', $config)) {
				$config['tbl'] = strtolower('#__'.$tbl_common.strtolower(FOFInflector::pluralize($type)));
			}
			if(!array_key_exists('tbl_key', $config)) {
				$keyName = FOFInflector::singularize($type);
				$config['tbl_key'] = strtolower($tbl_common.$keyName.'_id');
			}
			if(!array_key_exists('db', $config)) {
				$config['db'] = JFactory::getDBO();
			}

			$instance = new $tableClass($config['tbl'],$config['tbl_key'],$config['db']);

			$instances[$tableClass] = $instance;
		}
		
		return $instances[$tableClass];
	}
	
	function __construct( $table, $key, &$db )
	{
		$this->_tbl		= $table;
		$this->_tbl_key	= $key;
		$this->_db		= &$db;
		
		// Initialise the table properties.
		if ($fields = $this->getTableFields()) {
			foreach ($fields as $name => $v)
			{
				// Add the field if it is not already present.
				if (!property_exists($this, $name)) {
					$this->$name = null;
				}
			}
		}
		
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			// If we are tracking assets, make sure an access field exists and initially set the default.
			if (property_exists($this, 'asset_id')) {
				jimport('joomla.access.rules');
				$this->_trackAssets = true;
			}

			// If the acess property exists, set the default.
			if (property_exists($this, 'access')) {
				$this->access = (int) JFactory::getConfig()->get('access');
			}
		}
	}
	
	/**
	 * Method to reset class properties to the defaults set in the class
	 * definition. It will ignore the primary key as well as any private class
	 * properties.
	 */
	public function reset()
	{
		if(!$this->onBeforeReset()) return false;
		// Get the default values for the class from the table.
		$fields = $this->getTableFields();
		foreach ($fields as $k => $v)
		{
			// If the property is not the primary key or private, reset it.
			if ($k != $this->_tbl_key && (strpos($k, '_') !== 0)) {
				$this->$k = $v->Default;
			}
		}
		if(!$this->onAfterReset()) return false;
	}
	
	/**
	 * Generic check for whether dependancies exist for this object in the db schema
	 */
	public function canDelete( $oid=null, $joins=null )
	{
		$k = $this->_tbl_key;
		if ($oid) {
			$this->$k = intval( $oid );
		}

		if (is_array( $joins ))
		{
			$db = $this->_db;
			$query = FOFQueryAbstract::getNew($this->_db)
				->select($db->nameQuote('master').'.'.$db->nameQuote($k))
				->from($db->nameQuote($this->_tbl).' AS '.$db->nameQuote('master'));
			$tableNo = 0;
			foreach( $joins as $table )
			{
				$tableNo++;
				$query->select(array(
					'COUNT(DISTINCT '.$db->nameQuote('t'.$tableNo).'.'.$db->nameQuote($table['idfield']).') AS '.$db->nameQuote($table['idalias'])
				));
				$query->join('LEFT', 
						$db->nameQuote($table['name']).
						' AS '.$db->nameQuote('t'.$tableNo).
						' ON '.$db->nameQuote('t'.$tableNo).'.'.$db->nameQuote($table['joinfield']).
						' = '.$db->nameQuote('master').'.'.$db->nameQuote($k)
						);
			}

			$query->where($db->nameQuote('master').'.'.$db->nameQuote($k).' = '.$db->quote($this->$k));
			$query->group($db->nameQuote('master').'.'.$db->nameQuote($k));
			$this->_db->setQuery( (string)$query );

			if (!$obj = $this->_db->loadObject())
			{
				$this->setError($this->_db->getErrorMsg());
				return false;
			}
			$msg = array();
			$i = 0;
			foreach( $joins as $table )
			{
				$k = $table['idalias'];
				if ($obj->$k > 0)
				{
					$msg[] = JText::_( $table['label'] );
				}
				$i++;
			}

			if (count( $msg ))
			{
				$option = FOFInput::getCmd('option','com_foobar',$this->input);
				$comName = str_replace('com_','',$option);
				$tview = str_replace('#__'.$comName.'_', '', $this->_tbl);
				$prefix = $option.'_'.$tview.'_NODELETE_';
				
				foreach($msg as $key) {
					$this->setError(JText::_($prefix.$key));
				}
				return false;
			}
			else
			{
				return true;
			}
		}

		return true;
	}
	
	public function bind( $from, $ignore=array() )
	{
		if(!$this->onBeforeBind($from)) return false;
		return parent::bind($from, $ignore);
	}
	
	public function load( $oid=null )
	{
		$result = parent::load($oid);
		$this->onAfterLoad($result);
		return $result;
	}
	
	public function store( $updateNulls=false )
	{
		if(!$this->onBeforeStore($updateNulls)) return false;
		$result = parent::store($updateNulls);
		if($result) {
			$result = $this->onAfterStore();
		}
		return $result;
	}
	
	public function move( $dirn, $where='' )
	{
		if(!$this->onBeforeMove($dirn, $where)) return false;
		$result = parent::move($dirn, $where);
		if($result) {
			$result = $this->onAfterMove();
		}
		return $result;
	}
	
	public function reorder( $where='' )
	{
		if(!$this->onBeforeReorder($where)) return false;
		$result = parent::reorder($where);
		if($result) {
			$result = $this->onAfterReorder();
		}
		return $result;
	}
	
	public function checkout( $who, $oid = null )
	{
		if (!(
			in_array( 'locked_by', array_keys($this->getProperties()) ) ||
	 		in_array( 'locked_on', array_keys($this->getProperties()) )
		)) {
			return true;
		}

		$k = $this->_tbl_key;
		if ($oid !== null) {
			$this->$k = $oid;
		}

		$date =& JFactory::getDate();
		$time = $date->toMysql();
		
		$query = FOFQueryAbstract::getNew($this->_db)
				->update($this->_db->nameQuote( $this->_tbl ))
				->set(array(
					$this->_db->nameQuote('locked_by').' = '.(int)$who,
					$this->_db->nameQuote('locked_on').' = '.$this->_db->quote($time)
				))
				->where($this->_db->nameQuote($this->_tbl_key).' = '. $this->_db->quote($this->$k));
		$this->_db->setQuery( (string)$query );

		$this->checked_out = $who;
		$this->checked_out_time = $time;

		return $this->_db->query();
	}
	
	function checkin( $oid=null )
	{
		if (!(
			in_array( 'locked_by', array_keys($this->getProperties()) ) ||
	 		in_array( 'locked_on', array_keys($this->getProperties()) )
		)) {
			return true;
		}

		$k = $this->_tbl_key;

		if ($oid !== null) {
			$this->$k = $oid;
		}

		if ($this->$k == NULL) {
			return false;
		}

		$query = FOFQueryAbstract::getNew($this->_db)
				->update($this->_db->nameQuote( $this->_tbl ))
				->set(array(
					$this->_db->nameQuote('locked_by').' = 0',
					$this->_db->nameQuote('locked_on').' = '.$this->_db->quote($this->_db->getNullDate())
				))
				->where($this->_db->nameQuote($this->_tbl_key).' = '. $this->_db->quote($this->$k));
		$this->_db->setQuery( (string)$query );

		$this->checked_out = 0;
		$this->checked_out_time = '';

		return $this->_db->query();
	}
	
	function isCheckedOut( $with = 0, $against = null)
	{
		if(isset($this) && is_a($this, 'JTable') && is_null($against)) {
			$against = $this->get( 'locked_by' );
		}

		//item is not checked out, or being checked out by the same user
		if (!$against || $against == $with) {
			return  false;
		}

		$session =& JTable::getInstance('session');
		return $session->exists($against);
	}
	
	function publish( $cid=null, $publish=1, $user_id=0 )
	{
		JArrayHelper::toInteger( $cid );
		$user_id	= (int) $user_id;
		$publish	= (int) $publish;
		$k			= $this->_tbl_key;

		if (count( $cid ) < 1)
		{
			if ($this->$k) {
				$cid = array( $this->$k );
			} else {
				$this->setError("No items selected.");
				return false;
			}
		}
		
		if(!$this->onBeforePublish($cid, $publish)) return false;
		
		$query = FOFQueryAbstract::getNew($this->_db)
				->update($this->_db->nameQuote($this->_tbl))
				->set($this->_db->nameQuote('enabled').' = '.(int) $publish);

		$checkin = in_array( 'locked_by', array_keys($this->getProperties()) );
		if ($checkin)
		{
			$query->where(
				' ('.$this->_db->nameQuote('locked_by').
				' = 0 OR '.$this->_db->nameQuote('locked_by').' = '.(int) $user_id.')',
				'AND'
			);
		}
		
		$cids = $this->_db->nameQuote($k).' = ' .
				implode(' OR '.$this->_db->nameQuote($k).' = ',$cid);
		$query->where('('.$cids.')');
		
		$this->_db->setQuery( (string)$query );
		if (!$this->_db->query())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		if (count( $cid ) == 1 && $checkin)
		{
			if ($this->_db->getAffectedRows() == 1) {
				$this->checkin( $cid[0] );
				if ($this->$k == $cid[0]) {
					$this->published = $publish;
				}
			}
		}
		$this->setError('');
		return true;
	}
	
	public function delete( $oid=null )
	{
		if(!$this->onBeforeDelete($oid)) return false;
		$result = parent::delete($oid);
		if($result) {
			$result = $this->onAfterDelete();
		}
		return $result;
	}
	
	public function hit( $oid=null, $log=false )
	{
		if(!$this->onBeforeHit($oid, $log)) return false;
		$result = parent::hit($oid, $log);
		if($result) {
			$result = $this->onAfterHit();
		}
		return $result;
	}
	
	/**
	 * Export item list to CSV
	 */
	function toCSV($separator=',')
	{
		$csv = array();

		foreach (get_object_vars( $this ) as $k => $v)
		{
			if (is_array($v) or is_object($v) or $v === NULL)
			{
				continue;
			}
			if ($k[0] == '_')
			{ // internal field
				continue;
			}
			$csv[] = '"'.str_replace('"', '""', $v).'"';
		}
		$csv = implode($separator, $csv);

		return $csv;
	}
	
	/**
	 * Exports the table in array format
	 */
	function getData()
	{
		$ret = array();

		foreach (get_object_vars( $this ) as $k => $v)
		{
			if( ($k[0] == '_') || ($k[0] == '*'))
			{ // internal field
				continue;
			}
			$ret[$k] = $v;
		}

		return $ret;
	}
	
	/**
	 * Get the header for exporting item list to CSV
	 */
	function getCSVHeader($separator=',')
	{
		$csv = array();

		foreach (get_object_vars( $this ) as $k => $v)
		{
			if (is_array($v) or is_object($v) or $v === NULL)
			{
				continue;
			}
			if ($k[0] == '_')
			{ // internal field
				continue;
			}
			$csv[] = '"'.str_replace('"', '\"', $k).'"';
		}
		$csv = implode($separator, $csv);

		return $csv;
	}
	
	/**
	 * Get the columns from database table.
	 *
	 * @return  mixed  An array of the field names, or false if an error occurs.
	 */
	public function getTableFields()
	{
		static $cache = array();

		if(!array_key_exists($this->_tbl, $cache)) {
			// Lookup the fields for this table only once.
			$name	= $this->_tbl;
			$fields	= $this->_db->getTableFields($name, false);

			if (!isset($fields[$name])) {
				return false;
			}
			$cache[$this->_tbl] = $fields[$name];
		}

		return $cache[$this->_tbl];
	}
	
	protected function onBeforeBind(&$from)
	{
		return true;
	}
	
	protected function onAfterLoad(&$result)
	{
	}
	
	protected function onBeforeStore($updateNulls)
	{
		// Do we have a "Created" set of fields?
		if(property_exists($this, 'created_on') && property_exists($this, 'created_by') && $updateNulls) {
			if(empty($this->created_by) || ($this->created_on == '0000-00-00 00:00:00') || empty($this->create_on)) {
				$this->created_by = JFactory::getUser()->id;
				jimport('joomla.utilities.date');
				$date = new JDate();
				$this->created_on = $date->toMySQL();
			} elseif(property_exists($this, 'modified_on') && property_exists($this, 'modified_by')) {
				$this->modified_by = JFactory::getUser()->id;
				jimport('joomla.utilities.date');
				$date = new JDate();
				$this->modified_on = $date->toMySQL();
			}
		}
		
		return true;
	}
	
	protected function onAfterStore()
	{
		return true;
	}
	
	protected function onBeforeMove($updateNulls)
	{
		return true;
	}
	
	protected function onAfterMove()
	{
		return true;
	}
	
	protected function onBeforeReorder($where = '')
	{
		return true;
	}
	
	protected function onAfterReorder()
	{
		return true;
	}
	
	protected function onBeforeDelete($oid)
	{
		return true;
	}
	
	protected function onAfterDelete()
	{
		return true;
	}
	
	protected function onBeforeHit($oid, $log)
	{
		return true;
	}
	
	protected function onAfterHit()
	{
		return true;
	}
	
	protected function onBeforePublish(&$cid, $publish)
	{
		return true;
	}
	
	protected function onAfterReset()
	{
		return true;
	}
	
	protected function onBeforeReset()
	{
		return true;
	}
}