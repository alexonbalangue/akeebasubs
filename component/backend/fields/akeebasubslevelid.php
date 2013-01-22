<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class FOFFormFieldAkeebasubslevelid extends FOFFormFieldList
{
	protected function getOptions()
	{
		static $options = null;
		
		if (is_null($options))
		{
			$noneoption = $this->element['none'] ? $this->element['none'] : null;
			if ($noneoption)
			{
				$options[] = JHtml::_('select.option', '', JText::_($noneoption));
			}
			
			$enabled = $this->element['enabled'] ? $this->element['enabled'] : '';
			
			$levels = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->enabled($enabled)
				->getList(true);
			
			if (!empty($levels))
			{
				foreach ($levels as $level)
				{
					$options[] = JHtml::_('select.option',
						$level->akeebasubs_level_id, $level->title);
				}
			}
		}
		
		reset($options);
		
		return $options;
	}
}
