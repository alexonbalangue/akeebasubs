<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Dispatcher;

defined('_JEXEC') or die;

use FOF30\Container\Container;

class Dispatcher extends \FOF30\Dispatcher\Dispatcher
{
	/** @var   string  The name of the default view, in case none is specified */
	public $defaultView = 'ControlPanel';

	public function onBeforeDispatch()
	{
		// Render submenus as drop-down navigation bars powered by Bootstrap
		$this->container->renderer->setOption('linkbar_style', 'classic');

		// Load common CSS and JavaScript
		$this->container->template->addCSS('media://com_akeebasubs/css/backend.css');
		$this->container->template->addJS('media://com_akeebasubs/js/backend.js');
	}
}