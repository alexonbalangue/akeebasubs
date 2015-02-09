<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

	defined('_JEXEC') or die;

	$app       = JFactory::getApplication();
	$userModel = F0FModel::getTmpInstance('Users', 'AkeebasubsModel');

	// I have to do this trick since Joomla select list input always inject a "no value" option
	// In this way I can force a value even if the user selected the "no value" option
	if($this->input->getString('getRenewals', 1) == '')
	{
		$this->input->set('getRenewals', 1);
	}

	$newInput = $this->input;
	$newInput->set('getRenewals', $this->input->getInt('getRenewals', 1));

	// Injects the new input, so I can use all F0F built-in function without messing up other views
	$userModel->setInput($newInput);

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