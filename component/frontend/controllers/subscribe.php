<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

class ComAkeebasubsControllerSubscribe extends ComAkeebasubsControllerDefault
{
	protected function _actionValidate(KCommandContext $context)
	{
		// Set the model action to "validate" so that the JSON view knows what to do
		$model = $this->getModel();
		$model->set('action','validate');
		// Return nada. Let the view sort it out (which is wrong, because the view now
		// partially becomes a controller).
		return null;
	}
}