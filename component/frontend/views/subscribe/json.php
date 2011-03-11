<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsViewSubscribeJson extends KViewJson
{
	public function display()
	{
		$model = $this->getModel();
		if($model->get('action','display')) {
			$data = $model->getValidation();
			return json_encode($data);
		} else {
			return parent::display();
		}	
	}
}