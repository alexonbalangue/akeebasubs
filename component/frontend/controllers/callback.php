<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerCallback extends KControllerAbstract
{
	public function __construct(KConfig $config)
    {
        parent::__construct($config);
        
        $config->action = 'read';
    }
    
    public function execute($action, KCommandContext $context)
    {
    	$newAction = 'read';
    	return parent::execute($newAction, $context);
    }
    
    public function setAction($action)
    {
    	$newAction = 'read';
    	return parent::setAction($newAction);
    }
    
	public function _actionRead(KCommandContext $context)
	{
		$this->_doAkeebasubsCallback();
	}
	
	private function _doAkeebasubsCallback()
	{
		$result = KFactory::get('com://site/akeebasubs.model.subscribes')->runCallback();
		echo $result ? 'OK' : 'FAILED';
		KFactory::get('joomla:application')->close();
	}

	public function _denyAccess()
	{
		return false;
	}
}