<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerSubscriptions extends FOFController
{
	public function browse($cachable = false) {
		// When groupbydate is set to 1 we force a JSON view which returns the
		// sales info (subscriptions, net amount) grouped by date. You can use
		// the since/until or other filter in the URL to filter the whole lot
		if(FOFInput::getInt('groupbydate',0,$this->input) == 1) {
			if(JFactory::getUser()->guest) {
				return false;
			} else {
				$list = $this->getThisModel()
					->limit(0)
					->limitstart(0)
					->getItemList();
				header('Content-type: application/json');
				echo json_encode($list);
				JFactory::getApplication()->close();
			}
		}

		// Limit what a front-end user can do
		if(JFactory::getApplication()->isSite()) {
			if(JFactory::getUser()->guest) {
				return false;
			} else {
				FOFInput::setVar('user_id',JFactory::getUser()->id,$this->input);
			}
		}
		
		return parent::browse($cachable);
	}
}