<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerMessages extends FOFController
{
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$this->setThisModelName('AkeebasubsModelLevels');
		$this->csrfProtection = false;
		
		$this->cacheableTasks = array();
	}
	
	public function execute($task) {
		$task = 'read';
		FOFInput::setVar('task','read',$this->input);
		parent::execute($task);
	}
	
	/**
	 * Use the slug instead of the id to read a record
	 * 
	 * @return bool
	 */
	public function onBeforeRead()
	{
		$this->getThisModel()->setIDsFromRequest();
		$id = $this->getThisModel()->getId();
		$slug = FOFInput::getString('slug',null,$this->input);
		if(!$id && $slug) {
			$records = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->slug($slug)
				->getItemList();
			if(!empty($records)) {
				$item = array_pop($records);
				$this->getThisModel()->setId($item->akeebasubs_level_id);
			}
		}
		
		$subid = FOFInput::getInt('subid',0,$this->input);
		$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->setId($subid)
			->getItem();
		$this->getThisView()->assign('subscription',$subscription);
		
		return true;
	}
}