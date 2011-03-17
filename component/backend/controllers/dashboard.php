<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerDashboard extends ComAkeebasubsControllerDefault 
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		$this->registerCallback('before.read' , array($this, 'akeebasubsNoBlock'));
	}
	
    protected function _initialize(KConfig $config) 
    {
        $config->append(array(
            'request' => array('layout' => 'default'),
        ));

        parent::_initialize($config);
    }

    public function akeebasubsNoBlock(KCommandContext $context) 
    {
        KRequest::set('get.hidemainmenu', 0);
        return $this;
    }
    
    /**
     * This method is required because KControllerBread (the default ancestor of all controllers)
     * tries to load a row. We have no rows -or even a table- in this particular view, so...
     * 
     * @param $context KCommandContext The command context of the request
     */
    public function _actionDisplay(KCommandContext $context)
    {
		$view = $this->getView();
		    
        //Set the layout in the view
	    if(isset($this->_request->layout)) {
            $view->setLayout($this->_request->layout);
	     }
		
        //Render the view and return the output
		return $view->display();
    }
    
}