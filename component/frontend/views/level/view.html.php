<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsViewLevel extends F0FViewHtml
{
	protected function onRead($tpl = null)
	{
		JRequest::setVar('hidemainmenu', true);

		$model = $this->getModel();
		$this->item = $model->getItem();
		$this->dnt  = $this->do_not_track();

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
			'reqcoupon'			=> AkeebasubsHelperCparams::getParam('reqcoupon', 0),
		);
		$this->cparams = $cparams;

		$this->apply_validation = JFactory::getSession()->get('apply_validation.' . $this->item->akeebasubs_level_id, 0, 'com_akeebasubs') ? 'true' : 'false';

		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		JResponse::setHeader('X-Cache-Control', 'False', true);
	}

	private function do_not_track()
	{
		if (isset($_SERVER['HTTP_DNT']))
		{
			if ($_SERVER['HTTP_DNT']==1)
			{
				return true;
			}
		}
		elseif (function_exists('getallheaders'))
		{
			foreach (getallheaders() as $k => $v)
			{
				if (strtolower($k) === "dnt" && $v == 1)
				{
					return true;
				}
			}
		}
		return false;
	}
}