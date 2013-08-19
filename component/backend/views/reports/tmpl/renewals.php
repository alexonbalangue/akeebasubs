<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

	defined('_JEXEC') or die;

	JHtml::_('behavior.framework');
	$app       = JFactory::getApplication();
	$userModel = FOFModel::getTmpInstance('Users', 'AkeebasubsModel');

	// Injects the new input, so I can use all FOF built-in function without messing up other views
	$userModel->setInput($this->input);

	// First of all, let's call model functions to get some relevant info
	//$expiredUser = $userModel->getExpired();

	// Then modify the model in order to get a nice page
	$userModel->limit($this->input->getInt('limit', $app->getCfg('list_limit')))
			  ->limitstart($this->input->getInt('limitstart', 0));


	// Since I'm manually handling the model, I have to manually set View params, too
	$this->lists->set('order', $userModel->getState('filter_order', 'id', 'cmd'));
	$this->lists->set('order_Dir', $userModel->getState('filter_order_Dir', 'DESC', 'cmd'));

	$this->setModel($userModel, true);

	$viewTemplate = $this->getRenderedForm();

	// Injecting a new input field doing a string replace. Bad bad programmer!
	$viewTemplate = str_replace('</form>', '<input type="hidden" name="layout" value="renewals" /></form>', $viewTemplate);

	echo $viewTemplate;