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
		$this->onBrowse();
		
		JToolBarHelper::divider();
		
		$bar = JToolBar::getInstance('toolbar');
		$bar->appendButton('Link', 'subrefresh', JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH'), 'javascript:return false;');
	}
}