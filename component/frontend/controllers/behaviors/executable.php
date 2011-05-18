<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerBehaviorExecutable extends KControllerBehaviorExecutable
{
	/**
	 * Remove CSRF protection from specific actions
	 * @var array
	 */
	private $_unprotectedActions = array('callback');
	
	/**
	 * Remove CSRF protection from specific views.
	 * 
	 * Note: callback and message are accessed by external payment gateways
	 * which tend to use POST instead of GET. Since we have no control over them,
	 * we have to skip CSRF validation or the payments will never be registered
	 * to Akeeba Subscriptions and the user will always receive a 403 error when
	 * he's taken back to our site!
	 * 
	 * The validate view needs to be passed large amounts of data. Using GET would
	 * quickly deplete the limits of URL length and throw a server error. We have
	 * to resort to POST requests, breaking the RESTfulness of the view in favor
	 * of making it work. As a result, we should perform no token checks either.
	 * 
	 * @var array
	 */
	private $_unprotectedViews = array('validate','callback','message');
	
	/**
	 * Specialized behavior to handle POST requests without tokens on specific views
	 */
	public function execute( $name, KCommandContext $context) 
	{
		if(KRequest::method() != 'GET') {
	    	// Always allow POSTS to the 'validate', 'callback' and 'message' views
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
	    
	    //return parent::execute($name, $context);
		return $this->_koowa_execute($name, $context);
	}
	
	// ======================================================================
	// WARNING!! NON-DRY CODE AHEAD!!
	// ======================================================================
	// The rest of the code here is copied verbatim from
	// ComDefaultControllerBehaviorExecutable because the execute() method is
	// defined final and can not be overriden in child classes. We need to
	// override it in order to disable token checks in our callback and
	// message views, therefore we have to defy the DRY principle and do some
	// ugly copying & pasting.
	
	public function _koowa_execute( $name, KCommandContext $context) 
    { 
        $parts = explode('.', $name); 
        
        if($parts[0] == 'before') 
        { 
            if(!$this->_checkToken($context)) 
            {    
                $context->setError(new KControllerException(
                	'Invalid token or session time-out', KHttpResponse::FORBIDDEN
                ));
                
                return false;
            }
        }
        
        return parent::execute($name, $context); 
    }
	
	/**
     * Generic authorize handler for controller add actions
     * 
     * @param   object      The command context
     * @return  boolean     Can return both true or false.  
     */
    protected function _beforeAdd(KCommandContext $context)
    {
        if(version_compare(JVERSION,'1.6.0','ge')) {
            $result = KFactory::get('lib.joomla.user')->authorise('core.create');
        } else {
            $result = KFactory::get('lib.joomla.user')->get('gid') > 18;
        }
          
        return $result;
    }
    
    /**
     * Generic authorize handler for controller edit actions
     * 
     * @param   object      The command context
     * @return  boolean     Can return both true or false.  
     */
    protected function _beforeEdit(KCommandContext $context)
    {
        if(version_compare(JVERSION,'1.6.0','ge')) {
            $result = KFactory::get('lib.joomla.user')->authorise('core.edit');
        } else {
            $result = KFactory::get('lib.joomla.user')->get('gid') > 19;
        }
              
        return $result;
    }
    
    /**
     * Generic authorize handler for controller delete actions
     * 
     * @param   object      The command context
     * @return  boolean     Can return both true or false.  
     */
    protected function _beforeDelete(KCommandContext $context)
    {
        if(version_compare(JVERSION,'1.6.0','ge')) {
            $result = KFactory::get('lib.joomla.user')->authorise('core.delete');
        } else {
            $result = KFactory::get('lib.joomla.user')->get('gid') > 20;
        }
            
        return $result;
    }
    
	/**
	 * Check the token to prevent CSRF exploits
	 *
	 * @param   object  The command context
	 * @return  boolean Returns FAKSE if the check failed. Otherwise TRUE.
	 */
    protected function _checkToken(KCommandContext $context)
    {
        //Check the token
        if($context->caller->isDispatched())
        {  
            if((KRequest::method() != KHttpRequest::GET)) 
            {     
                if( KRequest::token() !== JUtility::getToken()) {     
                    return false;
                }
            }
        }
        
        return true;
    }
}
