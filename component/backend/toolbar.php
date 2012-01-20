<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsToolbar extends FOFToolbar
{
	public function onSubscriptionsBrowse()
	{
		// Set toolbar title
		$subtitle_key = FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_'.strtoupper(FOFInput::getCmd('view','cpanel',$this->input));
		JToolBarHelper::title(JText::_( FOFInput::getCmd('option','com_foobar',$this->input)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', FOFInput::getCmd('option','com_foobar',$this->input)));
		
		// Add toolbar buttons
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
		if($this->perms->edit) {
			JToolBarHelper::editListX();
		}
		if($this->perms->create) {
			JToolBarHelper::addNewX();
		}
		
		$this->renderSubmenu();
		
		$bar = JToolBar::getInstance('toolbar');
		
		// Add "Subscription Refresh"Run Integrations"
		JToolBarHelper::divider();
		$bar->appendButton('Link', 'subrefresh', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH'), 'javascript:return false;');
		
		// Add "Export to CSV"
		$link = JURI::getInstance();
		$query = $link->getQuery(true);
		$query['format'] = 'csv';
		$query['option'] = 'com_akeebasubs';
		$query['view'] = 'subscriptions';
		$query['task'] = 'browse';
		$link->setQuery($query);
		
		JToolBarHelper::divider();
		$bar->appendButton('Link', 'export', JText::_('COM_AKEEBASUBS_COMMON_EXPORTCSV'), $link->toString());
	}
	
	public function onLevelsBrowse()
	{
		$this->onBrowse();
		
		JToolBarHelper::divider();
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'JLIB_HTML_BATCH_COPY', false);
		} else {
			JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'Copy', false);
		}
	}
}