<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerToolbarConfig extends ComAkeebasubsControllerToolbarDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this
			->reset()
			->addSave()
			->addCancel();
	}

	public function _initialize(KConfig $config)
	{
		$config->append(array(
			'icon'		=> 'akeebasubs'
		));
		parent::_initialize($config);
	}
	
	public function setTitle($title)
	{
		$this->_title = JText::_('COM_AKEEBASUBS_CONFIG_TITLE');
	    return $this;
	}
}