<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsViewHtml extends ComDefaultViewHtml
{
	public function __construct(KConfig $config)
	{
		$config->views = array(
			'dashboard' 		=> 'COM_AKEEBASUBS_DASHBOARD',
			'levels' 			=> 'COM_AKEEBASUBS_LEVELS_TITLE',
			'subscriptions'		=> 'COM_AKEEBASUBS_SUBSCRIPTIONS_TITLE',
			'coupons'			=> 'COM_AKEEBASUBS_COUPONS_TITLE',
			'upgrades'			=> 'COM_AKEEBASUBS_UPGRADES_TITLE',
			'taxrules'			=> 'COM_AKEEBASUBS_TAXRULES_TITLE',
			'config'			=> 'COM_AKEEBASUBS_CONFIG_TITLE'
        );
		
		parent::__construct($config);
	}
	
	public function display()
	{
		// Joomla! 1.6 is screwed up. Fixing!
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			JHTML::_('behavior.mootools');
		}
	
		$name = $this->getName();
		
		//Apend enable and disbale button for all the list views
		if(!in_array($name, array('dashboard','tools')) && KInflector::isPlural($name) && KRequest::type() != 'AJAX')
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