<?php
/**
 *  @package AkeebaSubs
 *  @subpackage FrameworkOnFramework
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * FrameworkOnFramework dispatcher class
 * 
 * FrameworkOnFramework is a set of classes whcih extend Joomla! 1.5 and later's
 * MVC framework with features making maintaining complex software much easier,
 * without tedious repetitive copying of the same code over and over again.
 */
class FOFDispatcher extends JObject
{
	protected $config = array();
	
	protected $input = array();
	
	public $defaultView = 'cpanel';
	
	/**
	 *
	 * @staticvar array $instances
	 * @param type $option
	 * @param type $view
	 * @param type $config
	 * @return FOFDispatcher
	 */
	public static function &getAnInstance($option = null, $view = null, $config = array())
	{
		static $instances = array();
		
		$hash = $option.$view;
		if(!array_key_exists($hash, $instances)) {
			$config['option'] = !is_null($option) ? $option : JRequest::getCmd('option','com_foobar');
			$config['view'] = !is_null($view) ? $view : JRequest::getCmd('view','cpanel');
			
			$className = ucfirst(str_replace('com_', '', $config['option'])).'Dispatcher';
			if (!class_exists( $className )) {
				$app = JFactory::getApplication();
				if($app->isSite()) {
					$basePath = JPATH_SITE;
				} else {
					$basePath = JPATH_ADMINISTRATOR;
				}
				
				$searchPaths = array(
					$basePath.'/components/'.$config['option'],
					$basePath.'/components/'.$config['option'].'/dispatchers',
					JPATH_ADMINISTRATOR.'/components/'.$config['option'],
					JPATH_ADMINISTRATOR.'/components/'.$config['option'].'/dispatchers'
				);
				if(array_key_exists('searchpath', $config)) {
					array_unshift($searchPaths, $config['searchpath']);
				}
				
				jimport('joomla.filesystem.path');
				$path = JPath::find(
					$searchPaths,
					'dispatcher.php'
				);
				
				if ($path) {
					require_once $path;
				}
			}
			
			if (!class_exists( $className )) {
				$className = 'FOFDispatcher';
			}
			$instance = new $className($config);
			
			$instances[$hash] = $instance;
		}
		
		return $instances[$hash];
	}
	
	public function __construct($config = array()) {
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
	}
	
	public function dispatch()
	{
		// Timezone fix; avoids errors printed out by PHP 5.3.3+
		if( !version_compare(JVERSION, '1.6.0', 'ge') && function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set')) {
			if(function_exists('error_reporting')) {
				$oldLevel = error_reporting(0);
			}
			$serverTimezone = @date_default_timezone_get();
			if(empty($serverTimezone) || !is_string($serverTimezone)) $serverTimezone = 'UTC';
			if(function_exists('error_reporting')) {
				error_reporting($oldLevel);
			}
			@date_default_timezone_set( $serverTimezone);
		}
		
		// Master access check for the back-end
		if(version_compare(JVERSION, '1.6.0', 'ge') && JFactory::getApplication()->isAdmin()) {
			// Access check, Joomla! 1.6 style.
			$user = JFactory::getUser();
			if (
				!$user->authorise('core.manage', FOFInput::getCmd('option','com_foobar',$this->input) )
				&& !$user->authorise('core.admin', FOFInput::getCmd('option','com_foobar',$this->input))
			) {
				return JError::raiseError(403, JText::_('JERROR_ALERTNOAUTHOR'));
			}
		}
		
		// Merge English and local translations
		if(JFactory::getApplication()->isAdmin()) {
			$paths = array(JPATH_ROOT, JPATH_ADMINISTRATOR);
		} else {
			$paths = array(JPATH_ADMINISTRATOR, JPATH_ROOT);
		}
		$jlang =& JFactory::getLanguage();
		$jlang->load('com_akeebasubs', $paths[0], 'en-GB', true);
		$jlang->load('com_akeebasubs', $paths[0], null, true);
		$jlang->load('com_akeebasubs', $paths[1], 'en-GB', true);
		$jlang->load('com_akeebasubs', $paths[1], null, true);

		if(!$this->onBeforeDispatch()) {
			return false;
		}
		
		// Get and execute the controller
		$option = FOFInput::getCmd('option','com_foobar',$this->input);
		$view = FOFInput::getCmd('view',$this->defaultView, $this->input);
		$task = FOFInput::getCmd('task','display',$this->input);
		$controller = FOFController::getAnInstance($option, $view);
		$controller->execute($task);

		if(!$this->onAfterDispatch()) {
			return false;
		}
		
		$controller->redirect();
	}
	
	public function onBeforeDispatch()
	{
		return true;
	}
	
	public function onAfterDispatch()
	{
		return true;
	}
}