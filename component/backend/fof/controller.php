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
 * FrameworkOnFramework controller class
 * 
 * FrameworkOnFramework is a set of classes whcih extend Joomla! 1.5 and later's
 * MVC framework with features making maintaining complex software much easier,
 * without tedious repetitive copying of the same code over and over again.
 */
class FOFController extends JController
{
	/** @var string Current Joomla! version family (15 or 16) */
	protected $jversion = '15';
	
	/** @var string The current view name; you can override it in the configuration */
	protected $view = '';
	
	/** @var string The current component's name; you can override it in the configuration */
	protected $component = 'com_foobar';
	
	/** @var string The current component's name without the com_ prefix */
	protected $bareComponent = 'foobar';
	
	/** @var string The current layout; you can override it in the configuration */
	protected $layout = null;
	
	/** @var array A cached copy of the class configuration parameter passed during initialisation */
	protected $config = array();
	
	/** @var array The input variables array for this MVC triad; you can override it in the configuration */
	protected $input = array();

	/**
	 *
	 * @staticvar array $instances
	 * @param type $option
	 * @param type $view
	 * @param type $config
	 * @return FOFController
	 */
	public function &getInstance($option = null, $view = null, $config = array())
	{
		static $instances = array();
		
		$hash = $option.$view;
		if(!array_key_exists($hash, $instances)) {
			$config['option'] = !is_null($option) ? $option : JRequest::getCmd('option','com_foobar');
			$config['view'] = !is_null($view) ? $view : JRequest::getCmd('view','cpanel');
			
			$className = ucfirst($config['option']).'Controller'.ucfirst($config['view']);
			if (!class_exists( $className )) {
				$searchPaths = array(
					JPATH_ADMINISTRATOR.'/components/'.$config['option'].'/controllers'
				);
				if(array_key_exists('searchpath', $config)) {
					array_unshift($searchPaths, $config['searchpath']);
				}
				
				jimport('joomla.filesystem.path');
				$path = JPath::find(
					$searchPaths,
					strtolower($config['view']).'.php'
				);
				
				if ($path) {
					require_once $path;
				}
			}
			
			if (!class_exists( $className )) {
				$className = 'FOFController';
			}
			$instance = new $className($config);
			
			$instances[$hash] = $instance;
		}
		
		return $instances[$hash];
	}
	
	public function __construct($config = array())
	{
		parent::__construct();

		// Do we have Joomla! 1.6 or later?
		if( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
			$this->jversion = '16';
		}
		
		// Cache the config
		$this->config = $config;

		// Get the input for this MVC triad
		$this->input = JRequest::get('default', 3);
		if(array_key_exists('input', $config)) {
			$this->input = array_merge($this->input, $config['input']);
		}

		// Get the default values for the component and view names
		$this->component = FOFInput::getCmd('option','com_foobar',$this->input);
		$this->view = FOFInput::getCmd('view','cpanel',$this->input);
		
		// Overrides from the config
		if(array_key_exists('option', $config)) $this->component = $config['option'];
		if(array_key_exists('view', $config)) $this->view = $config['view'];
		if(array_key_exists('layout', $config)) $this->layout = $config['layout'];
		
		FOFInput::setVar('option', $this->component, $this->input);
		
		// Set the bareComponent variable
		$this->bareComponent = str_replace('com_', '', strtolower($this->component));
		
		// Set the $name/$_name variable
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$this->name = $this->bareComponent;
		} else {
			$this->_name = $this->bareComponent;
		}
	}

	public function display($cachable = false)
	{
		$document =& JFactory::getDocument();
		$viewType	= $document->getType();

		$view = $this->getThisView();

		// Get/Create the model
		if ($model = $this->getThisModel()) {
			// Push the model into the view (as default)
			$view->setModel($model, true);
		}

		// Set the layout
		$view->setLayout(is_null($this->layout) ? 'default' : $this->layout);

		// Display the view
		if ($cachable && $viewType != 'feed') {
			$cache =& JFactory::getCache($this->component, 'view');
			$cache->get($view, 'display');
		} else {
			$view->display();
		}
	}
	
	public function add($cachable = false)
	{
		// Load and reset the model
		$model = $this->getThisModel();
		$model->reset();

		// Set the layout to form, if it's not set in the URL
		if(is_null($this->layout)) $this->layout = 'form';

		// Display
		$this->display($cachable);
	}

	public function edit($cachable = false)
	{
		// Load the model
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$status = $model->checkout();

		if(!$status) {
			// Redirect on error
			$url = 'index.php?option='.$this->component.'&view='.$this->view;
			$this->setRedirect($url, $model->getError(), 'error');
			$this->redirect();
			return;
		}

		// Set the layout to form, if it's not set in the URL
		if(is_null($this->layout)) $this->layout = 'form';

		// Display
		$this->display($cachable);
	}

	public function apply()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$this->applySave();

		// Redirect to the edit task
		$id = FOFInput::getInt('id', 0, $this->input);
		$textkey = strtoupper($this->component).'_LBL_'.strtoupper($this->view).'_SAVED';
		$url = 'index.php?option='.$this->component.'&view='.$this->view.'&task=edit&id='.$id;
		$this->setRedirect($url, JText::_($textkey));
		$this->redirect();
	}

	public function save()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->applySave();

		// Redirect to the display task
		$textkey = strtoupper($this->component).'_LBL_'.strtoupper($this->view).'_SAVED';
		$url = 'index.php?option='.$this->component.'&view='.$this->view;
		$this->setRedirect($url, JText::_($textkey));
		$this->redirect();
	}

	public function savenew()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->applySave();

		// Redirect to the display task
		$textkey = strtoupper($this->component).'_LBL_'.strtoupper($this->view).'_SAVED';
		$url = 'index.php?option='.$this->component.'&view='.$this->view.'&task=add';
		$this->setRedirect($url, JText::_($textkey));
		$this->redirect();
	}

	public function cancel()
	{
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$model->checkin();

		// Redirect to the display task
		$url = 'index.php?option='.$this->component.'&view='.$this->view;
		$this->setRedirect($url);
		$this->redirect();
	}

	public function accesspublic()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setaccess(0);
	}

	public function accessregistered()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setaccess(1);
	}

	public function accessspecial()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setaccess(2);
	}

	public function publish()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setstate(1);
	}

	public function unpublish()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$this->setstate(0);
	}

	public function saveorder()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$model->setIDsFromRequest();

		$ids = $model->getIds();
		$orders = FOFInput::getArray('order', array(), $this->input);

		if($n = count($ids))
		{
			for($i = 0; $i < $n; $i++)
			{
				$model->setId( $ids[$i] );
				$neworder = (int)$orders[$i];

				$item = $model->getItem();
				$key = $item->getKeyName();
				if($item->$key == $ids[$i])
				{
					$item->ordering = $neworder;
					$model->save($item);
				}
			}
		}

		$model->reorder();

		// redirect
		$url = 'index.php?option='.$this->component.'&view='.$this->view;
		$this->setRedirect($url);
		$this->redirect();
		return;
	}

	public function orderdown()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$model->setIDsFromRequest();

		$status = $model->move(1);
		// redirect
		$url = 'index.php?option='.$this->component.'&view='.$this->view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
	}

	public function orderup()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$model->setIDsFromRequest();

		$status = $model->move(-1);
		// redirect
		$url = 'index.php?option='.$this->component.'&view='.$this->view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
	}

	public function remove()
	{
		// CSRF prevention
		if(!FOFInput::getVar(JUtility::getToken(), false, $this->input)) {
			JError::raiseError('403', JText::_('Request Forbidden'));
		}
		
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$status = $model->delete();

		// redirect
		$url = 'index.php?option='.$this->component.'&view='.$this->view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
		return;
	}

	protected final function setstate($state = 0)
	{
		$model = $this->getThisModel();
		$model->setIDsFromRequest();

		$status = $model->publish($state);

		// redirect
		$url = 'index.php?option='.$this->component.'&view='.$this->view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
		return;
	}

	protected final function setaccess($level = 0)
	{
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$id = $model->getId();

		$item = $model->getItem();
		$key = $item->getKeyName();
		$loadedid = $item->$key;

		if($id == $loadedid)
		{
			$item->access = $level;
			$status = $model->save($item);
		}
		else
		{
			$status = false;
		}


		// redirect
		$url = 'index.php?option='.$this->component.'&view='.$this->view;
		if(!$status)
		{
			$this->setRedirect($url, $model->getError(), 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
		$this->redirect();
		return;
	}
	
	protected function onBeforeApplySave(&$data)
	{
		return $data;
	}

	protected final function applySave()
	{
		// Load the model
		$model = $this->getThisModel();
		$model->setIDsFromRequest();
		$id = $model->getId();

		$data = $this->input;
		$this->onBeforeApplySave($data);
		$status = $model->save($data);

		if($status && ($id != 0)) {
			// Try to check-in the record if it's not a new one
			$status = $model->checkin();
		}
		
		FOFInput::setVar('id', $model->getId(), $this->input);

		if(!$status) {
			// Redirect on error
			// save the posted data
			$session = JFactory::getSession();
			$session->set($model->getHash().'savedata', serialize($data) );
			// redirect
			$id = $model->getId();
			$url = 'index.php?option='.$this->component.'&view='.$this->view.'&task=edit&id='.$id;
			$this->setRedirect($url, $model->getError(), 'error');
			$this->redirect();
			return;
		} else {
			$session = JFactory::getSession();
			$session->set($model->getHash().'savedata', null );
		}
	}

	/**
	 * Returns the default model associated with the current view
	 * @return FOFModel The global instance of the model (singleton)
	 */
	public final function getThisModel($config = array())
	{
		static $prefix = null;
		static $modelName = null;

		if(empty($modelName)) {
			$prefix = ucfirst($this->bareComponent).'Model';
			$modelName = ucfirst($this->view);
		}

		return $this->getModel($modelName, $prefix, array_merge(array(
				'input'	=> $this->input
			), $config
		));
	}

	/**
	 * Returns current view object
	 * @return FOFView The global instance of the view object (singleton)
	 */
	public final function getThisView($config = array())
	{
		static $prefix = null;
		static $viewName = null;
		static $viewType = null;

		if(empty($viewName)) {
			$prefix = ucfirst($this->bareComponent).'View';
			$viewName = ucfirst($this->view);
			$document =& JFactory::getDocument();
			$viewType	= $document->getType();
		}

		$basePath = ($this->jversion == '15') ? $this->_basePath : $this->basePath;
		return $this->getView( $viewName, $viewType, $prefix, array_merge(array(
				'input'		=> $this->input,
				'base_path'	=>$basePath
			), $config)
		);
	}
	
	/**
	 * Method to load and return a model object.
	 *
	 * @access	private
	 * @param	string  The name of the model.
	 * @param	string	Optional model prefix.
	 * @param	array	Configuration array for the model. Optional.
	 * @return	mixed	Model object on success; otherwise null
	 * failure.
	 * @since	1.5
	 */
	function &_createModel( $name, $prefix = '', $config = array())
	{
		$result = null;

		// Clean the model name
		$modelName	 = preg_replace( '/[^A-Z0-9_]/i', '', $name );
		$classPrefix = preg_replace( '/[^A-Z0-9_]/i', '', $prefix );

		$result =& FOFModel::getInstance($modelName, $classPrefix, $config);
		return $result;
	}
}