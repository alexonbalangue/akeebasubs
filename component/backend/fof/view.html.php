<?php
/**
 *  @package AkeebaSubs
 *  @subpackage FrameworkOnFramework
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * FrameworkOnFramework HTML List View class
 * 
 * FrameworkOnFramework is a set of classes whcih extend Joomla! 1.5 and later's
 * MVC framework with features making maintaining complex software much easier,
 * without tedious repetitive copying of the same code over and over again.
 */
class FOFViewHtml extends JView
{
	protected $lists = null;
	protected $perms = null;

	function  __construct($config = array()) {
		parent::__construct($config);
		
		// Get the input
		$this->input = JRequest::get('default', 3);
		if(array_key_exists('input', $config)) {
			$this->input = array_merge($this->input, $config['input']);
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
		$task = $model->getState('task','display');

		// Call the relevant method
		$method_name = 'on'.ucfirst($task);
		if(method_exists($this, $method_name)) {
			$this->$method_name();
		} else {
			$this->onDisplay();
		}

		// Add the CSS/JS definitions
		$doc = JFactory::getDocument();
		if($doc->getType() == 'html') {
			require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/includes.php';
			ArsHelperIncludes::includeMedia();
		}
		
		JHTML::_('behavior.mootools');

		// Show the view
		parent::display($tpl);
	}

	protected function onDisplay()
	{
		// Load the model
		$model = $this->getModel();

		// @todo Refactor the bitch
		// ...ordering
		$this->lists->set('order',		$model->getState('filter_order', 'id', 'cmd'));
		$this->lists->set('order_Dir',	$model->getState('filter_order_Dir', 'DESC', 'cmd'));

		// Assign data to the view
		$this->assign   ( 'items',		$model->getItemList() );
		$this->assignRef( 'pagination',	$model->getPagination());
		$this->assignRef( 'lists',		$this->lists);

		// Set toolbar title
		$subtitle_key = FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_'.strtoupper(FOFInput::getCmd('view','cpanel',$this->input));
		JToolBarHelper::title(JText::_( FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_DASHBOARD').' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', FOFInput::getCmd('option','com_foobar',$this->input)));
	}

	protected function onAdd()
	{
		$model = $this->getModel();
		
		$this->assignRef( 'item',		$model->getItem() );	
		// Set toolbar title
		$subtitle_key = FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_'.strtoupper(FOFInput::getCmd('view','cpanel',$this->input)).'_EDIT';
		JToolBarHelper::title(JText::_(FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_DASHBOARD').' &ndash; <small>'.JText::_($subtitle_key).'</small>','ars');

		JToolBarHelper::apply();
		JToolBarHelper::save();
		if(version_compare(JVERSION,'1.6.0','ge')) {
			JToolBarHelper::custom('savenew', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		} else {
			$sanTitle = 'Save & New';
			JToolBar::getInstance('toolbar')->appendButton( 'Standard', 'save', $sanTitle, 'savenew', false, false );
		}
		JToolBarHelper::cancel();
	}

	protected function onEdit()
	{
		// An editor is an editor, no matter if the record is new or old :p
		$this->onAdd();
	}
}