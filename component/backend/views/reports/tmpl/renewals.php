<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

	defined('_JEXEC') or die;

	$app = JFactory::getApplication();
	// We will use Tmp models, so we won't mess up with views that are using the same model
	$userModel = FOFModel::getTmpInstance('Users', 'AkeebasubsModel');

	// First of all, let's call model functions to get some relevant info
	//$expiredUser = $userModel->getExpired();

	// Then modify the model in order to get a nice page
	$userModel->filter_order($this->input->getCmd('filter_order', 'akeebasubs_user_id'))
			  ->filter_order_Dir($this->input->getCmd('filter_order_Dir', 'DESC'))
			  ->limit($this->input->getInt('limit', $app->getCfg('list_limit')))
			  ->limitstart($this->input->getInt('limitstart', 0));

	// Since I'm manually handling the model, I have to manually set View params, too
	$this->lists->set('order', $userModel->getState('filter_order', 'id', 'cmd'));
	$this->lists->set('order_Dir', $userModel->getState('filter_order_Dir', 'DESC', 'cmd'));

	$this->setModel($userModel, true);

	$viewTemplate = $this->getRenderedForm();
	echo $viewTemplate;