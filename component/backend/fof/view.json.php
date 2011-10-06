<?php
/**
 *  @package FrameworkOnFramework
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * FrameworkOnFramework JSON View class
 * 
 * FrameworkOnFramework is a set of classes whcih extend Joomla! 1.5 and later's
 * MVC framework with features making maintaining complex software much easier,
 * without tedious repetitive copying of the same code over and over again.
 */
class FOFViewJson extends FOFViewHtml
{
	protected function onDisplay($tpl=null)
	{
		// Load the model
		$model = $this->getModel();

		$items = $model->getItemList();
		$this->assignRef( 'items',		$items );
		
		$document = JFactory::getDocument();
		$document->setMimeEncoding('application/json');

		JError::setErrorHandling(E_ALL,'ignore');
		$result = $this->loadTemplate($tpl);
		JError::setErrorHandling(E_WARNING,'callback');
		
		if($result instanceof JException) {
			// Default JSON behaviour in case the template isn't there!
			echo json_encode($items);
			return;
		}
	}
}