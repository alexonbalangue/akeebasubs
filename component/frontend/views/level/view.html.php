<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewLevel extends FOFViewHtml
{
	protected function onRead($tpl = null)
	{
		JRequest::setVar('hidemainmenu', true);

		$model = $this->getModel();
		$this->assignRef( 'item',		$model->getItem() );

		// Get component parameters and pass them to the view
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		$cparams = (object)array(
			'currencypos'		=> AkeebasubsHelperCparams::getParam('currencypos', 'before'),
			'stepsbar'			=> AkeebasubsHelperCparams::getParam('stepsbar', 1),
			'allowlogin'		=> AkeebasubsHelperCparams::getParam('allowlogin', 1),
			'currencysymbol'	=> AkeebasubsHelperCparams::getParam('currencysymbol', '€'),
			'personalinfo'		=> AkeebasubsHelperCparams::getParam('personalinfo', 1),
			'showdiscountfield'	=> AkeebasubsHelperCparams::getParam('showdiscountfield', 1),
			'showtaxfield'		=> AkeebasubsHelperCparams::getParam('showtaxfield', 1),
			'showregularfield'	=> AkeebasubsHelperCparams::getParam('showregularfield', 1),
			'showcouponfield'	=> AkeebasubsHelperCparams::getParam('showcouponfield', 1),
			'hidelonepaymentoption'	=> AkeebasubsHelperCparams::getParam('hidelonepaymentoption', 1),
		);
		$this->cparams = $cparams;

		$this->apply_validation = JFactory::getSession()->get('apply_validation.' . $this->item->akeebasubs_level_id, 0, 'com_akeebasubs') ? 'true' : 'false';
	}
}