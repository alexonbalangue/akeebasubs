<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewLevel extends FOFViewHtml
{	
	protected function onRead($tpl = null) {
		JRequest::setVar('hidemainmenu', true);
		$model = $this->getModel();
		$this->assignRef( 'item',		$model->getItem() );
	}
}