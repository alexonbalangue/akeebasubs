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
}