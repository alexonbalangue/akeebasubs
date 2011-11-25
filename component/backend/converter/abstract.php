<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die('');

/**
 * Converter abstract code for Akeeba Subscriptions
 * 
 * Very losely based on NinjaBoard importer. Thank you, Stian, for your awesome code!
 *
 * @author Nicholas K. Dionysopoulos
 */

abstract class AkeebasubsConverterAbstract extends JObject implements AkeebasubsConverterInterface
{
	/**
	 * The current data
	 *
	 * @var array
	 */
	public $data = array();
	
	/**
	 * If set to true, then this converter is able to run in steps
	 *
	 * @var bool
	 */
	public $splittable = false;
	
	/**
	 * Array over tructated tables to prevent tables from trunctate twice
	 *
	 * @var array
	 */
	public $truncated = array();
	
	/**
	 * The name of this converter. If not specified, it is the name of the
	 * file.
	 * 
	 * @var string|null
	 */
	protected $convertername = null;
	
	/**
	 * A hash array of input variables, used by FOFInput
	 * 
	 * @var array
	 */
	private $input = null;
	
	/**
	 * The result to send back to the browser (remember to JSON-encode it!)
	 * 
	 * @var array
	 */
	public $result = array();
	
	/**
	 * Public constructor. Makes sure that the name of the converter is set at
	 * all times.
	 * 
	 * @param array|null $properties 
	 */
	public function __construct($properties = null) {
		parent::__construct($properties);
		
		if(is_null($this->convertername)) {
			$this->convertername = basename(__FILE__, '.php');
		}
	}
	
	/**
	 * Checks if the converter can convert
	 *
	 * Usually a check for wether the component is installed or not
	 * Example: JComponentHelper::getComponent( 'com_kunena', true )->enabled
	 *
	 * @return boolean
	 */
	public function canConvert()
	{
		return true;
	}
	
	/**
	 * Gets the name of the converter
	 *
	 * Is used as an identifier for the JS and controller
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->convertername;
	}
	
	/**
	 * Sets the name of the converter
	 *
	 * Is used as an identifier for the JS and controller
	 *
	 * @param $newName string The new name
	 */
	public function setName($newName)
	{
		$this->convertername = $newName;
	}
	
	/**
	 * Execute the convertion
	 *
	 * @return $this
	 */
	public function convert()
	{
		$db = JFactory::getDbo();
		
		// Pump data one table at a time
		foreach($this->data as $table => $rows)
		{
			// Pre-construct an INSERT query prototype for performance reasons
			$tableName = '#__akeebasubs_'.$table;
			$query = FOFQueryAbstract::getNew($db);
			$query->insert($tableName);
			$columns = array();
			
			// Make sure we will only be using valid column names
			$validColumns = array();
			$allFields = $db->getTableFields($tableName, false);
			foreach($allFields[$tableName] as $fn => $fv) {
				$validColumns[] = $fn;
			}
			
			// Loop through all data columns for this table
			$q = null;
			$runningSum = 0;
			foreach($rows as $row)
			{
				// Get a list of columns to insert to this table and pre-load it
				// to the query prototype
				if(empty($columns)) {
					foreach($row as $column => $data) {
						// Filter only valid fields
						if(!in_array($column, $validColumns)) continue;
						// Add valid fields
						$columns[] = $db->nameQuote($column);
					}
					$query->columns($columns);
				}
				
				if(is_null($q)) {
					$q = clone $query;
					$runningSum = 0;
				}
				$values = array();
				foreach($row as $column => $v) {
					if(!in_array($column, $validColumns)) continue;
					$values[] = $db->quote($v);
				}
				$values = implode(',',$values);
				$runningSum += strlen($values);
				$q->values(implode(',',$values));
				
				// Only run a query every 256Kb of data (makes import pimpin' fast!)
				if($runningSum > 262144) {
					$db->setQuery($q);
					$db->query();
					$q = null;
				}
			}
			
			if(!is_null($q)) {
				// Leftover data not commited to db. What are you waiting for,
				// commit them alright!
				$db->setQuery($q);
				$db->query();
			}
		}

		return $this;
	}

	/**
	 * Imports data from a list of foreign tables into this class. Later on,
	 * convert() will use that information to insert new data to the tables.
	 */
	public function importData(array $tables)
	{
		$db = JFactory::getDbo();
		
		$offset = FOFInput::getInt('coffset', -999, $this->input);
		$limit = FOFInput::getInt('climit', -999, $this->input);
		
		if($offset == -999) $offset = false;
		if($limit == -999) $limit = 200;
		
		// Loop through the tables and load the data count or data
		foreach ( $tables as $table )
		{
			$name = $table['name'];
			$query = clone $table['query'];
			$query->from($db->nameQuote($table['foreign']).' AS '.$db->nameQuote('tbl'));

			if($offset === false)
			{
				// When the offset is false, just fetch the number of rows
				$query
					->clear('select')
					->select('COUNT(*)');
				$db->setQuery($query);
				$count = $db->loadResult();
				$this->_truncateTable('#__akeebasubs_'.$name);
				
				if(!isset($this->data[$name]) || $this->data[$name] == array()) {
					$this->data[$name] = $count;
				} else {
					$this->data[$name] += $count;
				}

				continue;
			} else {
				$db->setQuery($query, $offset, $limit);
			}
			if(!isset($this->data[$name]) || $this->data[$name] == array()) {
				$this->data[$name] = $db->loadAssocList($table['foreignkey']);
			} else {
				$rows = $db->loadAssocList($table['foreignkey']);
				foreach($rows as $row)
				{
					$this->data[$name][] = $row;
				}
			}
		}

		if($offset === false) {
			$total = array_reduce($this->data, 'max');
			$steps = ceil($total / $limit);
			if($steps > 1) 
			{
				return array('splittable' => true, 'total' => $total, 'steps' => $steps, 'limit' => $limit);
			}
			else
			{
				foreach ( $tables as $table )
				{
					$name = $table['name'];
					$query = clone $table['query'];
					
					$query->from($db->nameQuote($table['foreign']).' AS '.$db->nameQuote('tbl'));
					$db->setQuery($query, $offset, $limit);

					if($this->data[$name] == array() || is_numeric($this->data[$name])) {
						$this->data[$name] = $db->loadAssocList($table['foreignkey']);
					} else {
						$rows = $db->loadAssocList($table['foreignkey']);
		
						foreach($rows as $row)
						{
							$this->data[$name][] = $row;
						}
					}
				}
				
				return array('splittable' => false);
			}
		} else {
			$step = (int)($offset / $limit);
			return array('splittable' => true, 'step' => $step);
		}
	}

	/**
	 * Truncates tables that data is being imported to
	 */
	protected function _truncateTable($name)
	{
		//If this table have been truncated ebfore, don't truncate it again
		if(isset($this->truncated[$name])) return $this;
		
		$db = JFactory::getDbo();

		$sql = 'TRUNCATE TABLE '.$db->nameQuote($name);

		//Execute the query
		$db->setQuery($sql);
		$db->query();
		
		//Update the truncated array
		$this->truncated[$name] = true;

		return $this;
	}
}