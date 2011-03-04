<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsViewCouponHtml extends ComDefaultViewHtml
{
	public function display()
	{	
		KFactory::get('admin::com.akeebasubs.toolbar.level')
                ->setTitle('COM_AKEEBASUBS_COUPON_EDITORTITLE','akeebasubs') 
				->setIcon('akeebasubs');
		
		return parent::display();
	}
}