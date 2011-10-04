<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewCpanel extends FOFViewHtml
{
	public function onBrowse($tpl = null) {
		JToolBarHelper::title(JText::_( FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_DASHBOARD'), 'akeebasubs');
	}
	
	public function onDisplay($tpl = null) {
		JError::raiseError(501, 'Not Implemented');
	}
	
	public function onAdd($tpl = null) {
		JError::raiseError(501, 'Not Implemented');
	}
	
	public function onEdit($tpl = null) {
		JError::raiseError(501, 'Not Implemented');
	}
	
	public function onRead($tpl = null) {
		JError::raiseError(501, 'Not Implemented');
	}
}