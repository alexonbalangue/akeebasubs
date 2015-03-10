<?php
/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2015 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

namespace Akeeba\Subscriptions\Admin\PluginAbstracts;

use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Admin\Model\Users;
use FOF30\Container\Container;
use JFactory;
use JFile;
use JLoader;
use JPlugin;
use JRegistry;
use JText;

defined('_JEXEC') or die();

/**
 * Akeeba Subscriptions payment plugin abstract class
 */
abstract class AkeebasubsBase extends JPlugin
{

	/**
	 * Levels to Groups to Add mapping
	 *
	 * @var  array
	 */
	protected $addGroups = array();

	/**
	 * Levels to Groups to Remove mapping
	 *
	 * @var  array
	 */
	protected $removeGroups = array();

	/**
	 * Default path where view templates for rendering this plugin's fields interface can be found
	 *
	 * @var  string
	 */
	protected $templatePath = '';

	/**
	 * The name of the plugin, as it's made known to the Akeeba Subscriptions component
	 *
	 * @var  string
	 */
	protected $name = '';

	/**
	 * The DI container for Akeeba Subscriptions
	 *
	 * @var  Container
	 */
	protected $container = null;

	/**
	 * Public constructor
	 *
	 * @param   object  $subject       The object to observe
	 * @param   array   $config        An optional associative array of configuration settings.
	 *                                 Recognized key values include 'name', 'group', 'params', 'language',
	 *                                 'templatePath' (this list is not meant to be comprehensive).
	 */
	public function __construct(&$subject, $config = array())
	{
		if (!array_key_exists('params', $config))
		{
			$config['params'] = new JRegistry('');
		}

		if (!is_object($config['params']))
		{
			$config['params'] = new JRegistry($config['params']);
		}

		parent::__construct($subject, $config);

		$name   = $config['name'];
		$templatePath = $config['templatePath'];

		$this->templatePath = $templatePath;
		$this->name         = $name;

		// Load the language files
		$jLanguage = JFactory::getLanguage();
		$jLanguage->load('plg_akeebasubs_' . $name, JPATH_ADMINISTRATOR, 'en-GB', true);
		$jLanguage->load('plg_akeebasubs_' . $name, JPATH_ADMINISTRATOR, $jLanguage->getDefault(), true);
		$jLanguage->load('plg_akeebasubs_' . $name, JPATH_ADMINISTRATOR, null, true);

		// Load the container
		$this->container = Container::getInstance('com_akeebasubs');

		$strAddGroups = null;
		$strRemoveGroups = null;

		// Do we have values from the Olden Days?
		if (isset($config['params']))
		{
			$configParams = @json_decode($config['params']);

			if (property_exists($configParams, 'addgroups'))
			{
				$strAddGroups = $configParams->addgroups;
			}
			else
			{
				$strAddGroups = null;
			}

			if (property_exists($configParams, 'removegroups'))
			{
				$strRemoveGroups = $configParams->removegroups;
			}
			else
			{
				$strRemoveGroups = null;
			}
		}

		if (!empty($strAddGroups) || !empty($strRemoveGroups))
		{
			// Load level to group mapping from plugin parameters
			$this->addGroups    = $this->parseGroups($strAddGroups);
			$this->removeGroups = $this->parseGroups($strRemoveGroups);
			// Do a transparent upgrade
			$this->upgradeSettings($config);
		}
		else
		{
			$this->loadGroupAssignments();
		}
	}

	/**
	 * Renders the configuration page in the component's back-end
	 *
	 * @param   Levels  $level
	 *
	 * @return  \stdClass
	 */
	public function onSubscriptionLevelFormRender(Levels $level)
	{
		JLoader::import('joomla.filesystem.file');
		$filename = $this->templatePath . '/override/default.php';

		if (!JFile::exists($filename))
		{
			$filename = $this->templatePath . '/tmpl/default.php';
		}

		$addgroupsKey    = strtolower($this->name) . '_addgroups';
		$removegroupsKey = strtolower($this->name) . '_addgroups';

		if (!isset($level->params->$addgroupsKey))
		{
			$level->params->$addgroupsKey = array();
		}

		if (!isset($level->params->$removegroupsKey))
		{
			$level->params->$removegroupsKey = array();
		}

		@ob_start();

		include_once $filename;

		$html = @ob_get_clean();

		$ret = (object) array(
			'title' => JText::_('PLG_AKEEBASUBS_' . $this->name . '_TAB_TITLE'),
			'html'  => $html
		);

		return $ret;
	}

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 *
	 * @param   Subscriptions  $row   The subscriptions row
	 * @param   array          $info  The row modification information
	 *
	 * @return  void
	 */
	public function onAKSubscriptionChange(Subscriptions $row, array $info)
	{
		if (is_null($info['modified']) || empty($info['modified']))
		{
			return;
		}

		if (array_key_exists('enabled', (array) $info['modified']))
		{
			$this->onAKUserRefresh($row->user_id);
		}
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param   int  $user_id  The Joomla! user ID to refresh information for.
	 *
	 * @return  void
	 */
	abstract public function onAKUserRefresh($user_id);

	/**
	 * Load the groups to add / remove for a user
	 *
	 * @param   int     $user_id              The Joomla! user ID
	 * @param   array   $addGroups            Array of groups to add (output)
	 * @param   array   $removeGroups         Array of groups to remove (output)
	 * @param   string  $addGroupsVarName     Property name of the map of the groups to add
	 * @param   string  $removeGroupsVarName  Property name of the map of the groups to remove
	 *
	 * @return  void  We modify the $addGroups and $removeGroups arrays directly
	 */
	protected function loadUserGroups($user_id, array &$addGroups, array &$removeGroups, $addGroupsVarName = 'addGroups', $removeGroupsVarName = 'removeGroups')
	{
		// Make sure we're configured
		if (empty($this->$addGroupsVarName) && empty($this->$removeGroupsVarName))
		{
			return;
		}

		// Get all of the user's subscriptions
		/** @var  Users  $user */
		$user = $this->container->factory->model('Users');
		$subscriptions = $user->subscriptions;

		// Make sure there are subscriptions set for the user
		if (!count($subscriptions))
		{
			return;
		}

		// Get the initial list of groups to add/remove from
		/** @var Subscriptions $sub */
		foreach ($subscriptions as $sub)
		{
			$level = $sub->akeebasubs_level_id;

			if ($sub->enabled)
			{
				// Enabled subscription, add groups
				if (empty($this->$addGroupsVarName))
				{
					continue;
				}

				if (!array_key_exists($level, $this->$addGroupsVarName))
				{
					continue;
				}

				$addGroupsVar = $this->$addGroupsVarName;
				$groups       = $addGroupsVar[ $level ];

				foreach ($groups as $group)
				{
					if (!in_array($group, $addGroups))
					{
						if (is_numeric($group) && !($group > 0))
						{
							continue;
						}

						$addGroups[] = $group;
					}
				}
			}
			else
			{
				// Disabled subscription, remove groups
				if (empty($this->$removeGroupsVarName))
				{
					continue;
				}

				if (!array_key_exists($level, $this->$removeGroupsVarName))
				{
					continue;
				}

				$removeGroupsVar = $this->$removeGroupsVarName;
				$groups          = $removeGroupsVar[ $level ];

				foreach ($groups as $group)
				{
					if (!in_array($group, $removeGroups))
					{
						if (is_numeric($group) && !($group > 0))
						{
							continue;
						}

						$removeGroups[] = $group;
					}
				}
			}
		}

		// If no groups are detected, do nothing
		if (empty($addGroups) && empty($removeGroups))
		{
			return;
		}

		// Sort the lists
		asort($addGroups);
		asort($removeGroups);

		// Clean up the remove groups: if we are asked to both add and remove a user
		// from a group, add wins.
		if (!empty($removeGroups) && !empty($addGroups))
		{
			$temp         = $removeGroups;
			$removeGroups = array();

			foreach ($temp as $group)
			{
				if (!in_array($group, $addGroups))
				{
					$removeGroups[] = $group;
				}
			}
		}
	}

	/**
	 * Load the add / remove group to level ID map from the subscription level options
	 *
	 * @return  void
	 */
	protected function loadGroupAssignments()
	{
		$this->addGroups    = array();
		$this->removeGroups = array();

		/** @var Levels $model */
		$model           = $this->container->factory->model('Levels');
		$levels          = $model->get(true);
		$addgroupsKey    = strtolower($this->name) . '_addgroups';
		$removegroupsKey = strtolower($this->name) . '_removegroups';

		if (!empty($levels))
		{
			foreach ($levels as $level)
			{
				if (isset($level->params->$addgroupsKey))
				{
					$this->addGroups[ $level->akeebasubs_level_id ] = array_filter($level->params->$addgroupsKey);
				}

				if (isset($level->params->$removegroupsKey))
				{
					$this->removeGroups[ $level->akeebasubs_level_id ] = array_filter($level->params->$removegroupsKey);
				}
			}
		}
	}
}