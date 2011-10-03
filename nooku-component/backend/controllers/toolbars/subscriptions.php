<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerToolbarSubscriptions extends ComAkeebasubsControllerToolbarDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this
			->addSubrefresh();
	}
	
	protected function _commandSubrefresh(KControllerToolbarCommand $command)
    {
        $command->icon = 'icon-32-subrefresh';
        $command->label = JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH');
    }
	
}