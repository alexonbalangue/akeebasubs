<?php

/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */
defined('_JEXEC') or die();

class AkeebasubsControllerLevels extends F0FController
{

	public function __construct($config = array())
	{
		parent::__construct($config);

		if ($this->input->getBool('caching', true))
		{
			$this->cacheableTasks = array('browse');
		}
		else
		{
			$this->cacheableTasks = array();
		}
	}

	public function onBeforeBrowse()
	{
		// Do we have an affiliate code?
		$affid = $this->input->getInt('affid', 0);
		if ($affid)
		{
			$session = JFactory::getSession();
			$session->set('affid', $affid, 'com_akeebasubs');
		}

		$params = JFactory::getApplication()->getPageParameters();
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
		$appInput = JFactory::getApplication()->input;

		if (!empty($ids))
		{
			$appInput->set('ids', $ids);
			$appInput->set('_x_userid', JFactory::getUser()->id);
		}

		/** @var AkeebasubsModelTaxhelper $taxHelper */
		$taxHelper = F0FModel::getTmpInstance('Taxhelper', 'AkeebasubsModel');
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
			$session = JFactory::getSession();
			$session->set('coupon', $coupon, 'com_akeebasubs');
		}

		// Continue parsing page options
		if (parent::onBeforeBrowse())
		{
			$noClear = $this->input->getBool('no_clear', false);

			if (!$noClear)
			{
				$model = $this->getThisModel()
					->clearState()
					->clearInput()
					->savestate(0)
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

			$this->getThisModel()->access_user_id(JFactory::getUser()->id);

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Use the slug instead of the id to read a record
	 *
	 * @return bool
	 */
	public function onBeforeRead()
	{
		// Do we have an affiliate code?
		$affid = $this->input->getInt('affid', 0);

		if ($affid)
		{
			$session = JFactory::getSession();
			$session->set('affid', $affid, 'com_akeebasubs');
		}

		// Fetch the subscription slug from page parameters
		$params = JFactory::getApplication()->getPageParameters();
		$pageslug = $params->get('slug', '');
		$slug = $this->input->getString('slug', null);

		if ($pageslug)
		{
			$slug = $pageslug;
			$this->input->set('slug', $slug);
		}

		$this->getThisModel()->setIDsFromRequest();
		$this->getThisModel()->access_user_id(JFactory::getUser()->id);
		$id = $this->getThisModel()->getId();

		if (!$id && $slug)
		{
			$item = F0FModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->slug($slug)
				->getFirstItem();

			if (!empty($item->akeebasubs_level_id))
			{
				$id = $item->akeebasubs_level_id;
				$this->getThisModel()->setId($item->akeebasubs_level_id);
			}
		}

		// Working around Progressive Caching
		JFactory::getApplication()->input->set('slug', $slug);
		JFactory::getApplication()->input->set('id', $id);
		$this->registerUrlParams(array(
			'slug' => 'STRING',
			'id'   => 'INT',
		));

		// Get the current level
		$level = $this->getThisModel()->getItem();

		// Make sure the level exists
		if ($level->akeebasubs_level_id == 0)
		{
			return false;
		}

		// Make sure the level is published
		if (!$level->enabled)
		{
			return false;
		}

		// Check for "Forbid renewal" conditions
		if ($level->only_once)
		{
			$levels = F0FModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->slug($level->slug)
				->only_once(1)
				->getItemList();
			if (!count($levels))
			{
				// User trying to renew a level which is marked as only_once
				if ($level->renew_url)
				{
					JFactory::getApplication()->redirect($level->renew_url);
				}

				return false;
			}
			$this->getThisModel()->setId($id);
		}

		$view = $this->getThisView();

		// Get the user model and load the user data
		$userparams = F0FModel::getTmpInstance('Users', 'AkeebasubsModel')
			->user_id(JFactory::getUser()->id)
			->getMergedData(JFactory::getUser()->id);
		$view->assign('userparams', $userparams);

		// Load any cached user supplied information
		$vModel = F0FModel::getAnInstance('Subscribes', 'AkeebasubsModel')
			->slug($slug)
			->id($id);

		// Should we use the coupon code saved in the session?
		$session = JFactory::getSession();
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
		$view->assign('cache', (array)$cache);
		$view->assign('validation', $vModel->getValidation());

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
		$app = JFactory::getApplication();

		$registeredurlparams = null;

		if (property_exists($app, 'registeredurlparams'))
		{
			$registeredurlparams = $app->registeredurlparams;
		}

		if (empty($registeredurlparams))
		{
			$registeredurlparams = new stdClass;
		}

		foreach ($urlparams AS $key => $value)
		{
			// Add your safe url parameters with variable type as value {@see JFilterInput::clean()}.
			$registeredurlparams->$key = $value;
		}

		$app->registeredurlparams = $registeredurlparams;
	}
}