<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use FOF30\Controller\Controller;

class ControlPanel extends Controller
{
	protected function onBeforeDefault()
	{
		/** @var \Akeeba\Subscriptions\Admin\Model\ControlPanel $model */
		$model = $this->getModel();
		$model
			->checkAndFixDatabase()
			->saveMagicVariables();

		/** @var \Akeeba\Subscriptions\Admin\Model\Updates $updatesModel */
		$updatesModel = $this->getModel('Updates');
		$updatesModel->refreshUpdateSite();
	}
}