<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsControllerSubscription extends ComAkeebasubsControllerDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this->registerCallback('before.browse', array($this, '_beforeBrowse'));
	}
	
	public function _beforeBrowse(KCommandContext $context)
	{
		// Do we have a user?
		if(KFactory::get('lib.joomla.user')->guest) {
			JError::raiseError('500',JText::_('ACCESS DENIED'));
			return;
		}
		
		if(KRequest::get('get.allUsers','int',0) && (KFactory::get('lib.joomla.user')->gid >= 23)) {
			$this->getModel()->getState()->user_id = null;
		} else {
			$this->getModel()->getState()->user_id = KFactory::get('lib.joomla.user')->id;
		}

		if(KRequest::get('get.allStates','int',0) && (KFactory::get('lib.joomla.user')->gid >= 23)) {
			$this->getModel()->getState()->paystate = null;
		} else {
			$this->getModel()->getState()->paystate = 'C,P';
		}
	}
	
	protected function _actionEdit()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}
	
	protected function _actionAdd()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}
	
	protected function _actionDelete()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}		
} 
