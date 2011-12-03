<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsToolbar extends FOFToolbar
{
	public function onSubscriptionsBrowse()
	{
		// Normal buttons
		$this->onBrowse();
		
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