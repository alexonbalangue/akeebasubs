<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerUserinfos extends FOFController
{
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$this->csrfProtection = false;
		
		$this->cacheableTasks = array('browse');
	}
	
	public function execute($task) {
		// Only task browse and save are valid
		$allowedTasks = array('browse', 'save');
		// Take browse as the default task
		if(! in_array($task,$allowedTasks)) {
			$task = 'browse';
		}
		
		FOFInput::setVar('task',$task,$this->input);
		
		parent::execute($task);
	}

	/**
	 * Initialize the user data
	 * 
	 * @return bool
	 */
	public function onBeforeBrowse()
	{	
		$view = $this->getThisView();
		$model = $this->getThisModel();
		
		// Get the user model and load the user data
		$userparams = FOFModel::getTmpInstance('Users','AkeebasubsModel')
				->user_id(JFactory::getUser()->id)
				->getMergedData();
		$view->assign('userparams', $userparams);
		
		$cache = (array)($model->getData());
		if($cache['firstrun']) {
			foreach($cache as $k => $v) {
				if(empty($v)) {
					if(property_exists($userparams, $k)) {
						$cache[$k] = $userparams->$k;
					}
				}
			}
		}
		$view->assign('cache', (array)$cache);
		$view->assign('validation', $model->getValidation());
		
		return true;
	}
	
	/**
	 * Always allow the active user to save its user data
	 * 
	 * @return bool
	 */
	protected function onBeforeSave()
	{
		return true;
	}
	
}