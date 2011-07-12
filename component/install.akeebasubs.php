<?php
/**
 *  @package	akeebasubs
 *  @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 *  @license	GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 *  @version 	$Id$
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// no direct access
defined('_JEXEC') or die('');

// Akeeba Component Installation Configuration
$installation_queue = array(
	'modules' => array(
		'site' => array(
			'aksubslist' => array('left', 0),
			'akslevels' => array('left', 0)
		)
	// modules => { (folder) => { (module) => { (position), (published) } }* }*
	),
	// plugins => { (folder) => { (element) => (published) }* }*
	'plugins' => array(
		'akeebasubs' => array(
			'docman'				=> 0,
			'jce'					=> 0,
			'joomla'				=> 0,
			'jomsocial'				=> 0,
			'juga'					=> 0,
			'jxjomsocial'			=> 0,
			'k2'					=> 0,
			'ninjaboard'			=> 0,
			'subscriptionemails'	=> 1,
			'tienda'				=> 0,
			'vm'					=> 0
		),
		'akpayment' => array(
			'none'					=> 0,
			'paypal'				=> 1,
			'offline'				=> 0,
			'worldpay'				=> 0,
			'ccavenue'				=> 0,
			'2checkout'				=> 0
		),
		'content' => array(
			'aslink'				=> 1,
			'asrestricted'			=> 1
		),
		'system' => array(
			'koowa'					=> 1,
			'asexpirationcontrol'	=> 1,
			'asexpirationnotify'	=> 1
		)
	)
);

// Define files and directories to remove (they will screw up the component
// due to Nooku Framework changes)
$removeFiles = array(
	'administrator/components/com_akeebasubs/akeebasubs.xml',
	'administrator/components/com_akeebasubs/commands/authorize.php',
	'administrator/components/com_akeebasubs/commands/hotfix.php',
	'administrator/components/com_akeebasubs/install.akeebasubs.php',
	'administrator/components/com_akeebasubs/uninstall.akeebasubs.php',
	'administrator/components/com_akeebasubs/views/config/html.php',
	'administrator/components/com_akeebasubs/views/coupon/html.php',
	'administrator/components/com_akeebasubs/views/coupons/html.php',
	'administrator/components/com_akeebasubs/views/level/html.php',
	'administrator/components/com_akeebasubs/views/levels/html.php',
	'administrator/components/com_akeebasubs/views/subscription/html.php',
	'administrator/components/com_akeebasubs/views/subscriptions/html.php',
	'administrator/components/com_akeebasubs/views/taxrule/html.php',
	'administrator/components/com_akeebasubs/views/taxrules/html.php',
	'administrator/components/com_akeebasubs/views/tools/html.php',
	'administrator/components/com_akeebasubs/views/upgrade/html.php',
	'administrator/components/com_akeebasubs/views/upgrades/html.php',
	'administrator/components/com_akeebasubs/views/user/html.php',
	'administrator/components/com_akeebasubs/views/users/html.php'
);
$removeFolders = array(
	'administrator/components/com_akeebasubs/controllers/commands',
	'administrator/components/com_akeebasubs/toolbars',
	'components/com_akeebasubs/commands'
);

// Joomla! 1.6 Beta 13+ hack
if( version_compare( JVERSION, '1.6.0', 'ge' ) && !defined('_AKEEBA_HACK') ) {
	return;
} else {
	global $akeeba_installation_has_run;
	if($akeeba_installation_has_run) return;
}

$db = JFactory::getDBO();

// ========== Pre-installation checks (because people DO NOT read the fine manual) ==========

// Do we have a Nooku Framework conflict?
if(class_exists('Koowa')) {
	if(Koowa::getVersion() != '0.7.0-alpha-3') {
		JError::raiseWarning(0, "You have some software installed based on a different version of Nooku Framework than Akeeba Subscriptions. We are not proceeding with the installation, as it would break your site.");
		return false;
	}
}

// Do you have at least Joomla! 1.5.14?
if(!version_compare(JVERSION, '1.5.14', 'ge')) {
	JError::raiseWarning(0, "The Joomla! version you are using is old, buggy, vulnerable and doesn't support Akeeba Subscriptions. Please upgrade your site then retry installing this component.");
	return false;
}

// Does the server support MySQLi?
if(!class_exists('mysqli')) {
	JError::raiseWarning(0, "Your server doesn't support MySQLi. Akeeba Subsciptions requires it to work at all. Please ask your host to enable the MySQLi extension on their server.");
	return false;
}

// Is MySQLi enabled?
$conf =& JFactory::getConfig();
if(!stristr(get_class($db),'MySQLi')) {
	JError::raiseWarning(0, "Your site is not usingn the MySQLi driver. Please go to Global Configuration, Server tab and set the Database Type to mysqli before installing this extension.");
}

// Does the server has PHP 5.2.7 or later?
if(!version_compare(phpversion(), '5.2.7', 'ge')) {
	JError::raiseWarning(0, "Your PHP version is older than 5.2.7");
	return false;
}

// Do we have the minimum required version of MySQL?
if(!version_compare($db->getVersion(), '5.0.41', 'ge')) {
	JError::raiseWarning(0, "Your MySQL version is older than 5.0.41");
	return false;
}

// Check if Suhosin can be configured
if (extension_loaded('suhosin'))
{
	//Attempt setting the whitelist value
	@ini_set('suhosin.executor.include.whitelist', 'tmpl://, file://');

	//Checking if the whitelist is ok
	if(!@ini_get('suhosin.executor.include.whitelist') || strpos(@ini_get('suhosin.executor.include.whitelist'), 'tmpl://') === false)
	{
		JError::raiseWarning(0, 'The install failed because your server has Suhosin loaded, but it\'s not configured correctly. Please follow <a href="https://nooku.assembla.com/wiki/show/nooku-framework/Known_Issues" target="_blank">this tutorial</a> before you reinstall.');
		return false;
	}
}

// Check for a Kunena installation
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
if(JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_kunena')) {
	JError::raiseWarning(0, "Your site has Kunena installed. Kunena is not compatible with Nooku Framework, the PHP framework used by Akeeba Subscriptions. The installation was cancelled, as it would result in your site being broken");
	return false;
}

// Check for broken IonCube loaders
if(function_exists('ioncube_loader_version')) {
	if(!function_exists('ioncube_loader_iversion')) {
		JError::raiseWarning(0, "You have a VERY old version of IonCube Loaders which is known to cause problems with Nooku Framework, the PHP framework used by Akeeba Subscriptions. Note: Neither Nooku Framework, not Akeeba Subscriptions, contains encrypted code. However, IonCube Loaders do prevent our unencrypted code from loading. Please go to <a href=\"http://www.ioncube.com/loaders.php\">the IonCube Loaders download page</a> to download and install the latest version of IonCube Loaders on your site before retrying to install this extension.");
		return false;
	}
	
	// Require at least version 4.0.7
	$iclVersion = ioncube_loader_iversion();
	if($iclVersion < 40070) {
		JError::raiseWarning(0, "You have an old version of IonCube Loaders (4.0.6 or earlier) which is known to cause problems with Nooku Framework, the PHP framework used by Akeeba Subscriptions. Note: Neither Nooku Framework, not Akeeba Subscriptions, contains encrypted code. However, IonCube Loaders do prevent our unencrypted code from loading. Please go to <a href=\"http://www.ioncube.com/loaders.php\">the IonCube Loaders download page</a> to download and install the latest version of IonCube Loaders on your site before retrying to install this extension.");
		return false;
	}
}

// Do we have the mooTools upgrade plugin?
if(!version_compare(JVERSION,'1.6.0','ge')) {
	$db->setQuery('SELECT `published` FROM #__plugins WHERE `element` = '.$db->Quote('mtupgrade').' AND `folder` = '.$db->Quote('system'));
	$mtuEnabled = $db->loadResult();
	if(!$mtuEnabled) {
		JError::raiseWarning(0, "Please enable the mooTools Upgrade plugin before installing the component. Go to Extensions, Plugin Manager, find the &quot;System - Mootools Upgrade&quot; plugin and publish it. Then, retry installing the component.");
		return false;
	}
}

// ========== Proceed with installation ==========

// Setup the sub-extensions installer
jimport('joomla.installer.installer');
$db = & JFactory::getDBO();
$status = new JObject();
$status->modules = array();
$status->plugins = array();
if( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
	if(!isset($parent))
	{
		$parent = $this->parent;
	}
	$src = $parent->getParent()->getPath('source');
} else {
	$src = $this->parent->getPath('source');
}

// Install the Koowa library and associated system files first
if(is_dir($src.'/koowa')) {
	$koowaInstalled = JFolder::copy("$src/koowa", JPATH_ROOT, null, true);
	if(!$koowaInstalled) {
		JError::raiseWarning(0,'Could not install the Nooku Framework. Please consult our documentation in order to manually install it before attempting to install Akeeba Subscriptions again.');
		return;
	}
	// Remove the index.html files from the site root and the administrator directory
	JFile::delete(JPATH_ROOT.DS.'index.html');
	JFile::delete(JPATH_ADMINISTRATOR.DS.'index.html');
} else {
	$koowaInstalled = null;
	if(!class_exists('Koowa')) {
		JError::raiseWarning(0, "Your site does nor have the Nooku Framework installed. Please download and install the full package, not the -noframework package, of Akeeba Susbcriptions. Thank you!");
		return false;
	}
}

// Remove unused files and folders (or the component will explode!)
foreach($removeFiles as $removedFile) {
	$removePath = JPATH_SITE.DS.$removedFile;
	if(JFile::exists($removePath)) JFile::delete($removePath);
}
foreach($removeFolders as $removedFolder) {
	$removePath = JPATH_SITE.DS.$removedFolder;
	if(JFolder::exists($removePath)) JFolder::delete(JPATH_SITE.DS.$removedFolder);
}

// Modules installation
if(count($installation_queue['modules'])) {
	foreach($installation_queue['modules'] as $folder => $modules) {
		if(count($modules)) foreach($modules as $module => $modulePreferences) {
			// Install the module
			if(empty($folder)) $folder = 'site';
			$path = "$src/modules/$folder/$module";
			if(!is_dir($path)) continue;
			$installer = new JInstaller;
			$result = $installer->install($path);
			$status->modules[] = array('name'=>'mod_'.$module, 'client'=>$folder, 'result'=>$result);
			// Modify where it's published and its published state
			list($modulePosition, $modulePublished) = $modulePreferences;
			$sql = "UPDATE #__modules SET position=".$db->Quote($modulePosition);
			if($modulePublished) $sql .= ', published=1';
			$sql .= ' WHERE `module`='.$db->Quote('mod_'.$module);
			$db->setQuery($sql);
			$db->query();
		}
	}
}

// Plugins installation
if(count($installation_queue['plugins'])) {
	foreach($installation_queue['plugins'] as $folder => $plugins) {
		if(count($plugins)) foreach($plugins as $plugin => $published) {
			$path = "$src/plugins/$folder/$plugin";
			if(!is_dir($path)) continue;
			$installer = new JInstaller;
			$result = $installer->install($path);
			$status->plugins[] = array('name'=>'plg_'.$plugin,'group'=>$folder, 'result'=>$result);
			
			if($published) {
				if( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
					$query = "UPDATE #__extensions SET enabled=1 WHERE element=".$db->Quote($plugin)." AND folder=".$db->Quote($folder);
				} else {
					$query = "UPDATE #__plugins SET published=1 WHERE element=".$db->Quote($plugin)." AND folder=".$db->Quote($folder);
				}
				$db->setQuery($query);
				$db->query();
			}
		}
	}
}

// Load the translation strings (Joomla! 1.5 and 1.6 compatible)
if( version_compare( JVERSION, '1.6.0', 'lt' ) ) {
	global $j15;
	// Joomla! 1.5 will have to load the translation strings
	$j15 = true;
	$jlang =& JFactory::getLanguage();
	$path = JPATH_ADMINISTRATOR.DS.'components'.DS.'com_akeebasubs';
	$jlang->load('com_akeebasubs.sys', $path, 'en-GB', true);
	$jlang->load('com_akeebasubs.sys', $path, $jlang->getDefault(), true);
	$jlang->load('com_akeebasubs.sys', $path, null, true);
} else {
	$j15 = false;
}

// Define the Akeeba installation translation functions, compatible with both Joomla! 1.5 and 1.6
if(!function_exists('pitext'))
{
	function pitext($key)
	{
		global $j15;
		$string = JText::_($key);
		if($j15)
		{
			$string = str_replace('"_QQ_"', '"', $string);
		}
		echo $string;
	}
}

if(!function_exists('pisprint'))
{
	function pisprint($key, $param)
	{
		global $j15;
		$string = JText::sprintf($key, $param);
		if($j15)
		{
			$string = str_replace('"_QQ_"', '"', $string);
		}
		echo $string;
	}
}
?>

<h1><?php pitext('COM_AKEEBASUBS_PIHEADER'); ?></h1>
<?php $rows = 0;?>
<img src="../media/com_akeebasubs/images/akeebasubs-48.png" width="48" height="48" alt="Akeeba Subscriptions" align="left" />
<h2 style="font-size: 14pt; font-weight: black; padding: 0; margin: 0 0 0.5em;">&nbsp;<?php pitext('COM_AKEEBASUBS_WELCOME'); ?></h2>
<span><?php pitext('COM_AKEEBASUBS_PISUBHEADER'); ?></span>
<table class="adminlist">
	<thead>
		<tr>
			<th class="title" colspan="2"><?php pitext('COM_AKEEBASUBS_PIEXTENSION'); ?></th>
			<th width="30%"><?php pitext('COM_AKEEBASUBS_PISTATUS'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr class="row0">
			<td class="key" colspan="2">
				<img src="../media/com_akeebasubs/images/akeebasubs-16.png" width="16" height="16" alt="Akeeba Subscriptions" align="left" />
				&nbsp;
				<strong><?php pitext('COM_AKEEBASUBS_PICOMPONENT'); ?></strong>
			</td>
			<td><strong style="color: green"><?php pitext('COM_AKEEBASUBS_PIINSTALLED');?></strong></td>
		</tr>
		<?php if(!is_null($koowaInstalled)): ?>
		<tr class="row1">
			<td class="key" colspan="2">
				<strong><?php pitext('COM_AKEEBASUBS_PIKOOWA'); ?></strong>
			</td>
			<td><strong style="color: <?php echo ($koowaInstalled) ? 'green' : 'red' ?>"><?php pitext($koowaInstalled ? 'COM_AKEEBASUBS_PIINSTALLED' : 'COM_AKEEBASUBS_PINOTINSTALLED');?></strong></td>
		</tr>
		<?php endif; ?>
		<?php if (count($status->modules)) : ?>
		<tr>
			<th><?php pitext('COM_AKEEBASUBS_PIMODULE'); ?></th>
			<th><?php pitext('COM_AKEEBASUBS_PICLIENT'); ?></th>
			<th></th>
		</tr>
		<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php pitext('COM_AKEEBASUBS_PICLIENT_').strtoupper( empty($module['client']) ? 'site' : $module['client'] ); ?></td>
			<td>
				<span style="color: <?php echo ($module['result'])?'green':'red'?>; font-weight: bold;">
					<?php ($module['result'])?pitext('COM_AKEEBASUBS_PIINSTALLED'):pitext('COM_AKEEBASUBS_PINOTINSTALLED'); ?>
				</span>
			</td>
		</tr>
		<?php endforeach;?>
		<?php endif;?>
		<?php if (count($status->plugins)) : ?>
		<tr>
			<th><?php pitext('COM_AKEEBASUBS_PIPLUGIN'); ?></th>
			<th><?php pitext('COM_AKEEBASUBS_PIGROUP'); ?></th>
			<th></th>
		</tr>
		<?php foreach ($status->plugins as $plugin) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $plugin['name']; ?></td>
			<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
			<td>
				<span style="color: <?php echo ($plugin['result'])?'green':'red'?>; font-weight: bold;">
					<?php ($plugin['result'])?pitext('COM_AKEEBASUBS_PIINSTALLED'):pitext('COM_AKEEBASUBS_PINOTINSTALLED'); ?>
				</span>
			</td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>