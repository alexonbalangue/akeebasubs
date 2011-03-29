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
	// modules => { (folder) => { (module) => { (position), (published) } }* }*
	),
	// plugins => { (folder) => { (element) => (published) }* }*
	'plugins' => array(
		'akeebasubs' => array(
			'juga'					=> 0,
			'subscriptionemails'	=> 1
		),
		'akpayment' => array(
			'none'					=> 0,
			'paypal'				=> 1
		),
		'content' => array(
			'asrestricted'			=> 1
		),
		'system' => array(
			'koowa'					=> 1,
			'asexpirationcontrol'	=> 1,
			'asexpirationnotify'	=> 1
		)
	)
);

// Joomla! 1.6 Beta 13+ hack
if( version_compare( JVERSION, '1.6.0', 'ge' ) && !defined('_AKEEBA_HACK') ) {
	return;
} else {
	global $akeeba_installation_has_run;
	if($akeeba_installation_has_run) return;
}

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

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

// Modules installation
if(count($installation_queue['modules'])) {
	foreach($installation_queue['modules'] as $folder => $modules) {
		if(count($modules)) foreach($modules as $module => $modulePreferences) {
			// Install the module
			$path = "$src/modules/$folder/$module";
			if(!is_dir($path)) continue;
			$installer = new JInstaller;
			$result = $installer->install($path);
			$status->modules[] = array('name'=>'mod_'.$module,'client'=>$folder, 'result'=>$result);
			// Modify where it's published and its published state
			list($modulePosition, $modulePublished) = $modulePreferences;
			$sql = "UPDATE #__modules SET position=".$db->Quote($modulePosition);
			if($modulePublished) $sql .= ', published=1';
			$sql .= ' WHERE `module`='.$db->Quote('mod_'.$module);
			$db->setQuery($query);
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

// Install the Koowa library and associated system files (let's hope it works!)
$koowaInstalled = JFolder::copy("$src/koowa", JPATH_ROOT, null, true);

// Change MySQL extension to mysqli if required
$config =& JFactory::getConfig();
$driver = $config->getValue('config.dbtype', 'mysql');
if($driver != 'mysqli') {
	$config->setValue('config.dbtype', 'mysqli');
	$buffer = $config->toString('PHP', 'config', array('class' => 'JConfig'));
	// On some occasions, Joomla! 1.6 ignores the configuration and produces "class c". Let's fix this!
	$buffer = str_replace('class c {','class JConfig {',$buffer);
	// Try to write out the configuration.php
	$file = JPATH_ROOT.DS.'configuration.php';
	// Hack me: Try changing the permissions, working around an age-old Joomla! bug
	@chmod($file, 0755);
	
	// Try using the FTP layer
	jimport('joomla.client.helper');
	$FTPOptions = JClientHelper::getCredentials('ftp');
	if ($FTPOptions['enabled'] == 1) {
		// Connect the FTP client
		jimport('joomla.client.ftp');
		$ftp = & JFTP::getInstance($FTPOptions['host'], $FTPOptions['port'], null, $FTPOptions['user'], $FTPOptions['pass']);

		// Translate path for the FTP account and use FTP write buffer to file
		$file = JPath::clean(str_replace(JPATH_ROOT, $FTPOptions['root'], $file), '/');
		$ret = $ftp->write($file, $buffer);
	} else {
		$ret = false;
	}
	
	// Try using direct file writes
	if(!$ret) {
		$ret = @file_put_contents($file, $buffer);
	}
	
	// Try using JFile - All hell might break loose...
	if(!$ret) {
		jimport('joomla.filesystem.file');
		$ret = JFile::write($file, $buffer);
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
		<tr class="row1">
			<td class="key" colspan="2">
				<strong><?php pitext('COM_AKEEBASUBS_PIKOOWA'); ?></strong>
			</td>
			<td><strong style="color: <?php echo ($koowaInstalled) ? 'green' : 'red' ?>"><?php pitext($koowaInstalled ? 'COM_AKEEBASUBS_PIINSTALLED' : 'COM_AKEEBASUBS_PINOTINSTALLED');?></strong></td>
		</tr>
		<?php if (count($status->modules)) : ?>
		<tr>
			<th><?php pitext('COM_AKEEBASUBS_PIMODULE'); ?></th>
			<th><?php pitext('COM_AKEEBASUBS_PICLIENT'); ?></th>
			<th></th>
		</tr>
		<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php pitext('COM_AKEEBASUBS_PICLIENT_').strtoupper($module['client']); ?></td>
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