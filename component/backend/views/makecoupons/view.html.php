<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewMakecoupons extends FOFViewHtml
{
	public function onOverview($tpl = null) {
		$model = $this->getModel();
		
		$session = JFactory::getSession();
		$coupons = $session->get('makecoupons.coupons', false, 'com_akeebasubs');
		$session->set('makecoupons.coupons', null, 'com_akeebasubs');
		
		$this->assignRef('coupons',		$coupons);
		
		return true;
	}
}