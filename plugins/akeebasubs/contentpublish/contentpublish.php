<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

if (!include_once(JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php'))
{
	return;
}

JLoader::import('joomla.application.component.helper');

class plgAkeebasubsContentpublish extends plgAkeebasubsAbstract
{
	/** @var bool Should I re-publish core Joomla! articles? */
	protected $publishCore = array();

	/** @var bool Should I re-publish K2 items? */
	protected $publishK2 = array();

	/** @var bool Should I re-publish SobiPro items? */
	protected $publishSobipro = array();

	/** @var bool Should I re-publish ZOO items? */
	protected $publishZOO = array();

	/** @var bool Should I unpublish core Joomla! articles? */
	protected $unpublishCore = array();

	/** @var bool Should I unpublish K2 items? */
	protected $unpublishK2 = array();

	/** @var bool Should I unpublish SobiPro items? */
	protected $unpublishSobipro = array();

	/** @var bool Should I unpublish ZOO items? */
	protected $unpublishZOO = array();

	/** @var array ZOO apps to republish items */
	protected $addGroups = array();

	/** @var array ZOO apps to unpublish items */
	protected $removeGroups = array();

	public function __construct(&$subject, $name, $config = array(), $templatePath = null) {
		parent::__construct($subject, $name, $config, $templatePath);

		$this->loadLanguage();
	}

	/**
	 * Renders the configuration page in the component's back-end
	 *
	 * @param AkeebasubsTableLevel $level
	 * @return object
	 */
	public function onSubscriptionLevelFormRender(AkeebasubsTableLevel $level)
	{
		JLoader::import('joomla.filesystem.file');
		$filename = dirname(__FILE__).'/override/default.php';
		if(!JFile::exists($filename)) {
			$filename = dirname(__FILE__).'/tmpl/default.php';
		}

		if (!property_exists($level->params, 'contentpublish_addgroups'))
		{
			$level->params->contentpublish_addgroups = array();
		}
		if (!property_exists($level->params, 'contentpublish_removegroups'))
		{
			$level->params->contentpublish_removegroups = array();
		}
		if (!property_exists($level->params, 'contentpublish_publishcore'))
		{
			$level->params->contentpublish_publishcore = false;
		}
		if (!property_exists($level->params, 'contentpublish_publishk2'))
		{
			$level->params->contentpublish_publishk2 = false;
		}
		if (!property_exists($level->params, 'contentpublish_publishsobipro'))
		{
			$level->params->contentpublish_publishsobipro = false;
		}
		if (!property_exists($level->params, 'contentpublish_publishzoo'))
		{
			$level->params->contentpublish_publishzoo = false;
		}
		if (!property_exists($level->params, 'contentpublish_unpublishcore'))
		{
			$level->params->contentpublish_unpublishcore = false;
		}
		if (!property_exists($level->params, 'contentpublish_unpublishk2'))
		{
			$level->params->contentpublish_unpublishk2 = false;
		}
		if (!property_exists($level->params, 'contentpublish_unpublishsobipro'))
		{
			$level->params->contentpublish_unpublishsobipro = false;
		}
		if (!property_exists($level->params, 'contentpublish_unpublishzoo'))
		{
			$level->params->contentpublish_unpublishzoo = false;
		}

		JLoader::import('joomla.filesystem.folder');
		if(JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_zoo'))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->select(array(
					$db->qn('id'),
					$db->qn('name'),
				))
				->from($db->qn('#__zoo_application'));
			$db->setQuery($query);
			$appsRaw = $db->loadObjectList();
		}
		else
		{
			$appsRaw = null;
		}
		$zooApps = array();
		if (!empty($appsRaw))
		{
			$zooApps[] = JHtml::_('select.option', '', JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_SELECTNONE'));
			foreach ($appsRaw as $app)
			{
				$zooApps[] = JHtml::_('select.option', $app->id, $app->name);
			}
		}

		@ob_start();
		include_once $filename;
		$html = @ob_get_clean();

		$ret = (object)array(
			'title'	=> JText::_('PLG_AKEEBASUBS_CONTENTPUBLISH_TAB_TITLE'),
			'html'	=> $html
		);

		return $ret;
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		static $hasZoo = null;
		static $hasK2 = null;
		static $hasSobipro = null;

		if(is_null($hasZoo) || is_null($hasK2) || is_null($hasSobipro))
		{
			JLoader::import('joomla.filesystem.folder');
			$hasZoo = JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_zoo');
			$hasK2 = JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_k2');
			$hasSobipro = JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_sobipro');
			if(@include_once( JPATH_ROOT . '/components/com_sobipro/lib/sobi.php' ))
			{
				if (!method_exists('Sobi', 'Initialise'))
				{
					// We require SOBIPro 1.1 or later
					$hasSobipro = false;
				}
			}
			else
			{
				$hasSobipro = false;
			}
		}

		// Get all of the user's subscriptions
		$subscriptions = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->user_id($user_id)
			->getList();

		// Make sure there are subscriptions set for the user
		if (!count($subscriptions))
		{
			return;
		}

		// Get active/inactive subscription level IDs
		$active = array();
		foreach ($subscriptions as $subscription)
		{
			if (!$subscription->enabled)
			{
				continue;
			}
			if (!in_array($subscription->akeebasubs_level_id, $active))
			{
				$active[] = $subscription->akeebasubs_level_id;
			}
		}

		$inactive = array();
		foreach ($subscriptions as $subscription)
		{
			if ($subscription->enabled)
			{
				continue;
			}
			if (!in_array($subscription->akeebasubs_level_id, $active))
			{
				$inactive[] = $subscription->akeebasubs_level_id;
			}
		}

		$db = JFactory::getDbo();

		// Unpublish articles for inactive subscriptions
		if (!empty($inactive))
		{
			foreach ($inactive as $level_id)
			{
				if (array_key_exists($level_id, $this->unpublishCore))
				{
					if ($this->unpublishCore[$level_id])
					{
						// Unpublish core articles
						$query = $db->getQuery(true)
							->update($db->qn('#__content'))
							->set($db->qn('state').' = '.$db->q('0'))
							->where($db->qn('created_by').' = '.$db->q($user_id))
							->where($db->qn('state').' <= '.$db->q('1'));
						$db->setQuery($query);
						$db->execute();
					}
				}

				if (array_key_exists($level_id, $this->unpublishK2) && $hasK2)
				{
					if ($this->unpublishK2[$level_id])
					{
						// Unpublish K2 items
						$query = $db->getQuery(true)
							->update($db->qn('#__k2_items'))
							->set($db->qn('published').' = '.$db->q('0'))
							->where($db->qn('created_by').' = '.$db->q($user_id));
						$db->setQuery($query);
						$db->execute();
					}
				}

				if (array_key_exists($level_id, $this->unpublishSobipro) && $hasSobipro)
				{
					if ($this->unpublishSobipro[$level_id])
					{
						if (!class_exists('Sobi'))
						{
							@include_once( JPATH_ROOT . '/components/com_sobipro/lib/sobi.php' );
						}

						if (class_exists('Sobi'))
						{
							Sobi::Initialise( );

							// Unpublish SOBI Pro items
							$query = $db->getQuery(true)
								->select($db->qn('id'))
								->from($db->qn('#__sobipro_object'))
								->where($db->qn('oType').' = '.$db->q('entry'))
								->where($db->qn('owner').' = '.$db->q($user_id))
								->where($db->qn('state').' = '.$db->q(1))
								;
							$db->setQuery($query);
							$ids = $db->loadColumn();

							if (count($ids))
							{
								foreach($ids as $id)
								{
									SPFactory::Entry( $id )->unpublish();
								}
							}
						}
					}
				}

				if (array_key_exists($level_id, $this->unpublishZOO) && array_key_exists($level_id, $this->removeGroups) && $hasZoo)
				{
					if ($this->unpublishZOO[$level_id])
					{
						$apps = $this->removeGroups[$level_id];
						if (!empty($apps))
						{
							$temp = array();
							foreach ($apps as $app)
							{
								$temp[] = $db->q($app);
							}
							// Unpublish ZOO items
							$query = $db->getQuery(true)
								->update($db->qn('#__zoo_item'))
								->set($db->qn('state').' = '.$db->q('0'))
								->where($db->qn('created_by').' = '.$db->q($user_id))
								->where($db->qn('state').' <= '.$db->q('1'))
								->where($db->qn('application_id').' IN ('.implode(',', $temp).')');
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
		}

		// Publish articles for active subscriptions
		if (!empty($active))
		{
			foreach ($active as $level_id)
			{
				if (array_key_exists($level_id, $this->publishCore))
				{
					if ($this->publishCore[$level_id])
					{
						// Publish core Joomla! articles
						$query = $db->getQuery(true)
							->update($db->qn('#__content'))
							->set($db->qn('state').' = '.$db->q('1'))
							->where($db->qn('created_by').' = '.$db->q($user_id))
							->where($db->qn('state').' = '.$db->q('0'));
						$db->setQuery($query);
						$db->execute();
					}
				}

				if (array_key_exists($level_id, $this->publishK2) && $hasK2)
				{
					if ($this->publishK2[$level_id])
					{
						// Publish K2 content
						$query = $db->getQuery(true)
							->update($db->qn('#__k2_items'))
							->set($db->qn('published').' = '.$db->q('1'))
							->where($db->qn('created_by').' = '.$db->q($user_id))
							->where($db->qn('published').' = '.$db->q('0'));
						$db->setQuery($query);
						$db->execute();
					}
				}

				if (array_key_exists($level_id, $this->publishSobipro) && $hasSobipro)
				{
					if ($this->publishSobipro[$level_id])
					{
						if (!class_exists('Sobi'))
						{
							@include_once( JPATH_ROOT . '/components/com_sobipro/lib/sobi.php' );
						}

						if (class_exists('Sobi'))
						{
							Sobi::Initialise();

							// Publish SOBI Pro items
							$query = $db->getQuery(true)
								->select($db->qn('id'))
								->from($db->qn('#__sobipro_object'))
								->where($db->qn('oType').' = '.$db->q('entry'))
								->where($db->qn('owner').' = '.$db->q($user_id))
								->where($db->qn('state').' = '.$db->q(0))
								;
							$db->setQuery($query);
							$ids = $db->loadColumn();

							if (count($ids))
							{
								foreach($ids as $id)
								{
									SPFactory::Entry( $id )->publish();
								}
							}
						}
					}
				}

				if (array_key_exists($level_id, $this->publishZOO) && array_key_exists($level_id, $this->addGroups) && $hasZoo)
				{
					if ($this->publishZOO[$level_id])
					{
						$apps = $this->addGroups[$level_id];
						if (!empty($apps))
						{
							$temp = array();
							foreach ($apps as $app)
							{
								$temp[] = $db->q($app);
							}
							// Publish ZOO items
							$query = $db->getQuery(true)
								->update($db->qn('#__zoo_item'))
								->set($db->qn('state').' = '.$db->q('1'))
								->where($db->qn('created_by').' = '.$db->q($user_id))
								->where($db->qn('state').' = '.$db->q('0'))
								->where($db->qn('application_id').' IN ('.implode(',', $temp).')');
							$db->setQuery($query);
							$db->execute();
						}
					}
				}
			}
		}
	}

	protected function loadGroupAssignments()
	{
		$this->addGroups = array();
		$this->removeGroups = array();

		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		if(!empty($levels))
		{
			foreach($levels as $level)
			{
				$save = false;
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					continue;
				}

				if (property_exists($level->params, 'contentpublish_addgroups'))
				{
					$this->addGroups[$level->akeebasubs_level_id] = $level->params->contentpublish_addgroups;
				}

				if (property_exists($level->params, 'contentpublish_removegroups'))
				{
					$this->removeGroups[$level->akeebasubs_level_id] = $level->params->contentpublish_removegroups;
				}

				if (property_exists($level->params, 'contentpublish_publishcore'))
				{
					$this->publishCore[$level->akeebasubs_level_id] = $level->params->contentpublish_publishcore;
				}

				if (property_exists($level->params, 'contentpublish_publishk2'))
				{
					$this->publishK2[$level->akeebasubs_level_id] = $level->params->contentpublish_publishk2;
				}

				if (property_exists($level->params, 'contentpublish_publishsobipro'))
				{
					$this->publishSobipro[$level->akeebasubs_level_id] = $level->params->contentpublish_publishsobipro;
				}

				if (property_exists($level->params, 'contentpublish_publishzoo'))
				{
					$this->publishZOO[$level->akeebasubs_level_id] = $level->params->contentpublish_publishzoo;
				}

				if (property_exists($level->params, 'contentpublish_unpublishcore'))
				{
					$this->unpublishCore[$level->akeebasubs_level_id] = $level->params->contentpublish_unpublishcore;
				}

				if (property_exists($level->params, 'contentpublish_unpublishk2'))
				{
					$this->unpublishK2[$level->akeebasubs_level_id] = $level->params->contentpublish_unpublishk2;
				}

				if (property_exists($level->params, 'contentpublish_unpublishsobipro'))
				{
					$this->unpublishSobipro[$level->akeebasubs_level_id] = $level->params->contentpublish_unpublishsobipro;
				}

				if (property_exists($level->params, 'contentpublish_unpublishzoo'))
				{
					$this->unpublishZOO[$level->akeebasubs_level_id] = $level->params->contentpublish_unpublishzoo;
				}
			}
		}
	}

	/**
	 * Not used in this plugin
	 */
	protected function upgradeSettings($config = array())
	{
		return true;
	}

	/**
	 * Not used in this plugin
	 */
	protected function groupToId($title)
	{
		return null;
	}

	/**
	 * Not used in this plugin
	 */
	protected function getGroups() {
		return array();
	}
}