<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerCallbacks extends FOFController
{

	public function __construct($config = array()) {
		parent::__construct($config);

		$this->setThisModelName('AkeebasubsModelSubscribes');
		$this->csrfProtection = false;

		$this->cacheableTasks = array();
	}


	public function execute($task) {
		$task = 'read';
		$this->input->set('task','read');
		parent::execute($task);
	}

	public function read($cachable = false) {
		$result = FOFModel::getTmpInstance('Subscribes', 'AkeebasubsModel')
			->paymentmethod($this->input->getCmd('paymentmethod','none'))
			->runCallback();
		echo $result ? 'OK' : 'FAILED';
		JFactory::getApplication()->close();
	}

	public function onBeforeRead()
	{
		return true;
	}
}