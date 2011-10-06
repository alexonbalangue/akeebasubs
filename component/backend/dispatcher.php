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
	public function onBeforeDispatch() {
		$result = parent::onBeforeDispatch();
		
		// Load helpers
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/helpers/cparams.php';
		
		// Handle Live Update requests
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/liveupdate/liveupdate.php';
		if($result && (FOFInput::getCmd('view','',$this->input) == 'liveupdate')) {
			LiveUpdate::handleRequest();
			return false;
		}
		
		return $result;
	}
}