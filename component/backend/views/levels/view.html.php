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
		
		$this->onDisplay($tpl);
	}
}