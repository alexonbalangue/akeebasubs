<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class FOFFormHeaderAkeebasubslevels extends FOFFormHeaderFieldselectable
{
	protected function getOptions()
	{
		static $options = null;

		if (is_null($options))
		{
			$rows = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
						->getList(true);
			foreach($rows as $row)
			{
				$options[] = JHTML::_('select.option', $row->akeebasubs_level_id, $row->title);
			}
		}

		return $options;
	}
}
