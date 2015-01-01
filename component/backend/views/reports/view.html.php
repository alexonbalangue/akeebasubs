<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

class AkeebasubsViewReports extends F0FViewHtml
{
	public function onDisplay($tpl = null)
	{
		// No need to bother the model, we're just displaying a bunch of links
		return true;
	}
}
