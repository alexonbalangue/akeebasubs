<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

class AkeebasubsViewReports extends FOFViewForm
{
	public function onDisplay($tpl = null)
	{
		// We will use other models, no need to get the reports one
		return true;
	}
}
