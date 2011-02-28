<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsDispatcher extends ComDefaultDispatcher
{
	/**
	 * Remove CSRF protection from specific actions
	 * @var array
	 */
	private $_unprotectedActions = array('validate');
	
    protected function _initialize(KConfig $config)
    {
        $config->append(array(
                'controller_default' => 'levels'
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
	
	/**
	 * Overriden to remove CSRF protection from specific actions
	 * @param KCommandContext $context
	 */
	public function _actionAuthorize(KCommandContext $context)
	{
        if(KRequest::method() != 'GET') {
			$action = KRequest::get('post.action', 'cmd');
			if(empty($action)) {
				$action = KRequest::get('get.action', 'cmd');
			}
	
			// Exception: When action=validate, do not try to use a CSRF check
			if(in_array($action,$this->_unprotectedActions)) {
				return true;
			}
        }
		
        if( KRequest::token() !== JUtility::getToken())
        {
        	throw new KDispatcherException('Invalid token or session time-out.', KHttp::UNAUTHORIZED);
        	return false;
        }
	}
	
}