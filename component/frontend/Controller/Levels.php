<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Site\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Controller\Mixin;
use Akeeba\Subscriptions\Site\Model\TaxHelper;
use FOF30\Container\Container;
use FOF30\Controller\DataController;
use FOF30\View\Exception\AccessForbidden;

class Levels extends DataController
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

		if ($this->input->getBool('caching', true))
		{
			$this->cacheableTasks = ['browse'];
		}
		else
		{
			$this->cacheableTasks = [];
		}
	}

	public function onBeforeBrowse()
	{
		$params = \JFactory::getApplication()->getPageParameters();

		$ids = $params->get('ids', '');

		if (is_array($ids) && !empty($ids))
		{
			$ids = implode(',', $ids);

			if ($ids === '0')
			{
				$ids = '';
			}
		}
		else
		{
			$ids = '';
		}

		// Working around Progressive Caching
		$appInput = \JFactory::getApplication()->input;

		if (!empty($ids))
		{
			$appInput->set('ids', $ids);
			$appInput->set('_x_userid', JFactory::getUser()->id);
		}

		/** @var TaxHelper $taxHelper */
		$taxHelper = $this->getModel('TaxHelper');
		$taxParameters = $taxHelper->getTaxDefiningParameters();
		$appInput->set('_akeebasubs_taxParameters', $taxParameters);

		$this->registerUrlParams(array(
			'ids'                       => 'ARRAY',
			'_akeebasubs_taxParameters' => 'ARRAY',
			'no_clear'                  => 'BOOL',
			'_x_userid'                 => 'INT',
			'coupon'                    => 'STRING'
		));

		// Save a possible coupon code in the session
		$coupon = $this->input->getString('coupon');

		if (!empty($coupon))
		{
			$session = \JFactory::getSession();
			$session->set('coupon', $coupon, 'com_akeebasubs');
		}

		// Continue parsing page options
		/** @var \Akeeba\Subscriptions\Site\Model\Levels $model */
		$model = $this->getModel();
		$noClear = $this->input->getBool('no_clear', false);

		if (!$noClear)
		{
			$model
				->clearState()
				->savestate(0)
				->setIgnoreRequest(1)
				->limit(0)
				->limitstart(0)
				->enabled(1)
				->only_once(1)
				->filter_order('ordering')
				->filter_order_Dir('ASC');

			if (!empty($ids))
			{
				$model->id($ids);
			}
		}

		$model->access_user_id(\JFactory::getUser()->id);
	}

	/**
	 * Use the slug instead of the id to read a record
	 *
	 * @return bool
	 */
	public function onBeforeRead()
	{
		// Fetch the subscription slug from page parameters
		$params = \JFactory::getApplication()->getPageParameters();
		$pageslug = $params->get('slug', '');
		$slug = $this->input->getString('slug', null);

		if ($pageslug)
		{
			$slug = $pageslug;
			$this->input->set('slug', $slug);
		}

		/** @var \Akeeba\Subscriptions\Site\Model\Levels $model */
		$model = $this->getModel();

		$this->getIDsFromRequest($model, true);
		$model->access_user_id(\JFactory::getUser()->id);
		$id = $model->getId();

		if (!$id && $slug)
		{
			$model
				->slug($slug)
				->firstOrFail();

			$id = $model->getId();
		}

		// Working around Progressive Caching
		\JFactory::getApplication()->input->set('slug', $slug);
		\JFactory::getApplication()->input->set('id', $id);

		$this->registerUrlParams(array(
			'slug' => 'STRING',
			'id'   => 'INT',
		));

		// Make sure the level exists
		if ($model->akeebasubs_level_id == 0)
		{
			return false;
		}

		// Make sure the level is published
		if (!$model->enabled)
		{
			return false;
		}

		// Check for "Forbid renewal" conditions
		if ($model->only_once)
		{
			$levels = $model->getClone()->savestate(false)->setIgnoreRequest(true)->clearState()->reset(true, true);
			$levels
				->slug($model->slug)
				->only_once(1)
				->get(true);

			if (!count($levels))
			{
				// User trying to renew a level which is marked as only_once
				if ($model->renew_url)
				{
					\JFactory::getApplication()->redirect($model->renew_url);
				}

				return false;
			}
		}

		$view = $this->getView();

		// Get the user model and load the user data
		$userparams = $this->getModel('Users')
			->getMergedData(\JFactory::getUser()->id);

		$view->userparams = $userparams;

		// Load any cached user supplied information
		$vModel = $this->getModel('Subscribes')
			->slug($slug)
			->id($id);

		// Should we use the coupon code saved in the session?
		$session = \JFactory::getSession();
		$sessionCoupon = $session->get('coupon', null, 'com_akeebasubs');
		$inputCoupon = $this->input->getString('coupon');

		if (empty($inputCoupon) && !empty($sessionCoupon))
		{
			$vModel->coupon($sessionCoupon);
			$session->set('coupon', null, 'com_akeebasubs');
		}

		$cache = (array)($vModel->getData());

		if ($cache['firstrun'])
		{
			foreach ($cache as $k => $v)
			{
				if (empty($v))
				{
					if (property_exists($userparams, $k))
					{
						$cache[$k] = $userparams->$k;
					}
				}
			}
		}
		$view->cache = (array)$cache;
		$view->validation = $vModel->getValidation();

		// If we accidentally have the awesome layout set, please reset to default
		if ($this->layout == 'awesome')
		{
			$this->layout = 'default';
		}

		if ($this->layout == 'item')
		{
			$this->layout = 'default';
		}

		if (empty($this->layout))
		{
			$this->layout = 'default';
		}

		return true;
	}

	protected function registerUrlParams($urlparams = array())
	{
		$app = \JFactory::getApplication();

		$registeredurlparams = null;

		if (property_exists($app, 'registeredurlparams'))
		{
			$registeredurlparams = $app->registeredurlparams;
		}

		if (empty($registeredurlparams))
		{
			$registeredurlparams = new \stdClass;
		}

		foreach ($urlparams AS $key => $value)
		{
			// Add your safe url parameters with variable type as value {@see JFilterInput::clean()}.
			$registeredurlparams->$key = $value;
		}

		$app->registeredurlparams = $registeredurlparams;
	}
}