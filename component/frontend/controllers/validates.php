<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerValidates extends FOFController
{
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->setThisModelName('AkeebasubsModelSubscribes');
		$this->csrfProtection = false;
		
		$this->cacheableTasks = array();
	}
	
	public function execute($task) {
		FOFInput::setVar('task','read',$this->input);
		$task = 'read';
		parent::execute($task);
	}
	
	public function read($cachable = false) {
		$data = $this->getThisModel()
			->action('validate')
			->getValidation();
		echo json_encode($data);
		
		JFactory::getApplication()->close();
	}
}