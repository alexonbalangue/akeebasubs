<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsViewDashboardHtml extends ComAkeebasubsViewHtml
{
	public function __construct(KConfig $config) {
		parent::__construct($config);
		$this->_auto_assign = false;
	}
	public function display()
	{
		KRequest::set('get.hidemainmenu', 0);

		return parent::display();
	}
}