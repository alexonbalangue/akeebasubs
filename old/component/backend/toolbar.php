<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsToolbar extends F0FToolbar
{
	protected function getMyViews()
	{
		$views = array('cpanel');

		$allViews = parent::getMyViews();
		foreach($allViews as $view) {
			if(!in_array($view, $views)) {
				$views[] = $view;
			}
		}

		return $views;
	}

	public function onSubscriptionsBrowse()
	{
		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option','com_foobar').'_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( $this->input->getCmd('option','com_foobar')).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $this->input->getCmd('option','com_foobar')));

		// Add toolbar buttons
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
		if($this->perms->edit) {
			JToolBarHelper::editList();
		}
		if($this->perms->create) {
			JToolBarHelper::addNew();
		}

		$this->renderSubmenu();

		$bar = JToolBar::getInstance('toolbar');

		// Add "Subscription Refresh"Run Integrations"
		JToolBarHelper::divider();
		$bar->appendButton('Link', 'play', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH'), 'javascript:akeebasubs_refresh_integrations();');

		// Add "Export to CSV"
		$link = JURI::getInstance();
		$query = $link->getQuery(true);
		$query['format'] = 'csv';
		$query['option'] = 'com_akeebasubs';
		$query['view'] = 'subscriptions';
		$query['task'] = 'browse';
		$link->setQuery($query);

		JToolBarHelper::divider();
		$bar->appendButton('Link', 'download', JText::_('COM_AKEEBASUBS_COMMON_EXPORTCSV'), $link->toString());
	}

	public function onLevelsBrowse()
	{
		$this->onBrowse();

		JToolBarHelper::divider();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'JLIB_HTML_BATCH_COPY', false);
	}

	public function onUsersBrowse()
	{
		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option','com_foobar').'_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( $this->input->getCmd('option','com_foobar')).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $this->input->getCmd('option','com_foobar')));

		// Add toolbar buttons
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
		if($this->perms->edit) {
			JToolBarHelper::editList();
		}
		if($this->perms->create) {
			JToolBarHelper::addNew();
		}

		$this->renderSubmenu();
	}

	public function onAffpaymentsBrowse()
	{
		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option','com_foobar').'_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( $this->input->getCmd('option','com_foobar')).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $this->input->getCmd('option','com_foobar')));

		// Add toolbar buttons
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
		if($this->perms->edit) {
			JToolBarHelper::editList();
		}
		if($this->perms->create) {
			JToolBarHelper::addNew();
		}

		$this->renderSubmenu();
	}

	public function onInvoicesBrowse()
	{
		//on frontend, buttons must be added specifically
		list($isCli, $isAdmin) = F0FDispatcher::isCliAdmin();

		if($isAdmin || $this->renderFrontendSubmenu) {
			$this->renderSubmenu();
		}

		if(!$isAdmin && !$this->renderFrontendButtons) return;

		// Set toolbar title
		$subtitle_key = $this->input->getCmd('option','com_foobar').'_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_( $this->input->getCmd('option','com_foobar')).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', $this->input->getCmd('option','com_foobar')));

		// Add toolbar buttons
		if($this->perms->delete) {
			JToolBarHelper::deleteList();
		}
	}

	public function onInvoicetemplatesBrowse()
	{
		$this->onBrowse();

		JToolBarHelper::divider();
		JToolBarHelper::custom('copy', 'copy.png', 'copy_f2.png', 'JLIB_HTML_BATCH_COPY', false);
	}

	public function onToolsBrowse()
	{
		$subtitle_key = 'COM_AKEEBASUBS_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_('COM_AKEEBASUBS').' &ndash; <small>'.JText::_($subtitle_key).'</small>', 'akeebasubs');

		$bar = JToolbar::getInstance('toolbar');
		$bar->appendButton('Link', 'arrow-left', 'JTOOLBAR_BACK', 'index.php?option=com_akeebasubs&view=cpanel');
	}

	public function onReports()
	{
		$subtitle_key = 'COM_AKEEBASUBS_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_('COM_AKEEBASUBS').' &ndash; <small>'.JText::_($subtitle_key).'</small>', 'akeebasubs');

		$bar = JToolbar::getInstance('toolbar');
		$bar->appendButton('Link', 'arrow-left', 'JTOOLBAR_BACK', 'index.php?option=com_akeebasubs&view=reports');
	}

	public function onReportsBrowse()
	{
		$subtitle_key = 'COM_AKEEBASUBS_TITLE_'.strtoupper($this->input->getCmd('view','cpanel'));
		JToolBarHelper::title(JText::_('COM_AKEEBASUBS').' &ndash; <small>'.JText::_($subtitle_key).'</small>', 'akeebasubs');

		$bar = JToolbar::getInstance('toolbar');
		$bar->appendButton('Link', 'arrow-left', 'JTOOLBAR_BACK', 'index.php?option=com_akeebasubs&view=cpanel');
	}
}