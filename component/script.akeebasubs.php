<?php
/**
 *  @package	akeebasubs
 *  @copyright	Copyright (c)2010-2014 Nicholas K. Dionysopoulos / AkeebaBackup.com
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

JLoader::import('joomla.filesystem.folder');
JLoader::import('joomla.filesystem.file');

class Com_AkeebasubsInstallerScript
{
	/** @var string The component's name */
	protected $_akeeba_extension = 'com_akeebasubs';

	/** @var array The list of extra modules and plugins to install */
	private $installation_queue = array(
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
				'adminemails'			=> 0,
				'agreetotos'			=> 0,
				'atscredits'			=> 0,
				'autocity'				=> 0,
				'canalyticscommerce'	=> 0,
				'contentpublish'		=> 0,
				'customfields'			=> 1,
				'invoices'				=> 0,
				'iproperty'				=> 0,
				'joomla'				=> 1,
				'joomlaprofilesync'		=> 1,
				'recaptcha'				=> 0,
				'slavesubs'				=> 1,
				'sql'					=> 0,
				'subscriptionemails'	=> 1,
			),
			'akpayment' => array(
				'2checkout'				=> 0,
				'2conew'				=> 0,
				'none'					=> 0,
				'offline'				=> 0,
				'paymill'				=> 0,
				'paypal'				=> 1,
				'paypalpaymentspro'		=> 0,
				'paypalproexpress'		=> 0,
				'skrill'				=> 0,
				'stripe'				=> 0,
				'viva'					=> 0,
			),
			'content' => array(
				'aslink'				=> 1,
				'asrestricted'			=> 1,
				'astimedrelease'		=> 1,
			),
            'sh404sefextplugins' => array(
                'com_akeebasubs'        => 1
            ),
			'system' => array(
				'asexpirationcontrol'	=> 1,
				'asexpirationnotify'	=> 1,
				'aslogoutuser'			=> 0,
				'asuserregredir'		=> 0,
			)
		)
	);

	/** @var array Obsolete files and folders to remove */
	private $akeebaRemoveFiles = array(
		'files'	=> array(
			'cache/com_akeebasubs.updates.php',
			'cache/com_akeebasubs.updates.ini',
			'administrator/cache/com_akeebasubs.updates.php',
			'administrator/cache/com_akeebasubs.updates.ini',

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
			'media/com_akeebasubs/js/akeebajq.js',

			'administrator/components/com_akeebasubs/fof/LICENSE.txt',
			'administrator/components/com_akeebasubs/fof/controller.php',
			'administrator/components/com_akeebasubs/fof/dispatcher.php',
			'administrator/components/com_akeebasubs/fof/index.html',
			'administrator/components/com_akeebasubs/fof/inflector.php',
			'administrator/components/com_akeebasubs/fof/input.php',
			'administrator/components/com_akeebasubs/fof/model.php',
			'administrator/components/com_akeebasubs/fof/query.abstract.php',
			'administrator/components/com_akeebasubs/fof/query.element.php',
			'administrator/components/com_akeebasubs/fof/query.mysql.php',
			'administrator/components/com_akeebasubs/fof/query.mysqli.php',
			'administrator/components/com_akeebasubs/fof/query.sqlazure.php',
			'administrator/components/com_akeebasubs/fof/query.sqlsrv.php',
			'administrator/components/com_akeebasubs/fof/table.php',
			'administrator/components/com_akeebasubs/fof/template.utils.php',
			'administrator/components/com_akeebasubs/fof/toolbar.php',
			'administrator/components/com_akeebasubs/fof/view.csv.php',
			'administrator/components/com_akeebasubs/fof/view.html.php',
			'administrator/components/com_akeebasubs/fof/view.json.php',
			'administrator/components/com_akeebasubs/fof/view.php',

			// Old fonts
			'media/com_akeebasubs/tcdpf/fonts/courier.php',
			'media/com_akeebasubs/tcdpf/fonts/courierb.php',
			'media/com_akeebasubs/tcdpf/fonts/courierbi.php',
			'media/com_akeebasubs/tcdpf/fonts/courieri.php',
			'media/com_akeebasubs/tcdpf/fonts/helvetica.php',
			'media/com_akeebasubs/tcdpf/fonts/helveticab.php',
			'media/com_akeebasubs/tcdpf/fonts/helveticabi.php',
			'media/com_akeebasubs/tcdpf/fonts/helveticai.php',
			'media/com_akeebasubs/tcdpf/fonts/symbol.php',
			'media/com_akeebasubs/tcdpf/fonts/times.php',
			'media/com_akeebasubs/tcdpf/fonts/timesbi.php',
			'media/com_akeebasubs/tcdpf/fonts/timesb.php',
			'media/com_akeebasubs/tcdpf/fonts/timesi.php',
			'media/com_akeebasubs/tcdpf/fonts/zapfdingbats.php',

			// Old PHP views, replaced with XML views
			'administrator/components/com_akeebasubs/view/affiliates/tmpl/default.php',
			'administrator/components/com_akeebasubs/view/affpayments/tmpl/default.php',
			'administrator/components/com_akeebasubs/view/customfields/tmpl/default.php',
			'administrator/components/com_akeebasubs/view/levelgroups/tmpl/default.php',
			'administrator/components/com_akeebasubs/view/levels/tmpl/default.php',
			'administrator/components/com_akeebasubs/view/levels/tmpl/form.php',
			'administrator/components/com_akeebasubs/view/states/tmpl/default.php',
			'administrator/components/com_akeebasubs/view/upgrades/tmpl/default.php',
			'administrator/components/com_akeebasubs/view/users/tmpl/default.php',

			// Do not delete (used to render custom form page elements):
			// 'administrator/components/com_akeebasubs/view/coupons/tmpl/default.php',
			// 'administrator/components/com_akeebasubs/view/invoices/tmpl/default.php',
			// 'administrator/components/com_akeebasubs/view/taxrules/tmpl/default.php',

		),
		'folders' => array(
			'administrator/components/com_akeebasubs/commands',
			'administrator/components/com_akeebasubs/controllers/behaviours',
			'administrator/components/com_akeebasubs/controllers/toolbars',
			'administrator/components/com_akeebasubs/databases',
			'administrator/components/com_akeebasubs/invoicetemplates',
			'administrator/components/com_akeebasubs/simpleforms',
			'administrator/components/com_akeebasubs/templates',
			'administrator/components/com_akeebasubs/toolbars',
			'administrator/components/com_akeebasubs/toolbars-xxx',
			'administrator/components/com_akeebasubs/views/config',
			'administrator/components/com_akeebasubs/views/dashboard',
			'components/com_akeebasubs/templates',
			'components/com_akeebasubs/controllers/behaviors',
		)
	);

	private $akeebaCliScripts = array(
		'akeebasubs-expiration-notify.php',
	);

	/**
	 * Joomla! pre-flight event
	 *
	 * @param string $type Installation type (install, update, discover_install)
	 * @param JInstaller $parent Parent object
	 */
	public function preflight($type, $parent)
	{
		// Only allow to install on Joomla! 2.5.0 or later with PHP 5.3.0 or later
		if(defined('PHP_VERSION')) {
			$version = PHP_VERSION;
		} elseif(function_exists('phpversion')) {
			$version = phpversion();
		} else {
			$version = '5.0.0'; // all bets are off!
		}
		if(!version_compare(JVERSION, '2.5.6', 'ge')) {
			$msg = "<p>You need Joomla! 2.5.6 or later to install this component</p>";
			JError::raiseWarning(100, $msg);
			return false;
		}
		if(!version_compare($version, '5.3.1', 'ge')) {
			$msg = "<p>You need PHP 5.3.1 or later to install this component</p>";
			if(version_compare(JVERSION, '3.0', 'gt'))
			{
				JLog::add($msg, JLog::WARNING, 'jerror');
			}
			else
			{
				JError::raiseWarning(100, $msg);
			}
			return false;
		}

		// Bugfix for "Can not build admin menus"
		if(in_array($type, array('install')))
		{
			$this->_bugfixDBFunctionReturnedNoError();
		}
		elseif ($type != 'discover_install')
		{
			$this->_bugfixCantBuildAdminMenus();
		}

		return true;
	}

	/**
	 * Runs after install, update or discover_update
	 * @param string $type install, update or discover_update
	 * @param JInstaller $parent
	 */
	function postflight( $type, $parent )
	{
		// Load FOF if not already loaded
		if (!defined('F0F_INCLUDED'))
		{
			$paths = array(
				(defined('JPATH_LIBRARIES') ? JPATH_LIBRARIES : JPATH_ROOT . '/libraries') . '/f0f/include.php',
				$parent->getParent()->getPath('source') . '/fof/include.php',
			);

			foreach ($paths as $filePath)
			{
				if (!defined('F0F_INCLUDED') && file_exists($filePath))
				{
					@include_once $filePath;
				}
			}
		}

		// Install or update database
		$dbInstaller = new F0FDatabaseInstaller(array(
			'dbinstaller_directory' => JPATH_ADMINISTRATOR . '/components/' . $this->_akeeba_extension . '/sql/xml'
		));
		$dbInstaller->updateSchema();

		// Install subextensions
		$status = $this->_installSubextensions($parent);

		// Install FOF
		$fofStatus = $this->_installFOF($parent);

		// Install Akeeba Straper
		$straperStatus = $this->_installStraper($parent);

		// Remove obsolete files and folders
		$akeebaRemoveFiles = $this->akeebaRemoveFiles;
		$this->_removeObsoleteFilesAndFolders($akeebaRemoveFiles);

		$this->_copyCliFiles($parent);

		// Show the post-installation page
		$this->_renderPostInstallation($status, $fofStatus, $straperStatus, $parent);

		// Clear FOF's cache
		if (!defined('F0F_INCLUDED'))
		{
			@include_once JPATH_LIBRARIES . '/f0f/include.php';
		}

		if (defined('FOF_INCLUDED'))
		{
			$platform = F0FPlatform::getInstance();
			if (method_exists($platform, 'clearCache'))
			{
				F0FPlatform::getInstance()->clearCache();
			}
		}
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param JInstaller $parent
	 */
	function uninstall($parent)
	{
		// Load FOF if not already loaded
		if (!defined('F0F_INCLUDED'))
		{
			$paths = array(
				(defined('JPATH_LIBRARIES') ? JPATH_LIBRARIES : JPATH_ROOT . '/libraries') . '/f0f/include.php',
				$parent->getParent()->getPath('source') . '/fof/include.php',
			);

			foreach ($paths as $filePath)
			{
				if (!defined('F0F_INCLUDED') && file_exists($filePath))
				{
					@include_once $filePath;
				}
			}
		}

		// Install or update database
		$dbInstaller = new F0FDatabaseInstaller(array(
			'dbinstaller_directory' => JPATH_ADMINISTRATOR . '/components/' . $this->_akeeba_extension . '/sql/xml'
		));
		$dbInstaller->removeSchema();

		// Uninstall subextensions
		$status = $this->_uninstallSubextensions($parent);

		// Show the post-uninstallation page
		$this->_renderPostUninstallation($status, $parent);
	}

	/**
	 * Copies the CLI scripts into Joomla!'s cli directory
	 *
	 * @param JInstaller $parent
	 */
	private function _copyCliFiles($parent)
	{
		$src = $parent->getParent()->getPath('source');

		JLoader::import("joomla.filesystem.file");
		JLoader::import("joomla.filesystem.folder");

		if(empty($this->akeebaCliScripts)) {
			return;
		}

		foreach($this->akeebaCliScripts as $script) {
			if(JFile::exists(JPATH_ROOT.'/cli/'.$script)) {
				JFile::delete(JPATH_ROOT.'/cli/'.$script);
			}
			if(JFile::exists($src.'/cli/'.$script)) {
				JFile::move($src.'/cli/'.$script, JPATH_ROOT.'/cli/'.$script);
			}
		}
	}

	/**
	 * Renders the post-installation message
	 */
	private function _renderPostInstallation($status, $fofStatus, $straperStatus, $parent)
	{
?>

<h1>Akeeba Subscriptions</h1>

<?php $rows = 1;?>
<img src="../media/com_akeebasubs/images/akeebasubs-48.png" width="48" height="48" alt="Akeeba Subscriptions" align="left" />
<h2 style="font-size: 14pt; font-weight: black; padding: 0; margin: 0 0 0.5em;">Welcome to Akeeba Subscriptions!</h2>
<span>The easiest way to sell subscriptions on your Joomla! site</span>

<div style="margin: 1em; font-size: 14pt; background-color: #fffff9; color: black">
	You can download translation files <a href="http://cdn.akeebabackup.com/language/akeebasubs/index.html">directly from our CDN page</a>.
</div>

<table class="adminlist table table-striped" width="100%">
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
				<img src="../media/com_akeebasubs/images/akeebasubs-16.png" width="16" height="16" alt="Akeeba Subscriptions" align="left" />
				&nbsp;
				Akeeba Subscriptions component
			</td>
			<td><strong style="color: green">Installed</strong></td>
		</tr>
		<tr class="row1">
			<td class="key" colspan="2">
				<strong>Framework on Framework (FOF) <?php echo $fofStatus['version']?></strong> [<?php echo $fofStatus['date'] ?>]
			</td>
			<td><strong>
				<span style="color: <?php echo $fofStatus['required'] ? ($fofStatus['installed']?'green':'red') : '#660' ?>; font-weight: bold;">
					<?php echo $fofStatus['required'] ? ($fofStatus['installed'] ?'Installed':'Not Installed') : 'Already up-to-date'; ?>
				</span>
			</strong></td>
		</tr>
		<tr class="row0">
			<td class="key" colspan="2">
				<strong>Akeeba Strapper <?php echo $straperStatus['version']?></strong> [<?php echo $straperStatus['date'] ?>]
			</td>
			<td><strong>
				<span style="color: <?php echo $straperStatus['required'] ? ($straperStatus['installed']?'green':'red') : '#660' ?>; font-weight: bold;">
					<?php echo $straperStatus['required'] ? ($straperStatus['installed'] ?'Installed':'Not Installed') : 'Already up-to-date'; ?>
				</span>
			</strong></td>
		</tr>
		<?php if (count($status->modules)) : ?>
		<tr>
			<th>Module</th>
			<th>Client</th>
			<th></th>
		</tr>
		<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo ($rows++ % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php echo ucfirst($module['client']); ?></td>
			<td><strong style="color: <?php echo ($module['result'])? "green" : "red"?>"><?php echo ($module['result'])?'Installed':'Not installed'; ?></strong></td>
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
		<tr class="row<?php echo ($rows++ % 2); ?>">
			<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
			<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
			<td><strong style="color: <?php echo ($plugin['result'])? "green" : "red"?>"><?php echo ($plugin['result'])?'Installed':'Not installed'; ?></strong></td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
<?php
	}

	private function _renderPostUninstallation($status, $parent) {
?>
<?php $rows = 0;?>
<h2 style="font-size: 14pt; font-weight: black; padding: 0; margin: 0 0 0.5em;">&nbsp;Akeeba Subscriptions Uninstallation</h2>
<p>We are sorry that you decided to uninstall Akeeba Subscriptions. Please let us know why by using the Contact Us form on our site. We appreciate your feedback; it helps us develop better software!</p>

<table class="adminlist table table-striped" width="100%">
	<thead>
		<tr>
			<th class="title" colspan="2"><?php echo JText::_('Extension'); ?></th>
			<th width="30%"><?php echo JText::_('Status'); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="3"></td>
		</tr>
	</tfoot>
	<tbody>
		<tr class="row0">
			<td class="key" colspan="2"><?php echo 'Akeeba Subscriptions '.JText::_('Component'); ?></td>
			<td><strong style="color: green"><?php echo JText::_('Removed'); ?></strong></td>
		</tr>
		<?php if (count($status->modules)) : ?>
		<tr>
			<th><?php echo JText::_('Module'); ?></th>
			<th><?php echo JText::_('Client'); ?></th>
			<th></th>
		</tr>
		<?php foreach ($status->modules as $module) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo $module['name']; ?></td>
			<td class="key"><?php echo ucfirst($module['client']); ?></td>
			<td><strong style="color: <?php echo ($module['result'])? "green" : "red"?>"><?php echo ($module['result'])?JText::_('Removed'):JText::_('Not removed'); ?></strong></td>
		</tr>
		<?php endforeach;?>
		<?php endif;?>
		<?php if (count($status->plugins)) : ?>
		<tr>
			<th><?php echo JText::_('Plugin'); ?></th>
			<th><?php echo JText::_('Group'); ?></th>
			<th></th>
		</tr>
		<?php foreach ($status->plugins as $plugin) : ?>
		<tr class="row<?php echo (++ $rows % 2); ?>">
			<td class="key"><?php echo ucfirst($plugin['name']); ?></td>
			<td class="key"><?php echo ucfirst($plugin['group']); ?></td>
			<td><strong style="color: <?php echo ($plugin['result'])? "green" : "red"?>"><?php echo ($plugin['result'])?JText::_('Removed'):JText::_('Not removed'); ?></strong></td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
<?php
	}

	/**
	 * Joomla! 1.6+ bugfix for "DB function returned no error"
	 */
	private function _bugfixDBFunctionReturnedNoError()
	{
		$db = JFactory::getDbo();

		// Fix broken #__assets records
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__assets')
			->where($db->qn('name').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__assets')
				->where($db->qn('id').' = '.$db->q($id));
			$db->setQuery($query);
			$db->execute();
		}

		// Fix broken #__extensions records
		$query = $db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($db->qn('element').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__extensions')
				->where($db->qn('extension_id').' = '.$db->q($id));
			$db->setQuery($query);
			$db->execute();
		}

		// Fix broken #__menu records
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('menutype').' = '.$db->q('main'))
			->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__menu')
				->where($db->qn('id').' = '.$db->q($id));
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Joomla! 1.6+ bugfix for "Can not build admin menus"
	 */
	private function _bugfixCantBuildAdminMenus()
	{
		$db = JFactory::getDbo();

		// If there are multiple #__extensions record, keep one of them
		$query = $db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($db->qn('element').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadColumn();
		if(count($ids) > 1) {
			asort($ids);
			$extension_id = array_shift($ids); // Keep the oldest id

			foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__extensions')
					->where($db->qn('extension_id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->execute();
			}
		}

		// @todo

		// If there are multiple assets records, delete all except the oldest one
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__assets')
			->where($db->qn('name').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$ids = $db->loadObjectList();
		if(count($ids) > 1) {
			asort($ids);
			$asset_id = array_shift($ids); // Keep the oldest id

			foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__assets')
					->where($db->qn('id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->execute();
			}
		}

		// Remove #__menu records for good measure!
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('menutype').' = '.$db->q('main'))
			->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_akeeba_extension));
		$db->setQuery($query);
		$ids1 = $db->loadColumn();
		if(empty($ids1)) $ids1 = array();
		$query = $db->getQuery(true);
		$query->select('id')
			->from('#__menu')
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('menutype').' = '.$db->q('main'))
			->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_akeeba_extension.'&%'));
		$db->setQuery($query);
		$ids2 = $db->loadColumn();
		if(empty($ids2)) $ids2 = array();
		$ids = array_merge($ids1, $ids2);
		if(!empty($ids)) foreach($ids as $id) {
			$query = $db->getQuery(true);
			$query->delete('#__menu')
				->where($db->qn('id').' = '.$db->q($id));
			$db->setQuery($query);
			$db->execute();
		}
	}

	/**
	 * Installs subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param JInstaller $parent
	 * @return JObject The subextension installation status
	 */
	private function _installSubextensions($parent)
	{
		$src = $parent->getParent()->getPath('source');

		$db = JFactory::getDbo();

		$status = new JObject();
		$status->modules = array();
		$status->plugins = array();

		// Modules installation
		if(count($this->installation_queue['modules'])) {
			foreach($this->installation_queue['modules'] as $folder => $modules) {
				if(count($modules)) foreach($modules as $module => $modulePreferences) {
					// Install the module
					if(empty($folder)) $folder = 'site';
					$path = "$src/modules/$folder/$module";
					if(!is_dir($path)) {
						$path = "$src/modules/$folder/mod_$module";
					}
					if(!is_dir($path)) {
						$path = "$src/modules/$module";
					}
					if(!is_dir($path)) {
						$path = "$src/modules/mod_$module";
					}
					if(!is_dir($path)) continue;
					// Was the module already installed?
					$sql = $db->getQuery(true)
						->select('COUNT(*)')
						->from('#__modules')
						->where($db->qn('module').' = '.$db->q('mod_'.$module));
					$db->setQuery($sql);
					$count = $db->loadResult();
					$installer = new JInstaller;
					$result = $installer->install($path);
					$status->modules[] = array(
						'name'=>'mod_'.$module,
						'client'=>$folder,
						'result'=>$result
					);
					// Modify where it's published and its published state
					if(!$count) {
						// A. Position and state
						list($modulePosition, $modulePublished) = $modulePreferences;
						if($modulePosition == 'cpanel') {
							$modulePosition = 'icon';
						}
						$sql = $db->getQuery(true)
							->update($db->qn('#__modules'))
							->set($db->qn('position').' = '.$db->q($modulePosition))
							->where($db->qn('module').' = '.$db->q('mod_'.$module));
						if($modulePublished) {
							$sql->set($db->qn('published').' = '.$db->q('1'));
						}
						$db->setQuery($sql);
						$db->execute();

						// B. Change the ordering of back-end modules to 1 + max ordering
						if($folder == 'admin') {
							$query = $db->getQuery(true);
							$query->select('MAX('.$db->qn('ordering').')')
								->from($db->qn('#__modules'))
								->where($db->qn('position').'='.$db->q($modulePosition));
							$db->setQuery($query);
							$position = $db->loadResult();
							$position++;

							$query = $db->getQuery(true);
							$query->update($db->qn('#__modules'))
								->set($db->qn('ordering').' = '.$db->q($position))
								->where($db->qn('module').' = '.$db->q('mod_'.$module));
							$db->setQuery($query);
							$db->execute();
						}

						// C. Link to all pages
						$query = $db->getQuery(true);
						$query->select('id')->from($db->qn('#__modules'))
							->where($db->qn('module').' = '.$db->q('mod_'.$module));
						$db->setQuery($query);
						$moduleid = $db->loadResult();

						$query = $db->getQuery(true);
						$query->select('*')->from($db->qn('#__modules_menu'))
							->where($db->qn('moduleid').' = '.$db->q($moduleid));
						$db->setQuery($query);
						$assignments = $db->loadObjectList();
						$isAssigned = !empty($assignments);
						if(!$isAssigned) {
							$o = (object)array(
								'moduleid'	=> $moduleid,
								'menuid'	=> 0
							);
							$db->insertObject('#__modules_menu', $o);
						}
					}
				}
			}
		}

		// Plugins installation
		if(count($this->installation_queue['plugins'])) {
			foreach($this->installation_queue['plugins'] as $folder => $plugins) {
				if(count($plugins)) foreach($plugins as $plugin => $published) {
					$path = "$src/plugins/$folder/$plugin";
					if(!is_dir($path)) {
						$path = "$src/plugins/$folder/plg_$plugin";
					}
					if(!is_dir($path)) {
						$path = "$src/plugins/$plugin";
					}
					if(!is_dir($path)) {
						$path = "$src/plugins/plg_$plugin";
					}
					if(!is_dir($path)) continue;

					// Was the plugin already installed?
					$query = $db->getQuery(true)
						->select('COUNT(*)')
						->from($db->qn('#__extensions'))
						->where($db->qn('element').' = '.$db->q($plugin))
						->where($db->qn('folder').' = '.$db->q($folder));
					$db->setQuery($query);
					$count = $db->loadResult();

					$installer = new JInstaller;
					$result = $installer->install($path);

					$status->plugins[] = array('name'=>'plg_'.$plugin,'group'=>$folder, 'result'=>$result);

					if($published && !$count) {
						$query = $db->getQuery(true)
							->update($db->qn('#__extensions'))
							->set($db->qn('enabled').' = '.$db->q('1'))
							->where($db->qn('element').' = '.$db->q($plugin))
							->where($db->qn('folder').' = '.$db->q($folder));
						$db->setQuery($query);
						$db->execute();
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Uninstalls subextensions (modules, plugins) bundled with the main extension
	 *
	 * @param JInstaller $parent
	 * @return JObject The subextension uninstallation status
	 */
	private function _uninstallSubextensions($parent)
	{
		JLoader::import('joomla.installer.installer');

		$db = JFactory::getDBO();

		$status = new JObject();
		$status->modules = array();
		$status->plugins = array();

		$src = $parent->getParent()->getPath('source');

		// Modules uninstallation
		if(count($this->installation_queue['modules'])) {
			foreach($this->installation_queue['modules'] as $folder => $modules) {
				if(count($modules)) foreach($modules as $module => $modulePreferences) {
					// Find the module ID
					$sql = $db->getQuery(true)
						->select($db->qn('extension_id'))
						->from($db->qn('#__extensions'))
						->where($db->qn('element').' = '.$db->q('mod_'.$module))
						->where($db->qn('type').' = '.$db->q('module'));
					$db->setQuery($sql);
					$id = $db->loadResult();
					// Uninstall the module
					if($id) {
						$installer = new JInstaller;
						$result = $installer->uninstall('module',$id,1);
						$status->modules[] = array(
							'name'=>'mod_'.$module,
							'client'=>$folder,
							'result'=>$result
						);
					}
				}
			}
		}

		// Plugins uninstallation
		if(count($this->installation_queue['plugins'])) {
			foreach($this->installation_queue['plugins'] as $folder => $plugins) {
				if(count($plugins)) foreach($plugins as $plugin => $published) {
					$sql = $db->getQuery(true)
						->select($db->qn('extension_id'))
						->from($db->qn('#__extensions'))
						->where($db->qn('type').' = '.$db->q('plugin'))
						->where($db->qn('element').' = '.$db->q($plugin))
						->where($db->qn('folder').' = '.$db->q($folder));
					$db->setQuery($sql);

					$id = $db->loadResult();
					if($id)
					{
						$installer = new JInstaller;
						$result = $installer->uninstall('plugin',$id,1);
						$status->plugins[] = array(
							'name'=>'plg_'.$plugin,
							'group'=>$folder,
							'result'=>$result
						);
					}
				}
			}
		}

		return $status;
	}

	/**
	 * Removes obsolete files and folders
	 *
	 * @param array $akeebaRemoveFiles
	 */
	private function _removeObsoleteFilesAndFolders($akeebaRemoveFiles)
	{
		// Remove files
		JLoader::import('joomla.filesystem.file');
		if(!empty($akeebaRemoveFiles['files'])) foreach($akeebaRemoveFiles['files'] as $file) {
			$f = JPATH_ROOT.'/'.$file;
			if(!JFile::exists($f)) continue;
			JFile::delete($f);
		}

		// Remove folders
		JLoader::import('joomla.filesystem.file');
		if(!empty($akeebaRemoveFiles['folders'])) foreach($akeebaRemoveFiles['folders'] as $folder) {
			$f = JPATH_ROOT.'/'.$folder;
			if(!JFolder::exists($f)) continue;
			JFolder::delete($f);
		}
	}

	private function _installFOF($parent)
	{
		$src = $parent->getParent()->getPath('source');

		// Install the FOF framework
		JLoader::import('joomla.filesystem.folder');
		JLoader::import('joomla.filesystem.file');
		JLoader::import('joomla.utilities.date');
		$source = $src . '/fof';

		if (!defined('JPATH_LIBRARIES'))
		{
			$target = JPATH_ROOT . '/libraries/f0f';
		}
		else
		{
			$target = JPATH_LIBRARIES . '/f0f';
		}

		$haveToInstallFOF = false;

		if (!JFolder::exists($target))
		{
			$haveToInstallFOF = true;
		}
		else
		{
			$fofVersion = array();

			if (JFile::exists($target . '/version.txt'))
			{
				$rawData				 = JFile::read($target . '/version.txt');
				$info					 = explode("\n", $rawData);
				$fofVersion['installed'] = array(
					'version'	 => trim($info[0]),
					'date'		 => new JDate(trim($info[1]))
				);
			}
			else
			{
				$fofVersion['installed'] = array(
					'version'	 => '0.0',
					'date'		 => new JDate('2011-01-01')
				);
			}

			$rawData				 = JFile::read($source . '/version.txt');
			$info					 = explode("\n", $rawData);

			$fofVersion['package']	 = array(
				'version'	 => trim($info[0]),
				'date'		 => new JDate(trim($info[1]))
			);

			$haveToInstallFOF = $fofVersion['package']['date']->toUNIX() > $fofVersion['installed']['date']->toUNIX();
		}

		$installedFOF = false;

		if ($haveToInstallFOF)
		{
			$versionSource	 = 'package';
			$installer		 = new JInstaller;
			$installedFOF	 = $installer->install($source);
		}
		else
		{
			$versionSource = 'installed';
		}

		if (!isset($fofVersion))
		{
			$fofVersion = array();

			if (JFile::exists($target . '/version.txt'))
			{
				$rawData				 = JFile::read($target . '/version.txt');
				$info					 = explode("\n", $rawData);
				$fofVersion['installed'] = array(
					'version'	 => trim($info[0]),
					'date'		 => new JDate(trim($info[1]))
				);
			}
			else
			{
				$fofVersion['installed'] = array(
					'version'	 => '0.0',
					'date'		 => new JDate('2011-01-01')
				);
			}

			$rawData				 = JFile::read($source . '/version.txt');
			$info					 = explode("\n", $rawData);

			$fofVersion['package']	 = array(
				'version'	 => trim($info[0]),
				'date'		 => new JDate(trim($info[1]))
			);

			$versionSource			 = 'installed';
		}

		if (!($fofVersion[$versionSource]['date'] instanceof JDate))
		{
			$fofVersion[$versionSource]['date'] = new JDate();
		}

		return array(
			'required'	 => $haveToInstallFOF,
			'installed'	 => $installedFOF,
			'version'	 => $fofVersion[$versionSource]['version'],
			'date'		 => $fofVersion[$versionSource]['date']->format('Y-m-d'),
		);
	}

	private function _installStraper($parent)
	{
		$src = $parent->getParent()->getPath('source');

		// Install the FOF framework
		JLoader::import('joomla.filesystem.folder');
		JLoader::import('joomla.filesystem.file');
		JLoader::import('joomla.utilities.date');
		$source = $src.'/strapper';
		$target = JPATH_ROOT.'/media/akeeba_strapper';

		$haveToInstallStraper = false;
		if(!JFolder::exists($target)) {
			$haveToInstallStraper = true;
		} else {
			$straperVersion = array();
			if(JFile::exists($target.'/version.txt')) {
				$rawData = JFile::read($target.'/version.txt');
				$info = explode("\n", $rawData);
				$straperVersion['installed'] = array(
					'version'	=> trim($info[0]),
					'date'		=> new JDate(trim($info[1]))
				);
			} else {
				$straperVersion['installed'] = array(
					'version'	=> '0.0',
					'date'		=> new JDate('2011-01-01')
				);
			}
			$rawData = JFile::read($source.'/version.txt');
			$info = explode("\n", $rawData);
			$straperVersion['package'] = array(
				'version'	=> trim($info[0]),
				'date'		=> new JDate(trim($info[1]))
			);

			$haveToInstallStraper = $straperVersion['package']['date']->toUNIX() > $straperVersion['installed']['date']->toUNIX();
		}

		$installedStraper = false;
		if($haveToInstallStraper) {
			$versionSource = 'package';
			$installer = new JInstaller;
			$installedStraper = $installer->install($source);
		} else {
			$versionSource = 'installed';
		}

		if(!isset($straperVersion)) {
			$straperVersion = array();
			if(JFile::exists($target.'/version.txt')) {
				$rawData = JFile::read($target.'/version.txt');
				$info = explode("\n", $rawData);
				$straperVersion['installed'] = array(
					'version'	=> trim($info[0]),
					'date'		=> new JDate(trim($info[1]))
				);
			} else {
				$straperVersion['installed'] = array(
					'version'	=> '0.0',
					'date'		=> new JDate('2011-01-01')
				);
			}
			$rawData = JFile::read($source.'/version.txt');
			$info = explode("\n", $rawData);
			$straperVersion['package'] = array(
				'version'	=> trim($info[0]),
				'date'		=> new JDate(trim($info[1]))
			);
			$versionSource = 'installed';
		}

		if(!($straperVersion[$versionSource]['date'] instanceof JDate)) {
			$straperVersion[$versionSource]['date'] = new JDate();
		}

		return array(
			'required'	=> $haveToInstallStraper,
			'installed'	=> $installedStraper,
			'version'	=> $straperVersion[$versionSource]['version'],
			'date'		=> $straperVersion[$versionSource]['date']->format('Y-m-d'),
		);
	}
}