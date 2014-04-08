<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelCpanels extends F0FModel
{
	/** @var string The root of the database installation files */
	private $dbFilesRoot = '/components/com_akeebasubs/sql/';

	/** @var array If any of these tables is missing we run the install SQL file and ignore the $dbChecks array */
	private $dbBaseCheck = array(
		'tables' => array(
			'akeebasubs_levels', 'akeebasubs_subscriptions',
			'akeebasubs_taxrules', 'akeebasubs_coupons',
			'akeebasubs_upgrades', 'akeebasubs_users',
			'akeebasubs_configurations', 'akeebasubs_invoices',
			'akeebasubs_affiliates', 'akeebasubs_affpayments',
			'akeebasubs_blockrules'
		),
		'file' => 'install/mysql/install.sql'
	);

	/** @var array Database update checks */
	private $dbChecks = array(
		// check for update 2.3.0-2012-06-15
		array(
			'table' => 'akeebasubs_levels',
			'field' => 'akeebasubs_levelgroup_id',
			'files' =>array(
				'updates/mysql/2.3.0-2012-06-15.sql',
			)
		),
		// check for update 2.3.0-2012-06-22
		array(
			'table' => 'akeebasubs_invoicetemplates',
			'field' => null,
			'files' =>array(
				'updates/mysql/2.3.0-2012-06-18.sql',
				'updates/mysql/2.3.0-2012-06-22.sql',
				'updates/mysql/3.0.0-2013-01-29.sql',
			)
		),
		// check for update 2.3.0-2012-07-13
		array(
			'table' => 'akeebasubs_upgrades',
			'field' => 'combine',
			'files' =>array(
				'updates/mysql/2.3.0-2012-07-13.sql',
			)
		),
		// check for update 2.4.0-2012-08-14
		array(
			'table' => 'akeebasubs_customfields',
			'field' => null,
			'files' =>array(
				'updates/mysql/2.4.0-2012-08-14.sql',
			)
		),
		// check for update 2.4.5-2012-11-02
		array(
			'table' => 'akeebasubs_levels',
			'field' => 'params',
			'files' =>array(
				'updates/mysql/2.4.4-2012-10-08.sql',
				'updates/mysql/2.4.5-2012-11-02.sql',
			)
		),
		// check for update 2.5.0-2012-11-07
		array(
			'table' => 'akeebasubs_levels',
			'field' => 'forever',
			'files' =>array(
				'updates/mysql/2.5.0-2012-11-07.sql',
			)
		),
		// check for update 3.0.0-2012-12-29.sql
		array(
			'table' => 'akeebasubs_states',
			'field' => '',
			'files' =>array(
				'updates/mysql/3.0.0-2012-12-29.sql',
			)
		),
		// check for update 3.0.0-2013-01-15.sql
		array(
			'table' => 'akeebasubs_relations',
			'field' => '',
			'files' =>array(
				'updates/mysql/3.0.0-2013-01-15.sql',
			)
		),
		// check for update 3.0.0-2013-01-22
		array(
			'table' => 'akeebasubs_emailtemplates',
			'field' => '',
			'files' =>array(
				'updates/mysql/3.0.0-2013-01-20.sql',
				'updates/mysql/3.0.0-2013-01-22.sql',
			)
		),
		// check for update 3.0.0-2013-01-23
		array(
			'table' => 'akeebasubs_levels',
			'field' => 'notifyafter',
			'files' =>array(
				'updates/mysql/3.0.0-2013-01-23.sql',
			)
		),
		// check for update 3.0.0-2013-01-23 (part 2)
		array(
			'table' => 'akeebasubs_subscriptions',
			'field' => 'after_contact',
			'files' =>array(
				'updates/mysql/3.0.0-2013-01-23.sql',
			)
		),
		// check for update 3.0.0-2013-01-24
		array(
			'table' => 'akeebasubs_levels',
			'field' => 'fixed_date',
			'files' =>array(
				'updates/mysql/3.0.0-2013-01-24.sql',
			)
		),
		// check for update 3.0.0-2013-01-25
		array(
			'table' => 'akeebasubs_invoices',
			'field' => 'extension',
			'files' =>array(
				'updates/mysql/3.0.0-2013-01-25.sql',
			)
		),
		// check for update 3.1.0-2013-03-30
		array(
			'table' => 'akeebasubs_invoicetemplates',
			'field' => 'globalformat',
			'files' =>array(
				'updates/mysql/3.1.0-2013-03-30.sql',
			)
		),
		// check for update 3.1.0-2014-04-01
		array(
			'table' => 'akeebasubs_blockrules',
			'field' => 'username',
			'files' =>array(
				'updates/mysql/3.1.0-2013-04-01.sql',
			)
		),
		// check for update 3.1.0-2014-04-13
		array(
			'table' => 'akeebasubs_levels',
			'field' => 'payment_plugins',
			'files' =>array(
				'updates/mysql/3.1.0-2013-04-13.sql',
			)
		),

		array(
			'table' => 'akeebasubs_levels',
			'field' => 'signupfee',
			'files' =>array(
				'updates/mysql/3.1.2-2013-04-27.sql',
			)
		),

		array(
			'table' => 'akeebasubs_taxrules',
			'field' => 'akeebasubs_level_id',
			'files' =>array(
				'updates/mysql/3.1.2-2013-05-08.sql',
			)
		),

		array(
			'table' => 'akeebasubs_customfields',
			'field' => 'show',
			'files' =>array(
				'updates/mysql/3.2.0-2013-06-08.sql',
			)
		),

		array(
			'table' => 'akeebasubs_invoicetemplates',
			'field' => 'noinvoice',
			'files' =>array(
				'updates/mysql/3.2.1-2013-07-15.sql',
			)
		),

		array(
			'table' => 'akeebasubs_coupons',
			'field' => 'akeebasubs_apicoupon_id',
			'files' =>array(
				'updates/mysql/3.2.1-2013-08-06.sql',
			)
		),

		array(
			'table' => 'akeebasubs_levels',
			'field' => 'orderurl',
			'files' =>array(
				'updates/mysql/3.2.1-2013-08-20.sql',
			)
		),

		array(
			'table' => 'akeebasubs_coupons',
			'field' => 'email',
			'files' =>array(
				'updates/mysql/3.2.1-2013-08-22.sql',
			)
		),
	);

	/**
	 * Checks the database for missing / outdated tables using the $dbChecks
	 * data and runs the appropriate SQL scripts if necessary.
	 *
	 * @return AkeebasubsModelCpanels
	 */
	public function checkAndFixDatabase()
	{
		// Install or update database
		$dbFilePath = JPATH_ADMINISTRATOR . '/components/com_akeebasubs/sql';
		if (!class_exists('AkeebaDatabaseInstaller'))
		{
			require_once $dbFilePath . '/dbinstaller.php';
		}
		$dbInstaller = new AkeebaDatabaseInstaller(JFactory::getDbo());
		$dbInstaller->setXmlDirectory($dbFilePath . '/xml');
		$dbInstaller->updateSchema();

		return $this;
	}

	/**
	 * Save some magic variables we need
	 *
	 * @return AkeebasubsModelCpanels
	 */
	public function saveMagicVariables()
	{
		// Store the URL to this site
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select('params')
			->from($db->qn('#__extensions'))
			->where($db->qn('element').'='.$db->q('com_akeebasubs'))
			->where($db->qn('type').'='.$db->q('component'));
		$db->setQuery($query);
		$rawparams = $db->loadResult();
		$params = new JRegistry();
		$params->loadString($rawparams, 'JSON');

		$siteURL_stored = $params->get('siteurl', '');
		$siteURL_target = str_replace('/administrator','',JURI::base());

		if($siteURL_target != $siteURL_stored) {
			$params->set('siteurl', $siteURL_target);
			$query = $db->getQuery(true)
				->update($db->qn('#__extensions'))
				->set($db->qn('params') .'='. $db->q($params->toString()))
				->where($db->qn('element').'='.$db->q('com_akeebasubs'))
				->where($db->qn('type').'='.$db->q('component'));
			$db->setQuery($query);
			$db->execute();
		}

		return $this;
	}

	/**
	 * Refreshes the Joomla! update sites for this extension as needed
	 *
	 * @return  AkeebasubsModelCpanels
	 */
	public function refreshUpdateSite()
	{
		// Create the update site definition we want to store to the database
		$update_site = array(
			'name'		=> 'Akeeba Subscriptions',
			'type'		=> 'extension',
			'location'	=> 'http://cdn.akeebabackup.com/updates/akeebasubs.xml',
			'enabled'	=> 1,
			'last_check_timestamp'	=> 0,
			'extra_query'	=> null
		);

		$db = $this->getDbo();

		// Get the extension ID to ourselves
		$query = $db->getQuery(true)
			->select($db->qn('extension_id'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('component'))
			->where($db->qn('element') . ' = ' . $db->q('com_akeebasubs'));
		$db->setQuery($query);

		$extension_id = $db->loadResult();

		if (empty($extension_id))
		{
			return $this;
		}

		// Get the update sites for our extension
		$query = $db->getQuery(true)
			->select($db->qn('update_site_id'))
			->from($db->qn('#__update_sites_extensions'))
			->where($db->qn('extension_id') . ' = ' . $db->q($extension_id));
		$db->setQuery($query);

		$updateSiteIDs = $db->loadColumn(0);

		if (!count($updateSiteIDs))
		{
			// No update sites defined. Create a new one.
			$newSite = (object)$update_site;
			$db->insertObject('#__update_sites', $newSite);

			$id = $db->insertid();

			$updateSiteExtension = (object)array(
				'update_site_id'	=> $id,
				'extension_id'		=> $extension_id,
			);
			$db->insertObject('#__update_sites_extensions', $updateSiteExtension);
		}
		else
		{
			// Loop through all update sites
			foreach ($updateSiteIDs as $id)
			{
				$query = $db->getQuery(true)
					->select('*')
					->from($db->qn('#__update_sites'))
					->where($db->qn('update_site_id') . ' = ' . $db->q($id));
				$db->setQuery($query);
				$aSite = $db->loadObject();

				// Does the name and location match?
				if (($aSite->name == $update_site['name']) && ($aSite->location == $update_site['location']))
				{
					continue;
				}

				$update_site['update_site_id'] = $id;
				$newSite = (object)$update_site;
				$db->updateObject('#__update_sites', $newSite, 'update_site_id', true);
			}
		}

		return $this;
	}
}