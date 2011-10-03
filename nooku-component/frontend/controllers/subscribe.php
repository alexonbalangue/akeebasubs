<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerSubscribe extends KControllerDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this->registerCallback('before.browse', array($this, '_denyAccess'));
		$this->registerCallback('before.read', array($this, '_denyAccess'));
		$this->registerCallback('before.delete', array($this, '_denyAccess'));
	}
	
	/**
	 * After March 17th, 2011 this is no longer called, but let's make sure it
	 * won't break AGAIN.
	 * 
	 * @param KCommandContext $context
	 */
	protected function _actionAdd(KCommandContext $context)
	{
		return $this->_actionEdit($context);
	}
	
	protected function _actionEdit(KCommandContext $context)
	{
		// For some odd reason, the add action is called twice?! Let's work around it...
		static $result = null;
		if(is_null($result)) {
			$result = $this->getModel()->createNewSubscription();
		}

		if($result) {
			// CRUCIAL! If we don't set the redirect to an empty string, a redirection DOES occur; to the subscription page...
			$this->setRedirect('');
			// Show the (usually auto-submitting) form to the user
			$view = $this->getView();
			$view->setLayout('form');
			echo $view->display();
		} else {
			// Redirect to the level page
			$url = str_replace('&amp;','&', JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&slug='.$this->getModel()->get('slug','slug')));
			$msg = JText::_('COM_AKEEBASUBS_LEVEL_ERR_VALIDATIONOVERALL');
			$this->setRedirect($url, $msg, 'error');
			return false;
		}
	}
	
	/**
	 * On some servers, even though _actionPost is supposed to call either _actionAdd or _actionEdit,
	 * neither is called. Instead we either get a 405 Method not allowed or a "Subscribe view not found".
	 * It doesn't make any sense, but forcibly calling _actionEdit() on POST solves this issue. ARGH!!!
	 */
	protected function _actionPost(KCommandContext $context)
	{
		return $this->_actionEdit($context);
	}
	
	public function unlockData(KCommandContext $context)
	{
		// Intentionally do nothing; the default behaviour is to unlock a row,
		// but this doesn't apply to this view.
	}
	
	public function _denyAccess()
	{
		return false;
	}
	
	// I really DON'T want this to run
	public function lockRow(KCommandContext $context)
	{
	}
	
	// Ditto. We don't want a lockable behaviour
	public function unlockRow(KCommandContext $context)
	{
	}
}