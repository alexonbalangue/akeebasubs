<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class F0FFormHeaderAkeebasubslevels extends F0FFormHeaderFieldselectable
{
	protected function getOptions()
	{
		static $options = null;

		if (is_null($options))
		{
			$rows = F0FModel::getTmpInstance('Levels', 'AkeebasubsModel')
						->getList(true);
			foreach($rows as $row)
			{
				$options[] = JHTML::_('select.option', $row->akeebasubs_level_id, $row->title);
			}
		}

		return $options;
	}
}
