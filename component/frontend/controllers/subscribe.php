<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerSubscribe extends ComAkeebasubsControllerDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this->registerCallback('before.browse', array($this, '_denyAccess'));
		$this->registerCallback('before.read', array($this, '_denyAccess'));
		$this->registerCallback('before.edit', array($this, '_denyAccess'));
		$this->registerCallback('before.delete', array($this, '_denyAccess'));
	}
	
	protected function _actionAdd(KCommandContext $context)
	{
		$result = $this->getModel()->createNewSubscription();

		if($result) {
			// Show the auto-submitting form to the user
			$view = $this->getView();
			$view->setLayout('form');
			return $view->display();
		} else {
			// Redirect to the level page
			$url = 'index.php?option=com_akeebasubs&view=level&id='.$this->getModel()->get('id','int',0);
			$this->setRedirect($url);
			return false;
		}
	}
	
	protected function _actionCallback(KCommandContext $context)
	{
		$result = $this->getModel()->runCallback();
		if($result) die('Success');
		die('Failed');
	}
	
	public function _denyAccess()
	{
		return false;
	}
}