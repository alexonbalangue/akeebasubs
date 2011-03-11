<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsViewTaxrulesHtml extends ComAkeebasubsViewHtml
{
	public function display()
	{	
		KFactory::get('admin::com.akeebasubs.toolbar.taxrules')
                ->setTitle('COM_AKEEBASUBS_TAXRULES_TITLE','akeebasubs') 
				->setIcon('akeebasubs');
		
		return parent::display();
	}
}