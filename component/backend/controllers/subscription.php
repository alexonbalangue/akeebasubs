<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerSubscription extends ComAkeebasubsControllerDefault 
{

	public function _actionBrowse(KCommandContext $context)
	{
		if(KRequest::get('get.groupbydate','int') == 1) {
			if(KFactory::get('joomla:user')->guest) {
				return false;
			} else {
				$list = $this->getModel()
					->limit(0)
					->offset(0)
					->getSalesList();
				header('Content-type: application/json');
				echo json_encode($list);
				KFactory::get('joomla:application')->close();
			}
		} else {
			return parent::_actionBrowse($context);
		}
	}
}
