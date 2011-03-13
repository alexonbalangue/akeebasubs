<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerSubscription extends ComAkeebasubsControllerDefault 
{
	public function _actionRefresh()
	{
		// Run the plugin events on the list
		$list = $this->getModel()->refresh(1)->getList()->subscriptionRefresh();

		$response = array(
			'total'	=> $this->getModel()->getTotal(),
			'processed'	=> count($this->getModel()->getList())
		);
		
		echo json_encode($response);
		
		// Return
		KFactory::get('lib.koowa.application')->close();
	}
}