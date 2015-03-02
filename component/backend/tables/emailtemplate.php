<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsTableEmailtemplate extends F0FTable
{
	protected function onAfterCopy($oid)
	{
		// Let's unpublish the new copied item
		$this->publish(null, -1);

		return parent::onAfterCopy($oid);
	}
}