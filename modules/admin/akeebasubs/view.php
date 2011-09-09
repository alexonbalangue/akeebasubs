<?php
/**
 * @package		mod_akeebasubs
 * @copyright 	Copyright (c) 2011 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */

class ModAkeebasubsView extends ModDefaultView
{       
	public function display()
	{
		$this->subscriptions = KFactory::get('com://admin/akeebasubs.model.subscriptions')
								->sort('akeebasubs_subscription_id')
								->direction('desc')
								->limit(10)
								->getList(); 
		// View stuff
		return parent::display();
	}
}