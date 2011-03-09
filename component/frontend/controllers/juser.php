<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsControllerJuser extends ComAkeebasubsControllerDefault
{
	protected function _actionBrowse()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}
	
	protected function _actionRead()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}

	protected function _actionEdit()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}
	
	protected function _actionAdd()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}
	
	protected function _actionDelete()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}
} 
