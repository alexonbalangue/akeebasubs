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
	var $_versionStrategy		= 'different';
	var $_updateURL				= 'http://cdn.akeebabackup.com/updates/akeebasubs.ini';
	var $_requiresAuthorization = false;
	/**
	var $_storageAdapter		= 'component';
	var $_storageConfig			= array(
			'extensionName'	=> 'com_akeebasubs',
			'key'			=> 'liveupdate'
		);
	*/
}