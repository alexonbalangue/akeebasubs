<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsViewConfigHtml extends ComDefaultViewHtml
{
	public function __construct(KConfig $config)
	{
		$config->append(array('auto_assign' => false));
		
		parent::__construct($config);
	}
	
	public function display()
	{	
		KFactory::get('admin::com.akeebasubs.toolbar.config')
				->reset()
				->append('save')
				->append('cancel')
                ->setTitle('COM_AKEEBASUBS_CONFIG_TITLE','akeebasubs') 
				->setIcon('akeebasubs');
		
		return parent::display();
	}
}