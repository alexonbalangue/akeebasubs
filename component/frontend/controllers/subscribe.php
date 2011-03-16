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
		// For some odd reason, the add action is called twice?! Let's work around it...
		static $result = null;
		if(is_null($result)) {
			$result = $this->getModel()->createNewSubscription();
		}

		if($result) {
			// Show the (usually auto-submitting) form to the user
			$view = $this->getView();
			$view->setLayout('form');
			return $view->display();
		} else {
			// Redirect to the level page
			$url = JRoute::_('index.php?option=com_akeebasubs&view=level&id='.$this->getModel()->get('id','int',0));
			$this->setRedirect($url);
			return false;
		}
	}
	
	public function _denyAccess()
	{
		return false;
	}
}