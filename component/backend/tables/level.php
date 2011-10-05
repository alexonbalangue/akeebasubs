<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableLevel extends FOFTable
{
	public function check() {
		$result = true;
		
		if(empty($this->title)) {
			$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_TITLE'));
			$result = false;
		}
		
		if(empty($this->slug)) {
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/filter.php';
			$this->slug = AkeebasubsHelperFilter::toSlug($this->title);
		}
		
		$existingItems = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
			->slug($this->slug)
			->getList(true);
		
		if(!empty($existingItems)) {
			$count = 0;
			$k = $this->getKeyName();
			foreach($existingItems as $item) {
				if($item->$k != $this->$k) $count++;
			}
			if($count) {
				$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_SLUGUNIQUE'));
				$result = false;
			}
		}
		
		if(empty($this->image)) {
			$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_IMAGE'));
			$result = false;
		}
		
		if($this->duration < 1) {
			$this->setError(JText::_('COM_AKEEBASUBS_LEVEL_ERR_LENGTH'));
			$result = false;
		}
		
		return $result;
	}
}