<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsDispatcher extends FOFDispatcher
{
	private $allowedViews = array(
		'levels','messages','subscribes','subscriptions','validates','callbacks'
	);
	
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$this->defaultView = 'levels';
	}
	
	public function onBeforeDispatch() {
		if($result = parent::onBeforeDispatch()) {
			// Load helpers
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';

			$view = FOFInput::getCmd('view',$this->defaultView, $this->input);
			if(empty($view) || ($view == 'cpanel')) {
				$view = 'levels';
			}
			FOFInput::setVar('view',$view,$this->input);
			if(!in_array(FOFInflector::pluralize($view), $this->allowedViews)) $result = false;
		}
		
		return $result;
	}
	
	public function getTask($view) {
		$task = parent::getTask($view);
		
		switch($view) {
			case 'level':
				if($task == 'add') $task = 'read';
		}
		
		return $task;
	}
}