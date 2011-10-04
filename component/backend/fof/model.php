<?php
/**
 *  @package AkeebaSubs
 *  @subpackage FrameworkOnFramework
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

jimport('joomla.application.component.controller');

require_once(dirname(__FILE__).'/input.php');

/**
 * FrameworkOnFramework model class
 * 
 * FrameworkOnFramework is a set of classes whcih extend Joomla! 1.5 and later's
 * MVC framework with features making maintaining complex software much easier,
 * without tedious repetitive copying of the same code over and over again.
 */
class FOFModel extends JModel
{
	protected $table = null;
	protected $otable = null;

	protected $id_list = array();
	protected $id = null;
	protected $record = null;
	protected $list = null;

	protected $pagination = null;
	protected $total = 0;
	
	protected $input = array();

	public static function &getAnInstance( $type, $prefix = '', $config = array() )
	{
		$type		= preg_replace('/[^A-Z0-9_\.-]/i', '', $type);
		$modelClass	= $prefix.ucfirst($type);
		$result		= false;

		if (!class_exists( $modelClass ))
		{
			jimport('joomla.filesystem.path');
			$path = JPath::find(
				JModel::addIncludePath(),
				JModel::_createFileName( 'model', array( 'name' => $type))
			);
			if ($path)
			{
				require_once $path;
			}
		}

		if (!class_exists( $modelClass )) {
			$config_extras = array(
				'name'	=> $type
			);
			$config = array_merge($config_extras, $config);
			$modelClass = 'FOFModel';
		}
		
		$result = new $modelClass($config);
		
		return $result;
	}
	
	public function __construct($config = array())
	{
		parent::__construct($config);

		// Get the input
		$this->input = JRequest::get('default', 3);
		if(array_key_exists('input', $config)) {
			$this->input = array_merge($this->input, $config['input']);
		}
		
		// Set the $name/$_name variable
		$component = FOFInput::getCmd('option','com_foobar',$this->input);
		if(array_key_exists('option', $config)) $component = $config['option'];
		FOFInput::setVar('option', $component, $this->input);
		$name = str_replace('com_', '', strtolower($component));
		if(array_key_exists('name', $config)) $name = $config['name'];
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$this->name = $name;
		} else {
			$this->_name = $name;
		}

		// Assign the correct table
		if(array_key_exists('table',$config)) {
			$this->table = $config['table'];
		} else {
			$className = get_class($this);
			if($className == 'FOFModel') {
				$view = FOFInput::getCmd('view','cpanel',$this->input);
			} else {
				$eliminatePart = ucfirst($name).'Model';
				$view = strtolower(str_replace($eliminatePart, '', $className));
			}
			$this->table = FOFInflector::singularize($view);
		}
		
		// Get and store the pagination request variables
		$app = JFactory::getApplication();
		$limit = $this->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
		$limitstart = $this->getUserStateFromRequest(JRequest::getCmd('option','com_ars').$this->getName().'limitstart','limitstart',0);
		$this->setState('limit',$limit);
		$this->setState('limitstart',$limitstart);

		// Get the ID or list of IDs from the request or the configuration
		if(array_key_exists('cid', $config)) {
			$cid = $config['cid'];
		} else {
			$cid = FOFInput::getVar('cid', null, $this->input, 'array');
		}
		if(array_key_exists('id', $config)) {
			$id = $config['id'];
		} else {
			$id = FOFInput::getInt('id', 0, $this->input);
		}

		if(is_array($cid) && !empty($cid))
		{
			$this->setIds($cid);
		}
		else
		{
			$this->setId($id);
		}
	}

	/**
	 * Sets the list of IDs from the request data
	 */
	public function setIDsFromRequest()
	{
		// Get the ID or list of IDs from the request or the configuration
		$cid = FOFInput::getVar('cid', null, $this->input, 'array');
		$id = FOFInput::getInt('id', 0, $this->input);

		if(is_array($cid) && !empty($cid))
		{
			$this->setIds($cid);
		}
		else
		{
			$this->setId($id);
		}
	}

	/**
	 * Sets the ID and resets internal data
	 * @param int $id The ID to use
	 */
	public final function setId($id=0)
	{
		$this->reset();
		$this->id = (int)$id;
		$this->id_list = array($this->id);
	}

	/**
	 * Returns the currently set ID
	 * @return int
	 */
	public final function getId()
	{
		return $this->id;
	}

	/**
	 * Sets a list of IDs for batch operations from an array and resets the model
	 */
	public final function setIds($idlist)
	{
		$this->reset();
		$this->id_list = array();
		$this->id = 0;
		if(is_array($idlist) && !empty($idlist)) {
			foreach($idlist as $value)
			{
				$this->id_list[] = (int)$value;
			}
			$this->id = $this->id_list[0];
		}
	}

	/**
	 * Returns the list of IDs for batch operations
	 * @return array An array of integers
	 */
	public final function getIds()
	{
		return $this->id_list;
	}

	/**
	 * Resets the model, like it was freshly loaded
	 */
	public function reset()
	{
		$this->id = 0;
		$this->id_list = null;
		$this->record = null;
		$this->list = null;
		$this->pagination = null;
		$this->total = 0;
		$this->otable = null;
		
		return $this;
	}

	/**
	 * Returns a single item. It uses the id set with setId, or the first ID in
	 * the list of IDs for batch operations
	 * @return JTable A copy of the item's JTable array
	 */
	public final function &getItem()
	{
		if(empty($this->record))
		{
			$table = $this->getTable($this->table);
			$table->load($this->id);
			$this->record = $table;

			// Do we have saved data?
			$session = JFactory::getSession();
			$serialized = $session->get($this->getHash().'savedata', null);
			if(!empty($serialized))
			{
				$data = @unserialize($serialized);
				if($data !== false)
				{
					$this->record->bind($data);
				}
			}

			$this->onAfterGetItem($this->record);
		}

		return $this->record;
	}

	/**
	 * Alias for getItemList
	 */
	public final function &getList($overrideLimits = false)
	{
		return $this->getItemList($overrideLimits);
	}
	
	/**
	 * Returns a list of items
	 * @param bool $overrideLimits When true, the limits (pagination) will be ignored
	 * @return array
	 */
	public final function &getItemList($overrideLimits = false)
	{
		if(empty($this->list)) {
			$table = $this->getTable($this->table);
			$source = $table->getTableName();

			$limitstart = $this->getState('limitstart');
			$limit = $this->getState('limit');

			$query = $this->buildQuery($overrideLimits);
			if(!$overrideLimits)
				$this->list = $this->_getList((string)$query, $limitstart, $limit);
			else
				$this->list = $this->_getList((string)$query);
		}

		return $this->list;
	}

	/**
	 * Binds the data to the model and tries to save it
	 * @param array|object $data The source data array or object
	 * @return bool True on success
	 */
	public function save($data)
	{
		$this->otable = null;

		$table = $this->getTable($this->table);

		$key = $table->getKeyName();
		if(array_key_exists($key, (array)$data))
		{
			$aData = (array)$data;
			$oid = $aData[$key];
			$table->load($oid);
		}
		
		if(!$this->onBeforeSave($data, $table)) {
			return false;
		}

		if(!$table->save($data)) {
			$this->setError($table->getError());
			return false;
		} else {
			$this->id = $table->$key;
		}
		
		$this->onAfterSave($table);

		$this->otable = $table;
		return true;
	}

	/**
	 * Returns the table object after the last save() operation
	 * @return JTable
	 */
	public final function getSavedTable()
	{
		return $this->otable;
	}

	/**
	 * Deletes one or several items
	 * @return bool
	 */
	public function delete()
	{
		if(is_array($this->id_list) && !empty($this->id_list)) {
			$table = $this->getTable($this->table);
			foreach($this->id_list as $id) {
				if(!$this->onBeforeDelete($id, $table)) continue;
				
				if(!$table->delete($id)) {
					$this->setError($table->getError());
					return false;
				} else {
					$this->onAfterDelete($id);
				}
			}
		}
		return true;
	}

	/**
	 * Toggles the published state of one or several items
	 * @param int $publish The publishing state to set (e.g. 0 is unpublished)
	 * @param int $user The user ID performing this action
	 * @return bool
	 */
	public function publish($publish = 1, $user = null)
	{
		if(is_array($this->id_list) && !empty($this->id_list)) {
			if(empty($user)) {
				$oUser = JFactory::getUser();
				$user = $oUser->id;
			}
			$table = $this->getTable($this->table);
			
			if(!$this->onBeforePublish($table)) return false;
			
			if(!$table->publish($this->id_list, $publish, $user) ) {
				$this->setError($table->getError());
				return false;
			} else {
				$this->onAfterPublish($table);
			}
		}
		return true;
	}

	/**
	 * Checks out the current item
	 * @return bool
	 */
	public function checkout()
	{
		$table = $this->getTable($this->table);
		$status = $table->checkout(JFactory::getUser()->id, $this->id);
		if(!$status) $this->setError($table->getError());
		return $status;
	}

	/**
	 * Checks in the current item
	 * @return bool
	 */
	public function checkin(){
		$table = $this->getTable($this->table);
		$status = $table->checkin($this->id);
		if(!$status) $this->setError($table->getError());
		return $status;
	}

	/**
	 * Tells you if the current item is checked out or not
	 * @return bool
	 */
	public function isCheckedOut() {
		$table = $this->getTable($this->table);
		$status = $table->isCheckedOut($this->id);
		if(!$status) $this->setError($table->getError());
		return $status;
	}

	/**
	 * Increments the hit counter
	 * @return bool
	 */
	public function hit() {
		$table = $this->getTable($this->table);
		if(!$this->onBeforeHit($table)) return false;
		$status = $table->hit($this->id);
		if(!$status) {
			$this->setError($table->getError());
		} else {
			$this->onAfterHit($table);
		}
		return $status;
	}

	/**
	 * Moves the current item up or down in the ordering list
	 * @param <type> $dirn
	 * @return bool
	 */
	public function move( $dirn ) {
		$table = $this->getTable($this->table);

		$id = $this->getId();
		$status = $table->load($id);
		if(!$status) $this->setError($table->getError());
		if(!$status) return false;

		if(!$this->onBeforeMove($table)) return false;
		
		$status = $table->move($dirn);
		if(!$status) {
			$this->setError($table->getError());
		} else {
			$this->onAfterMove($table);
		}

		return $status;
	}

	/**
	 * Reorders all items in the table
	 * @return bool
	 */
	public function reorder()
	{
		$table = $this->getTable($this->table);
		if(!$this->onBeforeReorder($table)) return false;
		$status = $table->reorder( $this->getReorderWhere() );
		if(!$status) {
			$this->setError($table->getError());
		} else {
			if(!$this->onAfterReorder($table)) return false;
		}
		return $status;
	}

	/**
	 * Get a pagination object
	 *
	 * @access public
	 * @return JPagination
	 *
	 */
	public final function getPagination()
	{
		if( empty($this->pagination) )
		{
			// Import the pagination library
			jimport('joomla.html.pagination');

			// Prepare pagination values
			$total = $this->getTotal();
			$limitstart = $this->getState('limitstart');
			$limit = $this->getState('limit');

			// Create the pagination object
			$this->pagination = new JPagination($total, $limitstart, $limit);
		}

		return $this->pagination;
	}

	/**
	 * Get the number of all items
	 *
	 * @access public
	 * @return integer
	 */
	public final function getTotal()
	{
		if( empty($this->total) )
		{
			$query = $this->buildCountQuery();
			if($query === false) {
				$sql = (string)$this->buildQuery(false);
				$query = FOFQueryAbstract::getNew($this->_db)
						->select('COUNT(*)')
						->from("($sql) AS a");
			}

			$this->_db->setQuery( (string)$query );
			$this->_db->query();
			
			$this->total = $this->_db->loadResult();
		}

		return $this->total;
	}

	/**
	 * Get a filtered state variable
	 * @param string $key
	 * @param mixed $default
	 * @param string $filter_type
	 * @return mixed
	 */
	public function getState($key = null, $default = null, $filter_type = 'raw')
	{
		if(empty($key)) {
			return parent::getState();
		}
		
		// Get the savestate status
		$savestate = parent::getState('savestate');
		if(is_null($savestate)) {
			$savestate = FOFInput::getBool('savestate',false,$this->input);
		}

		$value = parent::getState($key);
		if(is_null($value))
		{
			// Try to fetch it from the request or session
			if($savestate) {
				$value = $this->getUserStateFromRequest($this->getHash().$key,$key,null);
			} else {
				$value = FOFInput::getVar($key, null, $this->input);
			}

			if(is_null($value))	return $default;
		}
		
		if( strtoupper($filter_type) == 'RAW' )
		{
			return $value;
		}
		else
		{
			jimport('joomla.filter.filterinput');
			$filter = new JFilterInput();
			return $filter->clean($value, $filter_type);
		}
	}

	public final function getHash()
	{
		return FOFInput::getCmd('option', 'com_foobar', $this->input).'.'.str_replace('Model', '', $this->getName()).'.';
	}

	/**
	 * Gets the value of a user state variable.
	 *
	 * @access	public
	 * @param	string	The key of the user state variable.
	 * @param	string	The name of the variable passed in a request.
	 * @param	string	The default value for the variable if not found. Optional.
	 * @param	string	Filter for the variable, for valid values see {@link JFilterInput::clean()}. Optional.
	 * @return	The request user state.
	 */
	protected function getUserStateFromRequest( $key, $request, $default = null, $type = 'none' )
	{
		$app = JFactory::getApplication();
		$old_state = $app->getUserState( $key );
		$cur_state = (!is_null($old_state)) ? $old_state : $default;
		$new_state = FOFInput::getVar($request, null, $this->input, $type);

		// Save the new value only if it was set in this request
		if ($new_state !== null) {
			$app->setUserState($key, $new_state);
		} else {
			$new_state = $cur_state;
		}

		return $new_state;
	}
	
	/**
	 * Returns an object list
	 *
	 * @param	string The query
	 * @param	int Offset
	 * @param	int The number of records
	 * @return	array
	 * @access	protected
	 * @since	1.5
	 */
	function &_getList( $query, $limitstart=0, $limit=0 )
	{
		$this->_db->setQuery( $query, $limitstart, $limit );
		$result = $this->_db->loadObjectList();

		$this->onProcessList($result);
		
		return $result;
	}
	
	/**
	 * Method to load and return a model object.
	 *
	 * @access	private
	 * @param	string	The name of the view
	 * @param   string  The class prefix. Optional.
	 * @return	mixed	Model object or boolean false if failed
	 * @since	1.5
	 */
	function &_createTable( $name, $prefix = 'Table', $config = array())
	{
		$result = null;

		// Clean the model name
		$name	= preg_replace( '/[^A-Z0-9_]/i', '', $name );
		$prefix = preg_replace( '/[^A-Z0-9_]/i', '', $prefix );

		//Make sure we are returning a DBO object
		if (!array_key_exists('dbo', $config))  {
			$config['dbo'] =& $this->getDBO();;
		}

		$instance =& FOFTable::getAnInstance($name, $prefix, $config );
		return $instance;
	}
	
	/**
	 * Creates the WHERE part of the reorder query
	 * @return type 
	 */
	public function getReorderWhere()
	{
		return '';
	}

	/**
	 * Builds the SELECT query
	 */
	public function buildQuery($overrideLimits = false)
	{
		$table = $this->getTable();
		$tableName = $table->getTableName();
		$tableKey = $table->getKeyName();
		$db = $this->getDBO();
		
		$query = FOFQueryAbstract::getNew()
			->select('*')
			->from($db->nameQuote($tableName));
		
		$where = array();
		$fieldsArray = $db->getTableFields($tableName, true);
		$fields = array_shift($fieldsArray);
		foreach($fields as $fieldname => $fieldtype) {
			$filterName = ($fieldname == $tableKey) ? 'id' : $fieldname;
			$filterState = $this->getState($filterName, null);
			if(!empty($filterState)) {
				$query->where('('.$db->nameQuote($fieldname).'='.$db->Quote($filterState).')');
			}
		}
		
		return $query;
	}

	/**
	 * Builds the count query used in getTotal()
	 * @return type 
	 */
	public function buildCountQuery()
	{
		return false;
	}
	
	public function __get($name) {
		return $this->getState($name);
	}
	
	public function __set($name, $value) {
		return $this->setState($name, $value);
	}
	
	public function __call($name, $arguments) {
		$arg1 = array_shift($arguments);
		$this->setState($name, $arg1);
		return $this;
	}
	
	/**
	 * This method can be overriden to automatically do something with the
	 * list results array. You are supposed to modify the list which was passed
	 * in the parameters; DO NOT return a new array!
	 * 
	 * @param array $resultArray An array of objects, each row representing a record
	 */
	protected function onProcessList(&$resultArray)
	{
	}

	/**
	 * This method runs after an item has been gotten from the database in a read
	 * operation. You can modify it before it's returned to the MVC triad for
	 * further processing.
	 * 
	 * @param JTable $record 
	 */
	protected function onAfterGetItem(&$record)
	{
	}
	
	/**
	 * This method runs before the $data is saved to the $table. Return false to
	 * stop saving.
	 * 
	 * @param array $data
	 * @param JTable $table 
	 * @return bool
	 */
	protected function onBeforeSave(&$data, &$table)
	{
		return true;
	}
	
	/**
	 * This method runs after the data is saved to the $table.
	 * 
	 * @param JTable $table 
	 */
	protected function onAfterSave(&$table)
	{
	}
	
	protected function onBeforeDelete(&$id, &$table)
	{
		return true;
	}
	
	protected function onAfterDelete($id)
	{
	}

	protected function onBeforePublish(&$table)
	{
		return true;
	}

	protected function onAfterPublish(&$table)
	{
		return true;
	}
	
	protected function onBeforeHit(&$table)
	{
		return true;
	}
	
	protected function onAfterHit(&$table)
	{
	}
	
	protected function onBeforeMove(&$table)
	{
		return true;
	}
	
	protected function onAfterMove(&$table)
	{
	}
	
	protected function onBeforeReorder(&$table)
	{
		return true;
	}
	
	protected function onAfterReorder(&$table)
	{
	}
	
}