<?php
/**
 *  @package	akeebasubs
 *  @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
defined('_JEXEC') or die();

// =============================================================================
// Akeeba Component Installation Configuration
// =============================================================================
$installation_queue = array(
	// modules => { (folder) => { (module) => { (position), (published) } }* }*
	'modules' => array(
		'admin' => array(
			'akeebasubs' => array('cpanel', 1)
		),
		'site' => array(
			'aksexpires' => array('left', 0),
			'aksubslist' => array('left', 0),
			'akslevels' => array('left', 0)
		)
	),
	// plugins => { (folder) => { (element) => (published) }* }*
	'plugins' => array(
		'akeebasubs' => array(
			'acymailing'			=> 0,
			'adminemails'			=> 0,
			'affemails'				=> 0,
			'autocity'				=> 0,
			'cb'					=> 0,
			'communityacl'			=> 0,
			'ccinvoices'			=> 0,
			'docman'				=> 0,
			'jce'					=> 0,
			'jomsocial'				=> 0,
			'joomla'				=> 0,
			'juga'					=> 0,
			'jxjomsocial'			=> 0,
			'k2'					=> 0,
			'ninjaboard'			=> 0,
			'redshop'				=> 0,
			'samplefields'			=> 0,
			'sql'					=> 0,
			'subscriptionemails'	=> 1,
			'tienda'				=> 0,
			'userdelete'			=> 0,
			'vm'					=> 0,
			'vm2'					=> 0
		),
		'akpayment' => array(
			'2checkout'				=> 0,
			'ccavenue'				=> 0,
			'eway'					=> 0,
			'moip'					=> 0,
			'none'					=> 0,
			'offline'				=> 0,
			'paypal'				=> 1,
			'skrill'				=> 0,
			'upay'					=> 0,
			'worldpay'				=> 0
		),
		'content' => array(
			'aslink'				=> 1,
			'asrestricted'			=> 1
		),
		'system' => array(
			'asexpirationcontrol'	=> 1,
			'asexpirationnotify'	=> 1
		)
	)
);

// Define files and directories to remove - these are leftovers from Akeeba Subscriptions 1.0.x
$removeFiles = array(
	'administrator/components/com_akeebasubs/akeebasubs.xml',
	'administrator/components/com_akeebasubs/install.akeebasubs.php',
	'administrator/components/com_akeebasubs/uninstall.akeebasubs.php',
	'administrator/components/com_akeebasubs/config.json',
	'administrator/components/com_akeebasubs/controllers/config.php',
	'administrator/components/com_akeebasubs/controllers/dashboard.php',
	'administrator/components/com_akeebasubs/controllers/default.php',
	'administrator/components/com_akeebasubs/controllers/subrefresh.php',
	'administrator/components/com_akeebasubs/controllers/subscription.php',
	'administrator/components/com_akeebasubs/controllers/tool.php',
	'administrator/components/com_akeebasubs/models/configs.php',
	'administrator/components/com_akeebasubs/views/html.php',
	'administrator/components/com_akeebasubs/views/affiliates/tmpl/form.php',
	'administrator/components/com_akeebasubs/views/affpayments/tmpl/form.php',
	'administrator/components/com_akeebasubs/views/coupons/tmpl/form.php',
	'administrator/components/com_akeebasubs/views/levels/tmpl/form.php',
	'administrator/components/com_akeebasubs/views/subscriptions/tmpl/form.php',
	'administrator/components/com_akeebasubs/views/taxrules/tmpl/form.php',
	'administrator/components/com_akeebasubs/views/upgrades/tmpl/form.php',
	'administrator/components/com_akeebasubs/views/users/tmpl/form.php',
	'components/com_akeebasubs/controllers/callback.php',
	'components/com_akeebasubs/controllers/config.php',
	'components/com_akeebasubs/controllers/default.php',
	'components/com_akeebasubs/controllers/juser.php',
	'components/com_akeebasubs/controllers/level.php',
	'components/com_akeebasubs/controllers/message.php',
	'components/com_akeebasubs/controllers/subscribe.php',
	'components/com_akeebasubs/controllers/subscription.php',
	'components/com_akeebasubs/controllers/taxrule.php',
	'components/com_akeebasubs/controllers/user.php',
	'components/com_akeebasubs/controllers/validate.php',
	'components/com_akeebasubs/views/level/html.php',
	'components/com_akeebasubs/views/subscribe/html.php',
);
$removeFolders = array(
	'administrator/components/com_akeebasubs/commands',
	'administrator/components/com_akeebasubs/controllers/behaviours',
	'administrator/components/com_akeebasubs/controllers/toolbars',
	'administrator/components/com_akeebasubs/databases',
	'administrator/components/com_akeebasubs/simpleforms',
	'administrator/components/com_akeebasubs/templates',
	'administrator/components/com_akeebasubs/toolbars',
	'administrator/components/com_akeebasubs/toolbars-xxx',
	'administrator/components/com_akeebasubs/views/config',
	'administrator/components/com_akeebasubs/views/coupon',
	'administrator/components/com_akeebasubs/views/dashboard',
	'administrator/components/com_akeebasubs/views/level',
	'administrator/components/com_akeebasubs/views/subscription',
	'administrator/components/com_akeebasubs/views/taxrule',
	'administrator/components/com_akeebasubs/views/upgrade',
	'administrator/components/com_akeebasubs/views/user',
	'components/com_akeebasubs/templates',
	'components/com_akeebasubs/controllers/behaviors',
);

// Joomla! 1.6 Beta 13+ hack
if( version_compare( JVERSION, '1.6.0', 'ge' ) && !defined('_AKEEBA_HACK') ) {
	return;
} else {
	global $akeeba_installation_has_run;
	if($akeeba_installation_has_run) return;
}

$db = JFactory::getDBO();

// =============================================================================
// Pre-installation checks
// =============================================================================

// Do you have at least Joomla! 1.5.14?
if(!version_compare(JVERSION, '1.5.14', 'ge')) {
	JError::raiseWarning(0, "The Joomla! version you are using is old, buggy, vulnerable and doesn't support Akeeba Subscriptions. Please upgrade your site then retry installing this component.");
	return false;
}

// Does the server has PHP 5.2.7 or later?
if(!version_compare(phpversion(), '5.2.7', 'ge')) {
	JError::raiseWarning(0, "Your PHP version is older than 5.2.7. Akeeba Subscriptions may not work properly!");
} elseif(!version_compare(phpversion(), '5.2.6', 'ge')) {
	JError::raiseWarning(0, "Your PHP version is older than 5.2.6. Akeeba Subscriptions <u>WILL NOT</u> work. The installation is aborted.");
	return false;
}

// Do we have the minimum required version of MySQL?
if(!version_compare($db->getVersion(), '5.0.41', 'ge')) {
	JError::raiseWarning(0, "Your MySQL version is older than 5.0.41. Akeeba Subscriptions can't work on such an old database server.");
	return false;
}

// =============================================================================
// Database update
// =============================================================================
// Upgrade the levels table (1.0.0)
$sql = 'SHOW CREATE TABLE `#__akeebasubs_levels`';
$db->setQuery($sql);
$ctableAssoc = $db->loadResultArray(1);
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`notify1`'))
{
	$sql = <<<ENDSQL
ALTER TABLE `#__akeebasubs_level`
ADD COLUMN `notify1` int(10) unsigned NOT NULL DEFAULT '30',
ADD COLUMN `notify2` int(10) unsigned NOT NULL DEFAULT '15';
ENDSQL;
	$db->setQuery($sql);
	$status = $db->query();
}

// Upgrade the subscriptions table (2.0.a1)
$sql = 'SHOW CREATE TABLE `#__akeebasubs_subscriptions`';
$db->setQuery($sql);
$ctableAssoc = $db->loadResultArray(1);
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`akeebasubs_coupon_id`'))
{
	$sql = <<<ENDSQL
ALTER TABLE `#__akeebasubs_subscriptions`
ADD COLUMN `discount_amount` FLOAT NOT NULL DEFAULT '0' AFTER `params`,
ADD COLUMN `prediscount_amount` FLOAT NULL AFTER `params`,
ADD COLUMN `akeebasubs_invoice_id` BIGINT(20) NULL AFTER `params`,
ADD COLUMN `akeebasubs_affiliate_id` BIGINT(20) NULL AFTER `params`,
ADD COLUMN `akeebasubs_upgrade_id` BIGINT(20) NULL AFTER `params`,
ADD COLUMN `akeebasubs_coupon_id` BIGINT(20) NULL AFTER `params`;
ENDSQL;
	$db->setQuery($sql);
	$status = $db->query();
}

// Upgrade the subscriptions table (2.0.a2)
$sql = 'SHOW CREATE TABLE `#__akeebasubs_subscriptions`';
$db->setQuery($sql);
$ctableAssoc = $db->loadResultArray(1);
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`affiliate_comission`'))
{
	$sql = <<<ENDSQL
ALTER TABLE `#__akeebasubs_subscriptions`
ADD COLUMN `affiliate_comission` FLOAT NOT NULL DEFAULT '0' AFTER `akeebasubs_affiliate_id`;
ENDSQL;
	$db->setQuery($sql);
	$status = $db->query();
}

// Upgrade the subscriptions table (2.0.b3)
$sql = 'SHOW CREATE TABLE `#__akeebasubs_subscriptions`';
$db->setQuery($sql);
$ctableAssoc = $db->loadResultArray(1);
$ctable = empty($ctableAssoc) ? '' : $ctableAssoc[0];
if(!strstr($ctable, '`tax_percent`'))
{
	$sql = <<<ENDSQL
ALTER TABLE `#__akeebasubs_subscriptions`
ADD COLUMN `tax_percent` FLOAT DEFAULT NULL AFTER `gross_amount`;
ENDSQL;
	$db->setQuery($sql);
	$status = $db->query();
}

// =============================================================================
// Sub-extension installation
// =============================================================================

// Setup the sub-extensions installer
jimport('joomla.installer.installer');
$db = & JFactory::getDBO();
$status = new JObject();
$status->modules = array();
$status->plugins = array();
$src = $this->parent->getPath('source');

// Install the Joom!Fish content XML file
if(is_dir($src.'/plugins/joomfish')) {
	if(JFile::exists(JPATH_SITE . '/components/com_joomfish/helpers/defines.php')) {
		$result = JFile::copy($src.'/plugins/joomfish/akeebasubs_levels.xml', JPATH_ADMINISTRATOR.'/components/com_joomfish/contentelements/akeebasubs_levels.xml');
		$status->plugins[] = array('name'=>'akeebasubs_levels.xml','group'=>'joomfish', 'result'=>$result);
	}
}

// Remove unused files and folders (or the component will explode!)
foreach($removeFiles as $removedFile) {
	$removePath = JPATH_SITE.'/'.$removedFile;
	if(JFile::exists($removePath)) JFile::delete($removePath);
}
foreach($removeFolders as $removedFolder) {
	$removePath = JPATH_SITE.'/'.$removedFolder;
	if(JFolder::exists($removePath)) JFolder::delete(JPATH_SITE.'/'.$removedFolder);
}

// Modules installation
if(count($installation_queue['modules'])) {
	foreach($installation_queue['modules'] as $folder => $modules) {
		if(count($modules)) foreach($modules as $module => $modulePreferences) {
			// Install the module
			if(empty($folder)) $folder = 'site';
			$path = "$src/modules/$folder/$module";
			if(!is_dir($path)) continue;
			// Was the module alrady installed?
			$sql = 'SELECT COUNT(*) FROM #__modules WHERE `module`='.$db->Quote('mod_'.$module);
			$db->setQuery($sql);
			$count = $db->loadResult();
			$installer = new JInstaller;
			$result = $installer->install($path);
			$status->modules[] = array('name'=>'mod_'.$module, 'client'=>$folder, 'result'=>$result);
			// Modify where it's published and its published state
			if(!$count) {
				list($modulePosition, $modulePublished) = $modulePreferences;
				$sql = "UPDATE #__modules SET position=".$db->Quote($modulePosition);
				if($modulePublished) $sql .= ', published=1';
				$sql .= ' WHERE `module`='.$db->Quote('mod_'.$module);
				$db->setQuery($sql);
				$db->query();
			}
		}
	}
}

// Plugins installation
if(count($installation_queue['plugins'])) {
	foreach($installation_queue['plugins'] as $folder => $plugins) {
		if(count($plugins)) foreach($plugins as $plugin => $published) {
			$path = "$src/plugins/$folder/$plugin";
			if(!is_dir($path)) continue;
			
			// Was the plugin already installed?
			if( version_compare( JVERSION, '1.6.0', 'ge' ) ) {
				$query = "SELECT COUNT(*) FROM  #__extensions WHERE element=".$db->Quote($plugin)." AND folder=".$db->Quote($folder);
			} else {
				$query = "SELECT COUNT(*) FROM  #__plugins WHERE element=".$db->Quote($plugin)." AND folder=".$db->Quote($folder);
			}
			$db->setQuery($query);
			$count = $db->loadResult();
			
			$installer = new JInstaller;
			$result = $installer->install($path);
			$status->plugins[] = array('name'=>'plg_'.$plugin,'group'=>$folder, 'result'=>$result);
			
			if($published && !$count) {
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
	$j15 = true;
} else {
	$j15 = false;
}

$jlang =& JFactory::getLanguage();
$jlang->load('com_akeebasubs.sys', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_akeebasubs.sys', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_akeebasubs.sys', JPATH_ADMINISTRATOR, null, true);

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
<div style="margin: 1em; font-size: 14pt; background-color: #fffff9; color: black">
	You can download translation files <a href="http://akeeba-cdn.s3-website-eu-west-1.amazonaws.com/language/akeebasubs/">directly from our CDN page</a>.
</div>
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