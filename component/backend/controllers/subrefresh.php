<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerSubrefresh extends ComAkeebasubsControllerDefault 
{
	public function __construct(KConfig $config)
	{
		$config->append(array(
			'model'		=> KFactory::get('admin::com.akeebasubs.model.subscriptions')
		));
		
		parent::__construct($config);
	}

	public function _actionBrowse()
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