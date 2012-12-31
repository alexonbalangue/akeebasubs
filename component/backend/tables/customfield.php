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
		return $result;
	}
}