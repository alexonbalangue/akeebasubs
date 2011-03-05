<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsViewHtml extends ComDefaultViewHtml
{
	public function __construct(KConfig $config)
	{
		$config->views = array(
			'dashboard' 		=> JText::_('COM_AKEEBASUBS_DASHBOARD'),
			'subscriptions'		=> JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_TITLE'),
			'coupons'			=> JText::_('COM_AKEEBASUBS_COUPONS_TITLE'),
			'levels' 			=> JText::_('COM_AKEEBASUBS_LEVELS_TITLE'),
			'taxrules'			=> JText::_('COM_AKEEBASUBS_TAXRULES_TITLE'),
        );
		
		parent::__construct($config);
	}
	
	public function display()
	{
		$name = $this->getName();
		
		//Apend enable and disbale button for all the list views
		if($name != 'dashboard' && KInflector::isPlural($name) && KRequest::type() != 'AJAX')
		{
			KFactory::get('admin::com.akeebasubs.toolbar.'.$name)
				//->append('divider')	
				->append('enable')
				->append('disable')
				->append('csv');	
		}
					
		return parent::display();
	}
}