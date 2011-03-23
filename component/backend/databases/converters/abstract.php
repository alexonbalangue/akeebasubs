<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

abstract class ComAkeebasubsDatabaseConvertersAbstract extends KObject implements ComAkeebasubsDatabaseConvertersInterface, KObjectIdentifiable
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
	 * Get the object identifier
	 * 
	 * @return	KIdentifier	
	 * @see 	KObjectIdentifiable
	 */
	public function getIdentifier()
	{
		return $this->_identifier;
	}

	/**
	 * Execute the convertion
	 *
	 * @return $this
	 */
	public function convert()
	{
		$dbprefix = KFactory::get('lib.joomla.config')->getValue('dbprefix');
		$identifier = new KIdentifier('admin::com.akeebasubs.database.table.default');

		foreach($this->data as $this->name => $rows)
		{
			$identifier->name = $this->name;
			$table = KFactory::get($identifier);
			$table = KFactory::get('admin::com.akeebasubs.database.table.'.$this->name);
			
			$offset = KRequest::get('post.offset', 'int', -999);
			if($offset == -999) $offset = KRequest::get('get.offset', 'int', 0);
			if($offset < 1) $this->_truncateTable($table);
			
			foreach($rows as $row)
			{
				//Filter the data and remove unwanted columns
				$data = $table->filter($row, true);
				
				//Get the data and apply the column mappings
				$data = $table->mapColumns($data);
				
				$table->getDatabase()->insert($table->getBase(), KConfig::toData($data));
			}
		}

		return $this;
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
		return $this->getIdentifier()->name;
	}

	/**
	 * Truncates tables that data is being imported to
	 */
	protected function _truncateTable(KDatabaseTableAbstract $table)
	{
		$name = $table->getBase();

		//If this table have been truncated ebfore, don't truncate it again
		if(isset($this->truncated[$name])) return $this;

		$sql = 'TRUNCATE TABLE '.$table->getDatabase()->quoteName('#__'.$name);

		//Execute the query
		$table->getDatabase()->execute($sql);
		
		//Update the truncated array
		$this->truncated[$name] = true;

		return $this;
	}
	
	/**
	 * Imports data
	 *
	 * Used by splittable importers
	 */
	public function importData(array $tables)
	{
		$offset = KRequest::get('post.offset', 'int', -999);
		if($offset == -999) $offset = KRequest::get('get.offset', 'int', false);
		
		$limit = KRequest::get('post.limit', 'int', -999);
		if($limit == -999) $limit = KRequest::get('get.limit', 'int', 200);
		
		foreach ( $tables as $table )
		{
			$name = $table['name'];
			$query = clone $table['query'];

			if($offset === false)
			{
				if(!isset($this->data[$name]) || $this->data[$name] == array()) {
					$this->data[$name] = (int)KFactory::get('admin::com.default.database.table.'.$name.$table['options']['name'], $table['options'])->count(clone $query);
				} else {
					$this->data[$name] += (int)KFactory::get('admin::com.default.database.table.'.$name.$table['options']['name'], $table['options'])->count(clone $query);
				}

				continue;
			}
			elseif ($offset !== false)
			{
				$query->limit($limit, $offset);
			}
			
			if(!isset($this->data[$name]) || $this->data[$name] == array()) {
				$this->data[$name] = KFactory::get('admin::com.default.database.table.'.$table['options']['name'], $table['options'])
										->select($query, KDatabase::FETCH_ROWSET)
										->getData();
			} else {
				$rows = KFactory::get('admin::com.default.database.table.'.$name.$table['options']['name'], $table['options'])
							->select($query, KDatabase::FETCH_ROWSET)
							->getData();

				foreach($rows as $row)
				{
					$this->data[$name][] = $row;
				}
			}
			
		}

		if($offset === false) {
			$total = array_reduce($this->data, 'max');
			$steps = floor($total / $limit);
			if($steps > 0) 
			{
				echo json_encode(array('splittable' => true, 'total' => $total, 'steps' => $steps, 'limit' => $limit));
				return false;
			}
			else
			{
				echo json_encode(array('splittable' => false));
				foreach ( $tables as $table )
				{
					$name = $table['name'];
					$query = clone $table['query'];

					if($this->data[$name] == array() || is_numeric($this->data[$name])) {
						$this->data[$name] = KFactory::get('admin::com.default.database.table.'.$name.$table['options']['name'], $table['options'])
												->select($query, KDatabase::FETCH_ROWSET)
												->getData();
					} else {
						$rows = KFactory::get('admin::com.default.database.table.'.$name.$table['options']['name'], $table['options'])
									->select($query, KDatabase::FETCH_ROWSET)
									->getData();
		
						foreach($rows as $row)
						{
							$this->data[$name][] = $row;
						}
					}
				}
			}
		} else {
			$step = (int)($offset / $limit);
			echo json_encode(array('splittable' => true, 'step' => $step));
		}
	}
}