<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerSubscription extends ComAkeebasubsControllerDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this->registerCallback('before.browse', array($this, '_beforeBrowse'));
		$this->registerCallback('before.read', array($this, '_beforeRead'));
		
		$this->registerCallback('before.edit', array($this, '_denyAccess'));
		$this->registerCallback('before.add', array($this, '_denyAccess'));
		$this->registerCallback('before.delete', array($this, '_denyAccess'));
	}
	
	public function _beforeBrowse(KCommandContext $context)
	{
		// Do we have a user?
		if(JFactory::getUser()->guest) {
			// Obsolete behaviour: show unauthorized error message
			// JError::raiseError('403',JText::_('ACCESS DENIED'));

			// Show login page
			$juri = JURI::getInstance();
			$myURI = base64_encode($juri->toString());
			$com = version_compare(JVERSION, '1.6.0', 'ge') ? 'users' : 'user';
			JFactory::getApplication()->redirect(JURI::base().'index.php?option=com_'.$com.'&view=login&return='.$myURI);
			return;
		}
		
		if(KRequest::get('get.allUsers','int',0) && (JFactory::getUser()->gid >= 23)) {
			$this->getModel()->getState()->user_id = null;
		} else {
			$this->getModel()->getState()->user_id = JFactory::getUser()->id;
		}

		if(KRequest::get('get.allStates','int',0) && (JFactory::getUser()->gid >= 23)) {
			$this->getModel()->getState()->paystate = null;
		} else {
			$this->getModel()->getState()->paystate = 'C,P';
		}
	}
	
	public function _beforeRead(KCommandContext $context)
	{
		// Do we have a user?
		if(JFactory::getUser()->guest) {
			JError::raiseError('403',JText::_('ACCESS DENIED'));
			return;
		}

		// Simple trick: filter rows by the current user's ID. If he tries to access someone else's
		// subscription information, the ID will be 0. In that case, BUSTED! Of course, the same thing
		// happens if he tries to access a non-existent subscription ID.
		$this->getModel()->getState()->user_id = JFactory::getUser()->id;
		$this->getModel()->getState()->paystate = 'C,P';
		if($this->getModel()->getItem()->id == 0) {
			JError::raiseError('403',JText::_('ACCESS DENIED'));
			return;
		}
	}
	
	public function _denyAccess()
	{
		return false;
	}	
} 
