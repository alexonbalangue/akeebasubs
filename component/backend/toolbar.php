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
	protected function renderSubmenu()
	{
		$views = array(
			'cpanel',
			'COM_AKEEBASUBS_MAINMENU_SETUP' => array('levelgroups', 'levels', 'customfields', 'upgrades', 'taxrules'),
			'subscriptions',
			'coupons',
			'COM_AKEEBASUBS_MAINMENU_AFFILIATES' => array('affiliates', 'affpayments'),
			'COM_AKEEBASUBS_MAINMENU_TOOLS' => array('tools', 'users'),
		);
		
		foreach($views as $label => $view) {
			if(!is_array($view)) {
				$this->addSubmenuLink($view);
			} else {
				$label = JText::_($label);
				$this->appendLink($label, '', false);
				foreach($view as $v) {
					$this->addSubmenuLink($v, $label);
				}
			}
		}
	}
	
	private function addSubmenuLink($view, $parent = null)
	{
		static $activeView = null;
		if(empty($activeView)) {
			$activeView = FOFInput::getCmd('view','cpanel',$this->input);
		}
		
		$key = strtoupper($this->component).'_TITLE_'.strtoupper($view);
		if(strtoupper(JText::_($key)) == $key) {
			$altview = FOFInflector::isPlural($view) ? FOFInflector::singularize($view) : FOFInflector::pluralize($view);
			$key2 = strtoupper($this->component).'_TITLE_'.strtoupper($altview);
			if(strtoupper(JText::_($key2)) == $key2) {
				$name = ucfirst($view);
			} else {
				$name = JText::_($key2);
			}
		} else {
			$name = JText::_($key);
		}

		$link = 'index.php?option='.$this->component.'&view='.$view;

		$active = $view == $activeView;

		$this->appendLink($name, $link, $active, null, $parent);
	}
	
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
		$subtitle_key = FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_'.strtoupper(FOFInput::getCmd('view','cpanel',$this->input));
		JToolBarHelper::title(JText::_( FOFInput::getCmd('option','com_foobar',$this->input)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', FOFInput::getCmd('option','com_foobar',$this->input)));
		
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
		$bar->appendButton('Link', 'subrefresh', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH'), 'javascript:akeebasubs_refresh_integrations();');
		
		// Add "Export to CSV"
		$link = JURI::getInstance();
		$query = $link->getQuery(true);
		$query['format'] = 'csv';
		$query['option'] = 'com_akeebasubs';
		$query['view'] = 'subscriptions';
		$query['task'] = 'browse';
		$link->setQuery($query);
		
		JToolBarHelper::divider();
		$icon = version_compare(JVERSION, '3.0', 'lt') ? 'export' : 'download';
		$bar->appendButton('Link', $icon, JText::_('COM_AKEEBASUBS_COMMON_EXPORTCSV'), $link->toString());
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
		$subtitle_key = FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_'.strtoupper(FOFInput::getCmd('view','cpanel',$this->input));
		JToolBarHelper::title(JText::_( FOFInput::getCmd('option','com_foobar',$this->input)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', FOFInput::getCmd('option','com_foobar',$this->input)));
		
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
		$subtitle_key = FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_'.strtoupper(FOFInput::getCmd('view','cpanel',$this->input));
		JToolBarHelper::title(JText::_( FOFInput::getCmd('option','com_foobar',$this->input)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', FOFInput::getCmd('option','com_foobar',$this->input)));
		
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
	
	public function onMakecouponsOverview()
	{
		$subtitle_key = FOFInput::getCmd('option','com_foobar',$this->input).'_TITLE_'.strtoupper(FOFInput::getCmd('view','cpanel',$this->input));
		JToolBarHelper::title(JText::_( FOFInput::getCmd('option','com_foobar',$this->input)).' &ndash; <small>'.JText::_($subtitle_key).'</small>', str_replace('com_', '', FOFInput::getCmd('option','com_foobar',$this->input)));
		
		$this->renderSubmenu();
	}
}