<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerMakecoupons extends FOFController
{
	public function execute($task) {
		if(!in_array($task, array('overview','generate'))) {
			$task = 'overview';
		}
		parent::execute($task);
	}
	
	public function overview($cacheable = false) {
		$this->display(false);
	}
	
	public function generate($cacheable = false) {
		$model = $this->getThisModel();
		
		$model->makeCoupons();
		
		$this->setRedirect('index.php?option=com_akeebasubs&view=makecoupons');
	}
}