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
     * Normally, I should NOT need this method. Nooku should just display the template.
     */
    public function _actionDisplay(KCommandContext $context)
    {
    	$result = $this->execute('read', $context);
    	
    	$view = $this->getView();
    	
    	//Set the layout in the view
	    if($view instanceof KViewTemplate && isset($this->_request->layout)) {
		    $view->setLayout($this->_request->layout);
	    }
	    
	    // I don't know why it happens, but if I return the result instead of echoing it,
	    // I get a blank page. WTF?!
		echo $view->display();
    }
    
}