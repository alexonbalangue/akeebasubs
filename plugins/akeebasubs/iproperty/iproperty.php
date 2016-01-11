<?php

/**
 * @package        akeebasubs
 * @copyright      Copyright (c)2010-2016 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license        GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */
defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\PluginAbstracts\AkeebasubsBase;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Admin\Model\Users;

class plgAkeebasubsIproperty extends AkeebasubsBase
{

	/**
	 * Known configuration parameters per level
	 *
	 * @var array
	 */
	private $knownParamsKeys = ['maxlistings', 'maxflistings', 'maxagents', 'maxfagents', 'maximgs',];

	/**
	 * Public constructor
	 *
	 * @param   object  $subject       The object to observe
	 * @param   array   $config        An optional associative array of configuration settings.
	 *                                 Recognized key values include 'name', 'group', 'params', 'language',
	 *                                 'templatePath' (this list is not meant to be comprehensive).
	 */
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name         = 'iproperty';

		parent::__construct($subject, $config);
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param   int  $user_id  The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Get all of the user's subscriptions
		/** @var Subscriptions $subscriptionsModel */
		$subscriptionsModel = $this->container->factory->model('Subscriptions')->tmpInstance();

		$subscriptions = $subscriptionsModel
			->user_id($user_id)
			->get(true);

		// Make sure there are subscriptions set for the user
		if (!$subscriptions->count())
		{
			return;
		}

		// Do I have to activate the user?
		$mustActivate   = false;
		$mustDeactivate = false;
		$params         = array();

		/** @var Subscriptions $sub */
		foreach ($subscriptions as $sub)
		{
			$level = $sub->akeebasubs_level_id;

			if ($sub->enabled)
			{
				if (array_key_exists($level, $this->addGroups))
				{
					$mustActivate = true;
					$params       = $this->mixParams($params, $this->addGroups[ $level ]);
				}
			}
			else
			{
				if (array_key_exists($level, $this->addGroups))
				{
					$mustDeactivate = true;
				}
			}
		}

		if ($mustActivate && $mustDeactivate)
		{
			$mustDeactivate = false;
		}

		if ($mustActivate)
		{
			$this->publishAgent($user_id, $params);
		}
		elseif ($mustDeactivate)
		{
			$this->unpublishAgent($user_id);
		}
	}

	// =========================================================================
	// INTERNAL API SECTION
	// =========================================================================

	/**
	 * Publish an IP Agent linked with Joomla! user ID $user_id and limit
	 * parameters $params
	 *
	 * @param   integer $user_id The Joomla! user ID
	 * @param   array   $params  IP parameters for the agent
	 *
	 * @return  void
	 */
	private function publishAgent($user_id, $params)
	{
		// First, check if we already have agents for that user ID
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
		            ->select('*')
		            ->from($db->qn('#__iproperty_agents'))
		            ->where($db->qn('user_id') . ' = ' . $db->q($user_id));
		$db->setQuery($query);
		$agents = $db->loadObjectList();

		if (empty($agents))
		{
			// If we do not have any existing agents, create a new company and a new agent record
			// Load the user data
			/** @var Users $userModel */
			$userModel = $this->container->factory->model('Users')->tmpInstance();
			$user = $userModel->getMergedData($user_id);

			// Create a company
			$name = empty($user->businessname) ? $user->name : $user->businessname;

			JLoader::import('cms.application.helper');
			$alias = \JApplicationHelper::stringURLSafe($name);

			$company = (object) array(
				'name'        => $name,
				'alias'       => $alias,
				'description' => '&nbsp;',
				'street'      => $user->address1,
				'city'        => $user->city,
				'locstate'    => $user->state,
				'province'    => '',
				'postcode'    => $user->zip,
				'country'     => $user->country,
				'fax'         => '',
				'phone'       => '',
				'email'       => $user->email,
				'website'     => '',
				'featured'    => 0,
				'icon'        => 'nopic.png',
				'clicense'    => '',
				'language'    => '',
				'state'       => 1,
				'params'      => json_encode($params)
			);

			$db->insertObject('#__iproperty_companies', $company);

			$companyid = $db->insertid();

			// Create an agent
			$nameParts = explode(' ', $user->name, 2);

			$firstName = $nameParts[0];
			if (count($nameParts) > 1)
			{
				$lastName = $nameParts[1];
			}
			else
			{
				$lastName = '';
			}

			$alias = \JApplicationHelper::stringURLSafe($user->name);

			$agent = (object) array(
				'agent_type' => 1,
				'hometeam'   => 0,
				'fname'      => $firstName,
				'lname'      => $lastName,
				'alias'      => $alias,
				'company'    => $companyid,
				'email'      => $user->email,
				'phone'      => '',
				'mobile'     => '',
				'fax'        => '',
				'street'     => $user->address1,
				'street2'    => $user->address2,
				'city'       => $user->city,
				'locstate'   => $user->state,
				'province'   => '',
				'postcode'   => $user->zip,
				'country'    => $user->country,
				'website'    => '',
				'bio'        => '&nbsp;',
				'user_id'    => $user_id,
				'featured'   => 0,
				'icon'       => 'nopic.png',
				'msn'        => '',
				'skype'      => '',
				'gtalk'      => '',
				'linkedin'   => '',
				'facebook'   => '',
				'twitter'    => '',
				'social1'    => '',
				'alicense'   => '',
				'state'      => 1,
				'params'     => '',
			);

			$db->insertObject('#__iproperty_agents', $agent);
		}
		else
		{
			// If we have existing agents, we need to do two things:
			// a. Make sure all agent records are enabled
			$query = $db->getQuery(true)
			            ->update($db->qn('#__iproperty_agents'))
			            ->set($db->qn('state') . ' = ' . $db->q(1))
			            ->where($db->qn('user_id') . ' = ' . $db->q($user_id));
			$db->setQuery($query);
			$db->execute();

			// b. Update the company parameters
			$company_ids_raw = array();

			foreach ($agents as $agent)
			{
				$company_ids_raw[] = $agent->company;
			}

			$company_ids_raw = array_unique($company_ids_raw);
			$company_ids     = array();

			foreach ($company_ids_raw as $cid)
			{
				$company_ids[] = $db->q($cid);
			}

			$query = $db->getQuery(true)
			            ->select('*')
			            ->from($db->qn('#__iproperty_companies'))
			            ->where($db->qn('id') . ' IN (' . implode(',', $company_ids) . ')');
			$db->setQuery($query);
			$companies = $db->loadObjectList();

			foreach ($companies as $company)
			{
				$cparams         = json_decode($company->params, true);
				$cparams         = $this->mixParams($cparams, $params);
				$company->params = json_encode($cparams);
				$company->state  = 1;

				$db->updateObject('#__iproperty_companies', $company, 'id');
			}
		}
	}

	/**
	 * Remove the IP Agent and Company attached to the Joomla! user ID $user_id
	 *
	 * @param   integer $user_id The Joomla! user ID
	 *
	 * @return  void
	 */
	private function unpublishAgent($user_id)
	{
		// First, check if we already have agents for that user ID
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
		            ->select('*')
		            ->from($db->qn('#__iproperty_agents'))
		            ->where($db->qn('user_id') . ' = ' . $db->q($user_id));
		$db->setQuery($query);
		$agents = $db->loadObjectList();

		if (empty($agents))
		{
			return;
		}

		// Unpublish agent records
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
		            ->update($db->qn('#__iproperty_agents'))
		            ->set($db->qn('state') . ' = ' . $db->q(0))
		            ->where($db->qn('user_id') . ' = ' . $db->q($user_id));
		$db->setQuery($query);
		$db->execute();

		// b. Unpublish the companies of the user
		$company_ids_raw = array();

		foreach ($agents as $agent)
		{
			$company_ids_raw[] = $agent->company;
		}

		$company_ids_raw = array_unique($company_ids_raw);
		$company_ids     = array();

		foreach ($company_ids_raw as $cid)
		{
			$company_ids[] = $db->q($cid);
		}

		$query = $db->getQuery(true)
		            ->update($db->qn('#__iproperty_companies'))
		            ->set($db->qn('state') . ' = ' . $db->q('0'))
		            ->where($db->qn('id') . ' IN (' . implode(',', $company_ids) . ')');
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Mix two parameter sets, keeping the maximum value between the two
	 *
	 * @param   array $old Old parameter set
	 * @param   array $new New parameter set
	 *
	 * @return  array  The mixed parameter set
	 */
	private function mixParams($old, $new)
	{
		$oldKeys = count($old) ? array_keys($old) : array();
		$newKeys = count($new) ? array_keys($new) : array();
		$allKeys = array_merge($oldKeys, $newKeys);

		if (empty($allKeys))
		{
			return array();
		}

		$ret = array();

		foreach ($allKeys as $key)
		{
			if (array_key_exists($key, $old) && !array_key_exists($key, $new))
			{
				$ret[ $key ] = $old[ $key ];
			}
			elseif (!array_key_exists($key, $old) && array_key_exists($key, $new))
			{
				$ret[ $key ] = $new[ $key ];
			}
			else
			{
				$ret[ $key ] = max($old[ $key ], $new[ $key ]);
			}
		}

		return $ret;
	}

	/**
	 * Used by the view template. Returns the value of a named parameter for this plugin and a specific subscription
	 * level.
	 *
	 * @param   int     $level_id  Subscription level ID
	 * @param   string  $paramKey  The parameter key
	 * @param   int     $default   Default value
	 *
	 * @return  int  The parameter setting
	 */
	public function getParamValue($level_id, $paramKey, $default = 0)
	{
		if (array_key_exists($level_id, $this->addGroups))
		{
			if (!is_array($this->addGroups[ $level_id ]))
			{
				return $default;
			}

			if (array_key_exists($paramKey, $this->addGroups[ $level_id ]))
			{
				return (int) $this->addGroups[ $level_id ][ $paramKey ];
			}
			else
			{
				return $default;
			}
		}
		else
		{
			return $default;
		}
	}
}