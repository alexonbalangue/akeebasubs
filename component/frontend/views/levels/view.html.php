<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewLevels extends F0FViewHtml
{
	protected function onDisplay($tpl = null)
	{
		$subIDs = array();
		$user = JFactory::getUser();
		if($user->id) {
			$mysubs = F0FModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->user_id($user->id)
				->paystate('C')
				->getItemList();
			if(!empty($mysubs)) foreach($mysubs as $sub) {
				$subIDs[] = $sub->akeebasubs_level_id;
			}
			$subIDs = array_unique($subIDs);
		}
		$this->subIDs = $subIDs;

		parent::onDisplay($tpl);
	}
}