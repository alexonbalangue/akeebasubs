<?php
/**
 *  @package FrameworkOnFramework
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * FrameworkOnFramework HTML List View class
 * 
 * FrameworkOnFramework is a set of classes which extend Joomla! 1.5 and later's
 * MVC framework with features making maintaining complex software much easier,
 * without tedious repetitive copying of the same code over and over again.
 */
class FOFViewHtml extends FOFView
{
	protected $lists = null;
	protected $perms = null;

	function  __construct($config = array()) {
		parent::__construct($config);
		
		$this->config = $config;
		
		// Get the input
		if(array_key_exists('input', $config)) {
			$this->input = $config['input'];
		} else {
			$this->input = JRequest::get('default', 3);
		}
		
		$this->lists = new JObject();
		
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			$user = JFactory::getUser();
			$perms = (object)array(
				'create'	=> $user->authorise('core.create', FOFInput::getCmd('option','com_foobar',$this->input) ),
				'edit'		=> $user->authorise('core.edit', FOFInput::getCmd('option','com_foobar',$this->input)),
				'editstate'	=> $user->authorise('core.edit.state', FOFInput::getCmd('option','com_foobar',$this->input)),
				'delete'	=> $user->authorise('core.delete', FOFInput::getCmd('option','com_foobar',$this->input)),
			);
		} else {
			$perms = (object)array(
				'create'	=> true,
				'edit'		=> true,
				'editstate'	=> true,
				'delete'	=> true,
			);
		}
		$this->assign('aclperms', $perms);
		$this->perms = $perms;
	}

	function  display($tpl = null)
	{
		// Get the task set in the model
		$model = $this->getModel();
		$task = $model->getState('task','browse');

		// Call the relevant method
		$method_name = 'on'.ucfirst($task);
		if(method_exists($this, $method_name)) {
			$this->$method_name($tpl);
		} else {
			$this->onDisplay();
		}
		
		if(JFactory::getApplication()->isAdmin()) {
			$toolbar = FOFToolbar::getAnInstance(FOFInput::getCmd('option','com_foobar',$this->input), $this->config);
			$toolbar->perms = $this->perms;
			$toolbar->renderToolbar(FOFInput::getCmd('view','cpanel',$this->input), $task);
		}

		// Show the view
		parent::display($tpl);
	}
	
	protected function onBrowse($tpl = null)
	{
		// When in interactive browsing mode, save the state to the session
		$this->getModel()->savestate(1);
		$this->onDisplay($tpl);
	}

	protected function onDisplay($tpl = null)
	{
		$view = FOFInput::getCmd('view','cpanel',$this->input);
		if(in_array($view,array('cpanel','cpanels'))) return;
		
		// Load the model
		$model = $this->getModel();

		// ...ordering
		$this->lists->set('order',		$model->getState('filter_order', 'id', 'cmd'));
		$this->lists->set('order_Dir',	$model->getState('filter_order_Dir', 'DESC', 'cmd'));

		// Assign data to the view
		$this->assign   ( 'items',		$model->getItemList() );
		$this->assignRef( 'pagination',	$model->getPagination());
		$this->assignRef( 'lists',		$this->lists);
	}

	protected function onAdd($tpl = null)
	{
		JRequest::setVar('hidemainmenu', true);
		$model = $this->getModel();
		$this->assignRef( 'item',		$model->getItem() );	
	}

	protected function onEdit($tpl = null)
	{
		// An editor is an editor, no matter if the record is new or old :p
		$this->onAdd();
	}
	
	protected function onRead($tpl = null)
	{
		// All I need is to read the record
		$this->onAdd();
	}
}