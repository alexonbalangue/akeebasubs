<?php
/**
 * @package LiveUpdate
 * @copyright Copyright ©2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license GNU LGPLv3 or later <http://www.gnu.org/copyleft/lesser.html>
 */

defined('_JEXEC') or die();

/**
 * Configuration class for your extension's updates. Override to your liking.
 */
class LiveUpdateConfig extends LiveUpdateAbstractConfig
{
	var $_extensionName			= 'com_akeebasubs';
	var $_extensionTitle		= 'Akeeba Subscriptions';
	var $_versionStrategy		= 'vcompare';
	var $_updateURL				= 'http://cdn.akeebabackup.com/updates/akeebasubs.ini';
	var $_requiresAuthorization = false;
	var $_storageAdapter		= 'component';
	var $_storageConfig			= array(
		'extensionName'	=> 'com_akeebasubs',
		'key'			=> 'liveupdate'
	);

	public function __construct() {
		JLoader::import('joomla.filesystem.file');
		$isPro = defined('AKEEBASUBS_PRO') ? (AKEEBASUBS_PRO == 1) : false;

		// Load the component parameters, not using JComponentHelper to avoid conflicts ;)
		JLoader::import('joomla.html.parameter');
		JLoader::import('joomla.application.component.helper');
		$db = JFactory::getDbo();
		$sql = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type').' = '.$db->quote('component'))
			->where($db->quoteName('element').' = '.$db->quote('com_akeebasubs'));
		$db->setQuery($sql);
		$rawparams = $db->loadResult();
		$params = new JRegistry();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$params->loadString($rawparams, 'JSON');
		} else {
			$params->loadJSON($rawparams);
		}

		// Determine the appropriate update URL based on whether we're on Core or Professional edition
		if($isPro)
		{
			$this->_updateURL = 'http://cdn.akeebabackup.com/updates/akeebasubspro.ini';
			$this->_extensionTitle = 'Akeeba Subscriptions Professional';
		}
		else
		{
			$this->_updateURL = 'http://cdn.akeebabackup.com/updates/akeebasubs.ini';
			$this->_extensionTitle = defined('AKEEBASUBS_PRO') ? 'Akeeba Subscriptions Core' : 'Akeeba Subscriptions';
		}

		// Dev releases use the "newest" strategy
		if(substr($this->_currentVersion,1,2) == 'ev') {
			$this->_versionStrategy = 'newest';
		} else {
			$this->_versionStrategy = 'vcompare';
		}

		// Get the minimum stability level for updates
		$this->_minStability = $params->get('minstability', 'stable');

		// Do we need authorized URLs?
		$this->_requiresAuthorization = $isPro;

		// Should I use our private CA store?
		if(@file_exists(dirname(__FILE__).'/../assets/cacert.pem')) {
			$this->_cacerts = dirname(__FILE__).'/../assets/cacert.pem';
		}

		parent::__construct();
	}
}