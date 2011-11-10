<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerSubscriptions extends FOFController
{
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$this->cacheableTasks = array('read');
	}
	
	public function execute($task) {
		$allowedTasks = array('browse', 'read');
		if(in_array($task,array('edit','add'))) $task = 'read';
		if(!in_array($task,$allowedTasks)) return false;
		
		FOFInput::setVar('task',$task,$this->input);
		
		parent::execute($task);
	}
	
	public function onBeforeBrowse()
	{
		// Do we have a user?
		if(JFactory::getUser()->guest) {
			// Show login page
			$juri = JURI::getInstance();
			$myURI = base64_encode($juri->toString());
			$com = version_compare(JVERSION, '1.6.0', 'ge') ? 'users' : 'user';
			JFactory::getApplication()->redirect(JURI::base().'index.php?option=com_'.$com.'&view=login&return='.$myURI);
			return false;
		}
		
		if(FOFInput::getInt('allUsers',0,$this->input) && (JFactory::getUser()->gid >= 23)) {
			$this->getThisModel()->user_id(null);
		} else {
			$this->getThisModel()->user_id(JFactory::getUser()->id);
		}

		if(FOFInput::getInt('allStates',0,$this->input) && (JFactory::getUser()->gid >= 23)) {
			$this->getThisModel()->paystate(null);
		} else {
			$this->getThisModel()->paystate('C,P');
		}
		
		// Let me cheat. If the request doesn't specify how many records to show, show them all!
		if(!JRequest::getInt('limit',0) && !JRequest::getInt('limitstart',0)) {
			$this->getThisModel()->limit(0);
			$this->getThisModel()->limitstart(0);
		}
		
		return true;
	}
	
	public function onBeforeRead()
	{
		// Do we have a user?
		if(JFactory::getUser()->guest) {
			// Show login page
			$juri = JURI::getInstance();
			$myURI = base64_encode($juri->toString());
			$com = version_compare(JVERSION, '1.6.0', 'ge') ? 'users' : 'user';
			JFactory::getApplication()->redirect(JURI::base().'index.php?option=com_'.$com.'&view=login&return='.$myURI);
			return false;
		}

		// Make sure it's the current user's subscription
		$this->getThisModel()->setIDsFromRequest();
		$this->getThisModel()->user_id(JFactory::getUser()->id);
		$this->getThisModel()->paystate('C,P');

		$list = $this->getThisModel()->getItemList();
		$found = false;
		if(!empty($list)) foreach($list as $id => $sub) {
			if($sub->akeebasubs_subscription_id == $this->getThisModel()->getId()) {
				$found = true;
				break;
			}
		}
		
		if(!$found) {
			JError::raiseError('403',JText::_('ACCESS DENIED'));
			return false;
		}
		
		return true;
	}
} 
