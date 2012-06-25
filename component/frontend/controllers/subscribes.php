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
		$id = $this->getThisModel()->getState('id',0,'int');
		$slug = FOFInput::getString('slug',null,$this->input);
		if(!$id && $slug) {
			$item = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->slug($slug)
				->getFirstItem();
			if(!empty($item->akeebasubs_level_id)) {
				$id = $item->akeebasubs_level_id;
			}
		}
		
		$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')->setId($id)->getItem();
		if($level->only_once) {
			$levels = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->slug($level->slug)
				->only_once(1)
				->getItemList();
			if(!count($levels)) {
				// User trying to renew a level which is marked as only_once
				return false;
			}
		}
		$this->getThisModel()->setState('id',$id);
		
		$result = $this->getThisModel()->createNewSubscription();
		if($result) {
			$view = $this->getThisView();
			$view->setLayout('form');
			$view->assign('form', $this->getThisModel()->getForm());
			$model = $this->getThisModel();
			$view->setModel($model,true);
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