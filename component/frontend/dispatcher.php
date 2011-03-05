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

	public function _actionForward(KCommandContext $context)
	{
		// Normally, Nooku will assume that a POST request saves data in the background and redirects to
		// another URL. I DO NOT WANT TO REDIRECT TO ANOTHER URL! I just want to display a form, case the
		// payment processors expect POST data, therefore I have to override this action to make it do
		// my thing.
		if (KRequest::type() == 'HTTP') {
			$view = KRequest::get('get.view', 'cmd');
			$context->result = KFactory::get($this->getController())
				->execute( $this->getAction() );
			if($context->result === false) {
				// I have to redirect
				$redirect = KFactory::get($this->getController())->getRedirect();
				KFactory::get('lib.koowa.application')
					->redirect($redirect['url'], $redirect['message'], $redirect['type']);
			} else {
				return $context->result;
			}
		} elseif(KRequest::type() == 'AJAX') {
			$view = KRequest::get('get.view', 'cmd');
			$context->result = KFactory::get($this->getController())->execute('display');
			return $context->result;
		}
	}	
}