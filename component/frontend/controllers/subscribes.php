<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerSubscribes extends FOFController
{
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$this->csrfProtection = false;
		
		$this->cacheableTasks = array();
	}
	
	public function execute($task) {
		$task = 'add';
		
		FOFInput::setVar('task',$task,$this->input);
		parent::execute($task);
	}
	
	public function add() {
		$result = $this->getThisModel()->createNewSubscription();
		if($result) {
			$view = $this->getThisView();
			$view->setLayout('form');
			$view->assign('form', $this->getThisModel()->getForm());
			$view->setModel($this->getThisModel(),true);
			$view->display();
		} else {
			$url = str_replace('&amp;','&', JRoute::_('index.php?option=com_akeebasubs&view=level&layout=default&slug='.$this->getThisModel()->slug));
			$msg = JText::_('COM_AKEEBASUBS_LEVEL_ERR_VALIDATIONOVERALL');
			$this->setRedirect($url, $msg, 'error');
			return false;
		}
	}
	
	/**
	 * I don't want an ACL check when creating a new subscription
	 * 
	 * @return bool
	 */
	public function onBeforeAdd() {
		return true;
	}
}