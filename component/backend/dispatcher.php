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
	public function dispatch() {
		// Handle Live Update requests
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/liveupdate/liveupdate.php';
		if((FOFInput::getCmd('view','',$this->input) == 'liveupdate')) {
			LiveUpdate::handleRequest();
			return;
		}
		
		parent::dispatch();
	}
}