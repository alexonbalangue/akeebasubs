<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsDispatcher extends ComDefaultDispatcher
{
	/**
	 * Remove CSRF protection from specific actions
	 * @var array
	 */
	private $_unprotectedActions = array('callback');
	
	private $_unprotectedViews = array('validate','callback','message');
	
    protected function _initialize(KConfig $config)
    {
        $config->append(array(
                'controller_default' => 'levels'
        ));
        parent::_initialize($config);
    }
    
	/**
	 * Overriden to remove CSRF protection from specific actions
	 * @param KCommandContext $context
	 */
	public function _actionAuthorize(KCommandContext $context)
	{
        if(KRequest::method() != 'GET') {
        	// Always allow POSTS to the 'validate' and 'callback' views
        	$view = KRequest::get('post.view', 'cmd');
        	if(empty($view)) {
        		$view = KRequest::get('get.view', 'cmd');
        	}
        	
        	if(in_array($view, $this->_unprotectedViews)) return true;
        	
        	// Always allow specific actions
			$action = KRequest::get('post.action', 'cmd');
			if(empty($action)) {
				$action = KRequest::get('get.action', 'cmd');
			}
	
			if(in_array($action,$this->_unprotectedActions)) {
				return true;
			}
        }
        
        return parent::_actionAuthorize($context);
	}
}