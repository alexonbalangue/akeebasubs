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
		$model = $this->getModel();
		$data = $model->getValidation();

		// TODO This is a butt-ugly hack!
		header('Content-type: application/json');
		echo json_encode($data);die();
	}
}