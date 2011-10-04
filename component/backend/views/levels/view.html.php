<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class AkeebasubsViewLevels extends FOFViewHtml
{
	public function onBrowse($tpl = null) {
		$app = JFactory::getApplication();
		
		// ...filter states
		$this->lists->set('fltSearch',	$app->getUserStateFromRequest(
			'akeebasubs.levels.filter_search','search', null));
		
		// Add toolbar buttons
		if($this->perms->editstate) {
			JToolBarHelper::publishList();
			JToolBarHelper::unpublishList();
			JToolBarHelper::divider();
		}
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
		if($this->perms->edit) {
			JToolBarHelper::editListX();
		}
		if($this->perms->create) {
			JToolBarHelper::addNewX();
		}
		JToolBarHelper::divider();
		JToolBarHelper::back(version_compare(JVERSION,'1.6.0','ge') ? 'JTOOLBAR_BACK' : 'Back', 'index.php?option='.JRequest::getCmd('option'));
		
		$this->onDisplay($tpl);
	}
}