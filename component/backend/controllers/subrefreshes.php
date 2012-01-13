<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerSubrefreshes extends FOFController
{
	public function process()
	{
		$model = $this->getModel('Subscriptions', 'AkeebasubsModel');
		$model->setState('limitstart', FOFInput::getInt('forceoffset',0,$this->input));
		$model->setState('limit', FOFInput::getInt('forcelimit',100,$this->input));
		
		$list = $model
			->refresh(1)->getList();
		
		if(count($list)) {
			jimport('joomla.plugin.helper');
			JPluginHelper::importPlugin('akeebasubs');
			foreach($list as $item) {
				$user_id = $item->user_id;
				$app = JFactory::getApplication();
				$jResponse = $app->triggerEvent('onAKUserRefresh', array($user_id));
			}
		}
		$response = array(
			'total'	=> $model->getTotal(),
			'processed'	=> count($list)
		);
		
		echo json_encode($response);
		
		// Return
		JFactory::getApplication()->close();
	}
}