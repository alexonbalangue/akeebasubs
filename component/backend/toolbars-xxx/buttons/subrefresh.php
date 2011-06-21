<?php

/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsToolbarButtonSubrefresh extends KToolbarButtonGet
{
	protected function _initialize(KConfig $config)
	{
		$config->append(array(
			'text'		=> 'COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH',
			'id'		=> 'subrefresh',
			'icon'		=> 'icon-32-subrefresh'
		));
		
		parent::_initialize($config);
	}

	public function getLink()
	{
		// For some odd reason this doesn't get even called. What the...?! :@
		return 'akeebasubs_refresh_integrations();';
	}
}