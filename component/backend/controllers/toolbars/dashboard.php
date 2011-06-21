<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerToolbarDashboard extends ComAkeebasubsControllerToolbarDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		$this->reset();
	}

	public function _initialize(KConfig $config)
	{
		$config->append(array(
			'title'		=> JText::_('COM_AKEEBASUBS_DASHBOARD_TITLE'),
			'icon'		=> 'akeebasubs'
		));
		parent::_initialize($config);
	}
	
	public function setTitle($title)
	{
		$this->_title = $title;
        return $this;
	}
}