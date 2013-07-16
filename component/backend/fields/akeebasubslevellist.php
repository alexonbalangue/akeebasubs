<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Renders the price of a subscription level and its optional sign-up fee
 */
class FOFFormFieldAkeebasubslevellist extends FOFFormFieldText
{
	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
		$this->value = $this->formatInvTempLevels($this->value);

		return parent::getRepeatable();
	}

	private function formatInvTempLevels($ids)
	{
		if(empty($ids)) {
			return JText::_('COM_AKEEBASUBS_COMMON_LEVEL_ALL');
		}
		if(empty($ids)) {
			return JText::_('COM_AKEEBASUBS_COMMON_LEVEL_NONE');
		}

		if(!is_array($ids)) {
			$ids = explode(',', $ids);
		}

		static $levels;

		if(empty($levels)) {
			$levelsList = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->getItemList(true);
			if(!empty($levelsList)) foreach($levelsList as $level) {
				$levels[$level->akeebasubs_level_id] = $level->title;
			}

			$levels[-1] =  JText::_('COM_AKEEBASUBS_COMMON_LEVEL_NONE');
			$levels[0] =  JText::_('COM_AKEEBASUBS_COMMON_LEVEL_ALL');
		}

		$ret = array();
		foreach($ids as $id) {
			if(array_key_exists($id, $levels)) {
				$ret[] = $levels[$id];
			} else {
				$ret[] = '&mdash;';
			}
		}

		return implode(', ',$ret);
	}
}
