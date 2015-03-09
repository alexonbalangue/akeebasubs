<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Controller\Mixin;
use FOF30\Container\Container;
use FOF30\Controller\DataController;
use FOF30\Controller\Exception\ItemNotFound;
use FOF30\View\Exception\AccessForbidden;

class Subscriptions extends DataController
{
	use Mixin\PredefinedTaskList;

	/**
	 * Overridden. Limit the tasks we're allowed to execute.
	 *
	 * @param   Container  $container
	 * @param   array      $config
	 */
	public function __construct(Container $container, array $config = array())
	{
		parent::__construct($container, $config);

		$this->predefinedTaskList = ['browse', 'read'];
	}

	/**
	 * Runs before the browse task. Makes sure we're displaying the list of our own subscriptions.
	 */
	protected function onBeforeBrowse()
	{
		// If no user is logged in we can't display any subscriptions
		$user = $this->container->platform->getUser();

		if ($user->guest)
		{
			throw new AccessForbidden;
		}

		// Force filter by the currently logged in user
		$this->getModel()->user_id($user->id);
	}

	/**
	 * Runs before the read task. Makes sure we're not trying to access someone else's subscription.
	 */
	protected function onBeforeRead()
	{
		$model = $this->getModel();

		// If the record is not found throw a generic "Access forbidden" error
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if ($model->getId() != reset($ids))
			{
				throw new AccessForbidden;
			}
		}

		// If the subscription user does not match the currently logged in user throw an "Access forbidden" error
		if ($model->user_id != $this->container->platform->getUser()->id)
		{
			throw new AccessForbidden;
		}
	}
}