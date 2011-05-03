<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerDashboard extends ComDefaultControllerPage
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
}