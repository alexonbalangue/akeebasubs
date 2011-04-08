<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsCommandAuthorize extends ComDefaultCommandAuthorize
{
	/**
	 * Remove CSRF protection from specific actions
	 * @var array
	 */
	private $_unprotectedActions = array('callback');
	
	/**
	 * Remove CSRF protection from specific views. Note: callback and message are
	 * accessed by external payment gateways which tend to use POST instead of GET.
	 * Since we have no control over them, we have to skip CSRF validation or the
	 * payments will never be registered to AKSubs and the user will always receive
	 * a 403 error when he's taken back to our site!
	 * @var array
	 */
	private $_unprotectedViews = array('validate','callback','message');
	
	public function execute( $name, KCommandContext $context)
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
	    
	    return parent::execute($name, $context);
	}
}