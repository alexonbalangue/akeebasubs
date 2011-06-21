<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerToolbarDefault extends ComDefaultControllerToolbarDefault
{
	public function __construct(KConfig $config) {
		parent::__construct($config);
		
		// Joomla! 1.6 is screwed up. Fixing!
		if(version_compare(JVERSION, '1.6.0', 'ge')) {
			JHTML::_('behavior.mootools');
		}
		
		$name = $this->getName();
		
		if(in_array($name, array('dashboard','tools')) || KInflector::isPlural($name) && (KRequest::type() != 'AJAX'))
		{
			if(!in_array($name, array('dashboard','tools')) && KInflector::isPlural($name) && (KRequest::type() != 'AJAX'))
			{
				$this
					->addEnable()
					->addDisable()
					->addExport();
			}
		}
	}
	
	public function _initialize(KConfig $config)
	{
		$name = $this->getName();
		$name = strtoupper($name);
	
		$config->append(array(
			'title'		=> JText::_("COM_AKEEBASUBS_{$name}_TITLE"),
			'icon'		=> 'akeebasubs'
		));
		parent::_initialize($config);
	}
}