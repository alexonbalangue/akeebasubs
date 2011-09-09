<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');


class ComAkeebasubsDatabaseRowLevel extends KDatabaseRowTable
{
	public function delete()
	{
		$result = false;
		
		if($this->isConnected())
		{
			// Do we have subscriptions on that level?
			$subs = KFactory::get('com://admin/akeebasubs.model.subscriptions')
				->level($this->id)
				->getTotal();

			if($subs) {
				$this->setStatusMessage(JText::_('COM_AKEEBASUBS_LEVELS_ERR_EXISTINGSUBS'));
				return false;
			} else {
				return parent::delete();
			}
		}
		
		return (bool) $result;
	}
}