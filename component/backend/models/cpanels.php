<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelCpanels extends FOFModel
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
	);

	/**
	 * Checks the database for missing / outdated tables using the $dbChecks
	 * data and runs the appropriate SQL scripts if necessary.
	 *
	 * @return AkeebasubsModelCpanels
	 */
	public function checkAndFixDatabase()
	{
		$db = $this->getDbo();

		// Initialise
		$tableFields = array();
		$sqlFiles = array();

		// Get a listing of database tables known to Joomla!
		$allTables = $db->getTableList();
		$dbprefix = JFactory::getConfig()->get('dbprefix', '');

		// Perform the base check. If any of these tables is missing we have to run the installation SQL file
		if(!empty($this->dbBaseCheck)) {
			foreach($this->dbBaseCheck['tables'] as $table)
			{
				$tableName = $dbprefix . $table;
				$check = in_array($tableName, $allTables);
				if (!$check) break;
			}

			if (!$check)
			{
				$sqlFiles[] = JPATH_ADMINISTRATOR . $this->dbFilesRoot . $this->dbBaseCheck['file'];
			}
		}

		// If the base check was successful and we have further database checks run them
		if (empty($sqlFiles) && !empty($this->dbChecks)) foreach($this->dbChecks as $dbCheck)
		{
			// Always check that the table exists
			$tableName = $dbprefix . $dbCheck['table'];
			$check = in_array($tableName, $allTables);

			// If the table exists and we have a field, check that the field exists too
			if (!empty($dbCheck['field']) && $check)
			{
				if (!array_key_exists($tableName, $tableFields))
				{
					$tableFields[$tableName] = $db->getTableColumns('#__' . $dbCheck['table'], true);
				}

				if (is_array($tableFields[$tableName]))
				{
					$check = array_key_exists($dbCheck['field'], $tableFields[$tableName]);
				}
				else
				{
					$check = false;
				}
			}

			// Something's missing. Add the file to the list of SQL files to run
			if (!$check)
			{
				foreach ($dbCheck['files'] as $file)
				{
					$sqlFiles[] = JPATH_ADMINISTRATOR . $this->dbFilesRoot . $file;
				}
			}
		}

		// If we have SQL files to run, well, RUN THEM!
		if (!empty($sqlFiles))
		{
			JLoader::import('joomla.filesystem.file');
			foreach($sqlFiles as $file)
			{
				$sql = JFile::read($file);
				if($sql) {
					$commands = explode(';', $sql);
					foreach($commands as $query) {
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

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
	 * Does the user needs to enter a Download ID?
	 *
	 * @return boolean
	 */
	public function needsDownloadID()
	{
		JLoader::import('joomla.application.component.helper');

		// Do I need a Download ID?
		$ret = false;
		$isPro = AKEEBASUBS_PRO;
		if(!$isPro) {
			$ret = false;
		} else {
			$ret = false;
			$params = JComponentHelper::getParams('com_akeebasubs');
			if(version_compare(JVERSION, '3.0', 'ge')) {
				$dlid = $params->get('downloadid', '');
			} else {
				$dlid = $params->getValue('downloadid', '');
			}
			if(!preg_match('/^([0-9]{1,}:)?[0-9a-f]{32}$/i', $dlid)) {
				$ret = true;
			}
		}

		return $ret;
	}
}