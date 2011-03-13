<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsDispatcher extends ComDefaultDispatcher
{
    protected function _initialize(KConfig $config)
    {
        $config->append(array(
                'controller_default' => 'dashboard'
        ));
        parent::_initialize($config);
    }
    
    /**
     * Overriden to allow defining the action in the GET part of the request
     */
	public function getAction()
	{
		$action = KRequest::get('post.action', 'cmd');
		if(empty($action)) {
			$action = KRequest::get('get.action', 'cmd');
		}

		if(empty($action))
		{
			switch(KRequest::method())
			{
				case 'GET'    : $action = 'display'; break;
				case 'POST'   : $action = 'add'    ; break;
				case 'PUT'    : $action = 'edit'   ; break;
				case 'DELETE' : $action = 'delete' ; break;
			}
		}
		
		return $action;
	}    
}