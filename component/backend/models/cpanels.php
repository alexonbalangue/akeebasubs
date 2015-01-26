<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelCpanels extends F0FModel
{
	/**
	 * Checks the database for missing / outdated tables using the $dbChecks
	 * data and runs the appropriate SQL scripts if necessary.
	 *
	 * @return AkeebasubsModelCpanels
	 */
	public function checkAndFixDatabase()
	{
		// Install or update database
		$dbInstaller = new F0FDatabaseInstaller(array(
			'dbinstaller_directory'	=> JPATH_ADMINISTRATOR . '/components/com_akeebasubs/sql/xml'
		));
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
	 * Do we have the Akeeba GeoIP provider plugin installed?
	 *
	 * @return  boolean  False = not installed, True = installed
	 */
	public function hasGeoIPPlugin()
	{
		static $result = null;

		if (is_null($result))
		{
			$db = JFactory::getDbo();

			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->qn('#__extensions'))
				->where($db->qn('type') . ' = ' . $db->q('plugin'))
				->where($db->qn('folder') . ' = ' . $db->q('system'))
				->where($db->qn('element') . ' = ' . $db->q('akgeoip'));
			$db->setQuery($query);
			$result = $db->loadResult();
		}

		return ($result != 0);
	}

	/**
	 * Does the GeoIP database need update?
	 *
	 * @param   integer $maxAge The maximum age of the db in days (default: 15)
	 *
	 * @return  boolean
	 */
	public function GeoIPDBNeedsUpdate($maxAge = 15)
	{
		$needsUpdate = false;

		if (!$this->hasGeoIPPlugin())
		{
			return $needsUpdate;
		}

		// Get the modification time of the database file
		$filePath = JPATH_ROOT . '/plugins/system/akgeoip/db/GeoLite2-Country.mmdb';
		$modTime = @filemtime($filePath);

		// This is now
		$now = time();

		// Minimum time difference we want (15 days) in seconds
		if ($maxAge <= 0)
		{
			$maxAge = 15;
		}

		$threshold = $maxAge * 24 * 3600;

		// Do we need an update?
		$needsUpdate = ($now - $modTime) > $threshold;

		return $needsUpdate;
	}
}