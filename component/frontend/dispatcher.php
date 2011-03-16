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
	
	private $_unprotectedViews = array('validate','callback');
	
    protected function _initialize(KConfig $config)
    {
        $config->append(array(
                'controller_default' => 'levels'
        ));
        parent::_initialize($config);
    }
    
    /**
     * Overriden to allow defining the action in the GET part of the request (for testing)
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

	public function _actionForward(KCommandContext $context)
	{
		// Normally, Nooku will assume that a POST request saves data in the background and redirects to
		// another URL. I DO NOT WANT TO REDIRECT TO ANOTHER URL! I just want to display a form, case the
		// payment processors expect POST data, therefore I have to override this action to make it do
		// my thing.
		if (KRequest::type() == 'HTTP') {
			$view = KRequest::get('get.view', 'cmd');
			$context->result = KFactory::get($this->getController())
				->execute( $this->getAction(), $context );
			if($context->result === false) {
				// I have to redirect
				$redirect = KFactory::get($this->getController())->getRedirect();
				KFactory::get('lib.koowa.application')
					->redirect($redirect['url'], $redirect['message'], $redirect['type']);
			} else {
				return $context->result;
			}
		} elseif(KRequest::type() == 'AJAX') {
			$view = KRequest::get('post.view', 'cmd');
			if(empty($view)) {
				$view = KRequest::get('get.view', 'cmd');
			}
			
			$context->result = KFactory::get($this->getController())->execute('display', $context);
			return $context->result;
		}
	}	
}