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

// Akeeba Component Uninstallation Configuration
$installation_queue = array(
	'modules' => array(
		'admin' => array(
			'akeebasubs' => array('cpanel', 1)
		),
		'site' => array(
			'aksexpires' => array('left', 0),
			'aksubslist' => array('left', 0),
			'akslevels' => array('left', 0)
		)
	// modules => { (folder) => { (module) => { (position), (published) } }* }*
	),
	// plugins => { (folder) => { (element) => (published) }* }*
	'plugins' => array(
		'akeebasubs' => array(
			'acymailing'			=> 0,
			'adminemails'			=> 0,
			'affemails'				=> 0,
			'ageverification'		=> 0,
			'agora'					=> 0,
			'agreetotos'			=> 0,
			'autocity'				=> 0,
			'cb'					=> 0,
			'ccinvoices'			=> 0,
			'communityacl'			=> 0,
			'docman'				=> 0,
			'iplogger'				=> 0,
			'iproperty'				=> 0,
			'jce'					=> 0,
			'jomsocial'				=> 0,
			'joomla'				=> 0,
			'juga'					=> 0,
			'jxjomsocial'			=> 0,
			'k2'					=> 0,
			'kunena'				=> 0,
			'ninjaboard'			=> 0,
			'phocadownload'			=> 0,
			'recaptcha'				=> 0,
			'redshop'				=> 0,
			'redshopusersync'		=> 0,
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
			'deltapay'				=> 0,
			'eway'					=> 0,
			'googlecheckout'		=> 0,
			'moip'					=> 0,
			'moneris'				=> 0,
			'none'					=> 0,
			'offline'				=> 0,
			'pagseguro'				=> 0,
			'paypal'				=> 1,
			'postfinancech'			=> 0,
			'skrill'				=> 0,
			'upay'					=> 0,
			'worldpay'				=> 0
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

if( version_compare( JVERSION, '1.6.0', 'ge' ) && !defined('_AKEEBA_HACK') ) {
	return;
} else {
	global $akeeba_installation_has_run;
	if($akeeba_installation_has_run) return;
}

// Setup the sub-extensions installer
jimport('joomla.installer.installer');
$db = & JFactory::getDBO();
$status = new JObject();
$status->modules = array();
$status->plugins = array();
$src = $this->parent->getPath('source');

// Modules uninstallation
if(count($installation_queue['modules'])) {
	foreach($installation_queue['modules'] as $folder => $modules) {
		if(count($modules)) foreach($modules as $module => $modulePreferences) {
			// Find the module ID
			if(version_compare(JVERSION,'1.6.0','ge')) {
				$db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `element` = '.$db->Quote('mod_'.$module).' AND `type` = "module"');
			} else {
				$db->setQuery('SELECT `id` FROM #__modules WHERE `module` = '.$db->Quote('mod_'.$module));
			}
			$id = $db->loadResult();
			// Uninstall the module
			$installer = new JInstaller;
			$result = $installer->uninstall('module',$id,1);
			$status->modules[] = array('name'=>'mod_'.$module,'client'=>$folder, 'result'=>$result);
		}
	}
}

// Plugins uninstallation
if(count($installation_queue['plugins'])) {
	foreach($installation_queue['plugins'] as $folder => $plugins) {
		if(count($plugins)) foreach($plugins as $plugin => $published) {
			if(version_compare(JVERSION,'1.6.0','ge')) {
				$db->setQuery('SELECT `extension_id` FROM #__extensions WHERE `type` = "plugin" AND `element` = '.$db->Quote($plugin).' AND `folder` = '.$db->Quote($folder));
			} else {
				$db->setQuery('SELECT `id` FROM #__plugins WHERE `element` = '.$db->Quote($plugin).' AND `folder` = '.$db->Quote($folder));
			}
			
			$id = $db->loadResult();
			if($id)
			{
				$installer = new JInstaller;
				$result = $installer->uninstall('plugin',$id,1);
				$status->plugins[] = array('name'=>'plg_'.$plugin,'group'=>$folder, 'result'=>$result);
			}			
		}
	}
}


$akeeba_installation_has_run = true;
?>

<?php $rows = 0;?>
<h2 style="font-size: 14pt; font-weight: black; padding: 0; margin: 0 0 0.5em;">&nbsp;Akeeba Subscriptions Uninstallation</h2>
<p>We are sorry that you decided to uninstall Akeeba Subscriptions. Please let us know why by posting in the Pre-Sales area of our ticket system. We appreciate your feedback; it helps us develop better software!</p>
<table class="adminlist">
	<thead>
		<tr>
			<th class="title" colspan="2">Extension</th>
			<th width="30%">Status</th>
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
				<strong>Akeeba Subscriptions component</strong>
			</td>
			<td><strong style="color: green">Uninstalled</strong></td>
		</tr>
		<?php if (count($status->modules)) : ?>
		<tr>
			<th>Module</th>
			<th>Client</th>
			<th></th>
		</tr>
		<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php echo $module['client']; ?></td>
			<td>
				<span style="color: <?php echo ($module['result'])?'green':'red'?>; font-weight: bold;">
					<?php echo ($module['result'])?'Uninstalled':'Not Uninstalled'; ?>
				</span>
			</td>
		</tr>
		<?php endforeach;?>
		<?php endif;?>
		<?php if (count($status->plugins)) : ?>
		<tr>
			<th>Plugin</th>
			<th>Group</th>
			<th></th>
		</tr>
		<?php foreach ($status->plugins as $plugin) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $plugin['name']; ?></td>
			<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
			<td>
				<span style="color: <?php echo ($plugin['result'])?'green':'red'?>; font-weight: bold;">
					<?php ($plugin['result'])?'Uninstalled':'Not Uninstalled'; ?>
				</span>
			</td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>