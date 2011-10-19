<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerLevels extends FOFController
{
	public function onBeforeBrowse() {
		if(parent::onBeforeBrowse()) {
			$this->getThisModel()
				->limit(0)
				->limitstart(0);
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Use the slug instead of the id to read a record
	 * 
	 * @return bool
	 */
	public function onBeforeRead()
	{
		// Fetch the subscription slug from page parameters
		$params	= JFactory::getApplication()->getPageParameters();
		$pageslug	= $params->get('slug','');
		$slug = FOFInput::getString('slug',null,$this->input);
		if(empty($slug) && empty($pageslug)) {
			FOFInput::setVar('slug', $slug, $this->input);
			$slug = $pageslug;
		}
		
		$this->getThisModel()->setIDsFromRequest();
		$id = $this->getThisModel()->getId();
		if(!$id && $slug) {
			$records = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->slug($slug)
				->getItemList();
			if(!empty($records)) {
				$item = array_pop($records);
				$this->getThisModel()->setId($item->akeebasubs_level_id);
			}
		}
		
		$view = $this->getThisView();
		
		// Get the user model and load the user data
		$view->assign('userparams',
			FOFModel::getTmpInstance('Users','AkeebasubsModel')
				->user_id(JFactory::getUser()->id)
				->getMergedData()
		);
		
		// Load any cached user supplied information
		$vModel = FOFModel::getAnInstance('Subscribes','AkeebasubsModel')
			->slug($slug);
		$view->assign('cache', (array)($vModel->getData()));
		$view->assign('validation', $vModel->getValidation());
		
		return true;
	}
}