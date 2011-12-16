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
			'adminemails'			=> 0,
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

// Load the translation strings (Joomla! 1.5 and 1.6 compatible)
if( version_compare( JVERSION, '1.6.0', 'lt' ) ) {
	global $j15;
	// Joomla! 1.5 will have to load the translation strings
	$j15 = true;
	$jlang =& JFactory::getLanguage();
	$path = JPATH_ADMINISTRATOR.'/components/com_akeebasubs';
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

$akeeba_installation_has_run = true;
?>

<?php $rows = 0;?>
<h2 style="font-size: 14pt; font-weight: black; padding: 0; margin: 0 0 0.5em;">&nbsp;<?php pitext('COM_AKEEBASUBS_PIUNINSTALL'); ?></h2>
<p><?php pitext('COM_AKEEBASUBS_PIUNINSTALLTEXT')?></p>
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
				<strong><?php pitext('COM_AKEEBASUBS_PICOMPONENT'); ?></strong>
			</td>
			<td><strong style="color: green"><?php pitext('COM_AKEEBASUBS_PIUNINSTALLED');?></strong></td>
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
					<?php ($module['result'])?pitext('COM_AKEEBASUBS_PIUNINSTALLED'):pitext('COM_AKEEBASUBS_PINOTUNINSTALLED'); ?>
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
					<?php ($plugin['result'])?pitext('COM_AKEEBASUBS_PIUNINSTALLED'):pitext('COM_AKEEBASUBS_PINOTUNINSTALLED'); ?>
				</span>
			</td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>