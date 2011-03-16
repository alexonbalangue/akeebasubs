<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerCallback extends ComAkeebasubsControllerDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this->registerCallback('before.browse', array($this, '_denyAccess'));
		$this->registerCallback('before.delete', array($this, '_denyAccess'));
	}

	public function _actionRead(KCommandContext $context)
	{
		$this->_doCallback();
	}
	
	public function _actionAdd(KCommandContext $context)
	{
		$this->_doCallback();
	}
	
	public function _actionEdit(KCommandContext $context)
	{
		$this->_doCallback();
	}
	
	private function _doCallback()
	{
		$result = KFactory::get('site::com.akeebasubs.model.subscribes')->runCallback();
		echo $result ? 'OK' : 'FAILED';
		KFactory::get('lib.koowa.application')->close();
	}

	public function _denyAccess()
	{
		return false;
	}
}