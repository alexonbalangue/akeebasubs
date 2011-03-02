<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsViewSubscribeHtml extends KViewHtml
{
	
    protected function _initialize(KConfig $config)
    {
    	$config->append(array(
			'auto_assign'  		=> false
       	));
    	
    	parent::_initialize($config);
    }
	
	public function display()
	{
		$model = $this->getModel();
		$this->assign('form', $model->getForm());
		
		return parent::display();
	}
}