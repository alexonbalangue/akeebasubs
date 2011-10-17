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
		'level','levels','message','subscribe','subscription','subscriptions'
	);
	
	public function onBeforeDispatch() {
		if($result = parent::onBeforeDispatch()) {
			// Load helpers
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';

			$view = FOFInput::getCmd('view','',$this->input);
			if(empty($view) || ($view == 'cpanel')) {
				$view = 'levels';
				FOFInput::setVar('view','levels');
			}
			if(!in_array($view, $this->allowedViews)) $result = false;
		}
		
		return $result;
	}
}