<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerLevels extends FOFController
{
	public function onBeforeBrowse() {
		if(parent::onBeforeBrowse()) {
			$this->getThisModel()
				->limit(0)
				->limitstart(0);
			return true;
		} else {
			return false;
		}
	}
}