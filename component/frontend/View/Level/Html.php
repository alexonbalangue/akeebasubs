<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\View\Level;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use Akeeba\Subscriptions\Site\Model\Levels;

class Html extends \FOF30\View\DataView\Html
{
	/**
	 * The record loaded (read, edit, add views)
	 *
	 * @var  Levels
	 */
	protected $item = null;

	/**
	 * Should I apply validation? Please note that this is a string, not a boolean! It's used directly inside the
	 * Javascript.
	 *
	 * @var  string  "true" or "false". This is NOT a boolean, it's a string.
	 */
	public $apply_validation = '';

	/**
	 * Some component parameters used in this view
	 *
	 * @var  object
	 */
	public $cparams = null;

	/**
	 * Executes before the read task, allows us to push data to the view
	 */
	protected function onBeforeRead()
	{
		parent::onBeforeRead();

		// Force the layout
		$this->layout = 'default';

		$this->dnt = $this->getDoNotTrackStatus();

		// Get component parameters and pass them to the view
		$componentParams = (object)array(
			'currencypos'           => ComponentParams::getParam('currencypos', 'before'),
			'stepsbar'              => ComponentParams::getParam('stepsbar', 1),
			'allowlogin'            => ComponentParams::getParam('allowlogin', 1),
			'currencysymbol'        => ComponentParams::getParam('currencysymbol', '€'),
			'personalinfo'          => ComponentParams::getParam('personalinfo', 1),
			'showdiscountfield'     => ComponentParams::getParam('showdiscountfield', 1),
			'showtaxfield'          => ComponentParams::getParam('showtaxfield', 1),
			'showregularfield'      => ComponentParams::getParam('showregularfield', 1),
			'showcouponfield'       => ComponentParams::getParam('showcouponfield', 1),
			'hidelonepaymentoption' => ComponentParams::getParam('hidelonepaymentoption', 1),
			'reqcoupon'             => ComponentParams::getParam('reqcoupon', 0),
		);

		$this->cparams = $componentParams;

		$this->apply_validation = \JFactory::getSession()->get('apply_validation.' . $this->item->akeebasubs_level_id, 0, 'com_akeebasubs') ? 'true' : 'false';

		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		\JFactory::getApplication()->setHeader('X-Cache-Control', 'False', true);
	}

	/**
	 * Gets the status of the Do Not Track preference set in the client's browser and communicated through an HTTP
	 * header.
	 *
	 * @return  bool
	 */
	private function getDoNotTrackStatus()
	{
		if (isset($_SERVER['HTTP_DNT']))
		{
			if ($_SERVER['HTTP_DNT'] == 1)
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