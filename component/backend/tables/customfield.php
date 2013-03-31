<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableCustomfield extends FOFTable
{
	protected function onBeforeStore($updateNulls)
	{
		$result = parent::onBeforeStore($updateNulls);
		if($result) {
			$slug			= $this->getColumnAlias('slug');
			if(property_exists($this, $slug)) {
				$this->$slug = str_replace('-', '_', $this->$slug);
			}
		}

		// Make sure assigned subscription levels really do exist and normalize the list
		if(!empty($this->akeebasubs_level_id)) {
			if(is_array($this->akeebasubs_level_id)) {
				$subs = $this->akeebasubs_level_id;
			} else {
				$subs = explode(',', $this->akeebasubs_level_id);
			}
			if(empty($subs)) {
				$this->akeebasubs_level_id = '';
			} else {
				$subscriptions = array();
				foreach($subs as $id) {
					$subObject = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
						->setId($id)
						->getItem();
					$id = null;
					if(is_object($subObject)) {
						if($subObject->akeebasubs_level_id > 0) {
							$id = $subObject->akeebasubs_level_id;
						}
					}
					if(!is_null($id)) $subscriptions[] = $id;
				}
				$this->akeebasubs_level_id = implode(',', $subscriptions);
			}
		}

		return $result;
	}
}