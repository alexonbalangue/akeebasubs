<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsViewUpgradeHtml extends ComAkeebasubsViewHtml
{
	public function display()
	{	
		KFactory::get('admin::com.akeebasubs.toolbar.upgrade')
                ->setTitle('COM_AKEEBASUBS_UPGRADE_TITLE','akeebasubs') 
				->setIcon('akeebasubs');
		
		return parent::display();
	}
}