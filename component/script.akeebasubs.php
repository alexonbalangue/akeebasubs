<?php
/**
 *  @package	akeebasubs
 *  @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
				'aceshop'				=> 0,
				'acymailing'			=> 0,
				'adminemails'			=> 0,
				'affemails'				=> 0,
				'ageverification'		=> 0,
				'agora'					=> 0,
				'agreetotos'			=> 0,
				'atscredits'			=> 0,
				'autocity'				=> 0,
				'canalyticscommerce'	=> 0,
				'cb'					=> 0,
				'cbsync'				=> 0,
				'ccinvoices'			=> 0,
				'constantcontact'		=> 0,
				'contentpublish'		=> 0,
				'customfields'			=> 1,
				'easydiscuss'			=> 0,
				'freshbooks'			=> 0,
				'frontenduseraccess'	=> 0,
				'invoices'				=> 0,
				'iplogger'				=> 0,
				'iproperty'				=> 0,
				'jomsocial'				=> 0,
				'joomla'				=> 1,
				'joomlaprofilesync'		=> 1,
				'k2'					=> 0,
				'kunena'				=> 0,
				'mailchimp'				=> 0,
				'mijoshop'				=> 0,
				'projectfork'			=> 0,
				'projectfork4'			=> 0,
				'recaptcha'				=> 0,
				'redshop'				=> 0,
				'redshopusersync'		=> 0,
				'samplefields'			=> 0,
				'slavesubs'				=> 1,
				'sql'					=> 0,
				'subscriptionemails'	=> 1,
				'tracktime'				=> 0,
				'userdelete'			=> 0,
				'vm2'					=> 0,
				'zohoinvoice'			=> 0,
			),
			'akpayment' => array(
				'2checkout'				=> 0,
				'allopass'				=> 0,
				'alphauserpoints'		=> 0,
				'authorizenet'			=> 0,
				'beanstream'			=> 0,
				'braintree'				=> 0,
				'cashu'					=> 0,
				'ccavenue'				=> 0,
				'clickandbuy'			=> 0,
				'cmcic'					=> 0,
				'deltapay'				=> 0,
				'dwolla'				=> 0,
				'epaydk'				=> 0,
				'eselectplus'			=> 0,
				'eway'					=> 0,
				'ewayrapid3'			=> 0,
				'gocardless'			=> 0,
				'googlecheckout'		=> 0,
				'ifthen'				=> 0,
				'moip'					=> 0,
				'moipassinaturas'		=> 0,
				'moneris'				=> 0,
				'nochex'				=> 0,
				'none'					=> 0,
				'offline'				=> 0,
				'pagseguro'				=> 0,
				'payfast'				=> 0,
				'paymill'				=> 0,
				'paypal'				=> 1,
				'paypalpaymentspro'		=> 0,
				'paypalproexpress'		=> 0,
				'payu'					=> 0,
				'postfinancech'			=> 0,
				'przelewy24'			=> 0,
				'rbkmoney'				=> 0,
				'realex'				=> 0,
				'robokassa'				=> 0,
				'scnet'					=> 0,
				'scnetintegrated'		=> 0,
				'skrill'				=> 0,
				'stripe'				=> 0,
				'suomenverkkomaksut'	=> 0,
				'upay'					=> 0,
				'verotel'				=> 0,
				'viva'					=> 0,
				'worldpay'				=> 0,
				'zarinpal'				=> 0,
			),
			'ccinvoicetags' => array(
				'akeebasubs'			=> 1,
			),
			'content' => array(
				'aslink'				=> 1,
				'asrestricted'			=> 1,
				'astimedrelease'		=> 1,
			),
			'system' => array(
				'affiliatesessiongeneration' => 0,
				'asexpirationcontrol'	=> 1,
				'asexpirationnotify'	=> 1,
				'asuserregredir'		=> 0,
				'idevaffiliate'			=> 0,
				'oneclickaction'		=> 1,
				'postaffiliatepro'		=> 0,
			)
		)
	);

	private $akeebaRemovePlugins = array(
		'akeebasubs' => array(
			'communityacl',
			'juga',
			'ninjaboard',
			'tienda',
			'vm',
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

			// FOF 1.x files
			'libraries/fof/controller.php',
			'libraries/fof/dispatcher.php',
			'libraries/fof/inflector.php',
			'libraries/fof/input.php',
			'libraries/fof/model.php',
			'libraries/fof/query.abstract.php',
			'libraries/fof/query.element.php',
			'libraries/fof/query.mysql.php',
			'libraries/fof/query.mysqli.php',
			'libraries/fof/query.sqlazure.php',
			'libraries/fof/query.sqlsrv.php',
			'libraries/fof/render.abstract.php',
			'libraries/fof/render.joomla.php',
			'libraries/fof/render.joomla3.php',
			'libraries/fof/render.strapper.php',
			'libraries/fof/string.utils.php',
			'libraries/fof/table.php',
			'libraries/fof/template.utils.php',
			'libraries/fof/toolbar.php',
			'libraries/fof/view.csv.php',
			'libraries/fof/view.html.php',
			'libraries/fof/view.json.php',
			'libraries/fof/view.php',

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

		),
		'folders' => array(
			'administrator/components/com_akeebasubs/commands',
			'administrator/components/com_akeebasubs/controllers/behaviours',
			'administrator/components/com_akeebasubs/controllers/toolbars',
			'administrator/components/com_akeebasubs/databases',
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
		if(in_array($type, array('install','discover_install'))) {
			$this->_bugfixDBFunctionReturnedNoError();
		} else {
			$this->_bugfixCantBuildAdminMenus();
			$this->_fixBrokenSQLUpdates($parent);
			$this->_fixSchemaVersion();
			$this->_resetLiveUpdate();
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

		// Remove Professional version plugins from Akeeba Backup Core
		$this->_removeObsoletePlugins($parent);

		// Show the post-installation page
		$this->_renderPostInstallation($status, $fofStatus, $straperStatus, $parent);

		// Kill update site
		$this->_killUpdateSite();
	}

	/**
	 * Runs on uninstallation
	 *
	 * @param JInstaller $parent
	 */
	function uninstall($parent)
	{
		// Uninstall subextensions
		$status = $this->_uninstallSubextensions($parent);

		// Show the post-uninstallation page
		$this->_renderPostUninstallation($status, $parent);
	}

	/**
	 * Removes the plugins which have been discontinued
	 *
	 * @param JInstaller $parent
	 */
	private function _removeObsoletePlugins($parent)
	{
		$src = $parent->getParent()->getPath('source');
		$db = JFactory::getDbo();

		foreach($this->akeebaRemovePlugins as $folder => $plugins) {
			foreach($plugins as $plugin) {
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
				}
			}
		}
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
		$source = $src.'/fof';
		if(!defined('JPATH_LIBRARIES')) {
			$target = JPATH_ROOT.'/libraries/fof';
		} else {
			$target = JPATH_LIBRARIES.'/fof';
		}
		$haveToInstallFOF = false;
		if(!JFolder::exists($target)) {
			$haveToInstallFOF = true;
		} else {
			$fofVersion = array();
			if(JFile::exists($target.'/version.txt')) {
				$rawData = JFile::read($target.'/version.txt');
				$info = explode("\n", $rawData);
				$fofVersion['installed'] = array(
					'version'	=> trim($info[0]),
					'date'		=> new JDate(trim($info[1]))
				);
			} else {
				$fofVersion['installed'] = array(
					'version'	=> '0.0',
					'date'		=> new JDate('2011-01-01')
				);
			}
			$rawData = JFile::read($source.'/version.txt');
			$info = explode("\n", $rawData);
			$fofVersion['package'] = array(
				'version'	=> trim($info[0]),
				'date'		=> new JDate(trim($info[1]))
			);

			$haveToInstallFOF = $fofVersion['package']['date']->toUNIX() > $fofVersion['installed']['date']->toUNIX();
		}

		$installedFOF = false;
		if($haveToInstallFOF) {
			$versionSource = 'package';
			$installer = new JInstaller;
			$installedFOF = $installer->install($source);
		} else {
			$versionSource = 'installed';
		}

		if(!isset($fofVersion)) {
			$fofVersion = array();
			if(JFile::exists($target.'/version.txt')) {
				$rawData = JFile::read($target.'/version.txt');
				$info = explode("\n", $rawData);
				$fofVersion['installed'] = array(
					'version'	=> trim($info[0]),
					'date'		=> new JDate(trim($info[1]))
				);
			} else {
				$fofVersion['installed'] = array(
					'version'	=> '0.0',
					'date'		=> new JDate('2011-01-01')
				);
			}
			$rawData = JFile::read($source.'/version.txt');
			$info = explode("\n", $rawData);
			$fofVersion['package'] = array(
				'version'	=> trim($info[0]),
				'date'		=> new JDate(trim($info[1]))
			);
			$versionSource = 'installed';
		}

		if(!($fofVersion[$versionSource]['date'] instanceof JDate)) {
			$fofVersion[$versionSource]['date'] = new JDate();
		}

		return array(
			'required'	=> $haveToInstallFOF,
			'installed'	=> $installedFOF,
			'version'	=> $fofVersion[$versionSource]['version'],
			'date'		=> $fofVersion[$versionSource]['date']->format('Y-m-d'),
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

	/**
	 * Remove the update site specification from Joomla! – we no longer support
	 * that misbehaving crap, thank you very much...
	 */
	private function _killUpdateSite()
	{
		// Get some info on all the stuff we've gotta delete
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select(array(
				$db->qn('s').'.'.$db->qn('update_site_id'),
				$db->qn('e').'.'.$db->qn('extension_id'),
				$db->qn('e').'.'.$db->qn('element'),
				$db->qn('s').'.'.$db->qn('location'),
			))
			->from($db->qn('#__update_sites').' AS '.$db->qn('s'))
			->join('INNER',$db->qn('#__update_sites_extensions').' AS '.$db->qn('se').' ON('.
				$db->qn('se').'.'.$db->qn('update_site_id').' = '.
				$db->qn('s').'.'.$db->qn('update_site_id')
				.')')
			->join('INNER',$db->qn('#__extensions').' AS '.$db->qn('e').' ON('.
				$db->qn('e').'.'.$db->qn('extension_id').' = '.
				$db->qn('se').'.'.$db->qn('extension_id')
				.')')
			->where($db->qn('s').'.'.$db->qn('type').' = '.$db->q('extension'))
			->where($db->qn('e').'.'.$db->qn('type').' = '.$db->q('component'))
			->where($db->qn('e').'.'.$db->qn('element').' = '.$db->q($this->_akeeba_extension))
		;
		$db->setQuery($query);
		$oResult = $db->loadObject();

		// If no record is found, do nothing. We've already killed the monster!
		if(is_null($oResult)) return;

		// Delete the #__update_sites record
		$query = $db->getQuery(true)
			->delete($db->qn('#__update_sites'))
			->where($db->qn('update_site_id').' = '.$db->q($oResult->update_site_id));
		$db->setQuery($query);
		try {
			$db->execute();
		} catch (Exception $exc) {
			// If the query fails, don't sweat about it
		}

		// Delete the #__update_sites_extensions record
		$query = $db->getQuery(true)
			->delete($db->qn('#__update_sites_extensions'))
			->where($db->qn('update_site_id').' = '.$db->q($oResult->update_site_id));
		$db->setQuery($query);
		try {
			$db->execute();
		} catch (Exception $exc) {
			// If the query fails, don't sweat about it
		}

		// Delete the #__updates records
		$query = $db->getQuery(true)
			->delete($db->qn('#__updates'))
			->where($db->qn('update_site_id').' = '.$db->q($oResult->update_site_id));
		$db->setQuery($query);
		try {
			$db->execute();
		} catch (Exception $exc) {
			// If the query fails, don't sweat about it
		}
	}

	/**
	 * When you are upgrading from an old version of the component or when your
	 * site is upgraded from Joomla! 1.5 there is no "schema version" for our
	 * component's tables. As a result Joomla! doesn't run the database queries
	 * and you get a broken installation.
	 *
	 * This method detects this situation, forces a fake schema version "0.0.1"
	 * and lets the crufty mess Joomla!'s extensions installer is to bloody work
	 * as anyone would have expected it to do!
	 */
	private function _fixSchemaVersion()
	{
		// Get the extension ID
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($db->qn('element').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$eid = $db->loadResult();

		if (!$eid)
		{
			return;
		}

		$query = $db->getQuery(true);
		$query->select('version_id')
			->from('#__schemas')
			->where('extension_id = ' . $eid);
		$db->setQuery($query);
		$version = $db->loadResult();

		if (!$version)
		{
			// No schema version found. Fix it.
			$o = (object)array(
				'version_id'	=> '0.0.1-2007-08-15',
				'extension_id'	=> $eid,
			);
			$db->insertObject('#__schemas', $o);
		}
	}

	/**
	 * Let's say that a user tries to install a component and it somehow fails
	 * in a non-graceful manner, e.g. a server timeout error, going over the
	 * quota etc. In this case the component's administrator directory is
	 * created and not removed (because the installer died an untimely death).
	 * When the user retries installing the component JInstaller sees that and
	 * thinks it's an update. This causes it to neither run the installation SQL
	 * file (because it's not supposed to run on extension update) nor the
	 * update files (because there is no schema version defined). As a result
	 * the files are installed, the database tables are not, the component is
	 * broken and I have to explain to non-technical users how to edit their
	 * database with phpMyAdmin.
	 *
	 * This method detects this stupid situation and attempts to execute the
	 * installation file instead.
	 */
	private function _fixBrokenSQLUpdates($parent)
	{
		// Get the extension ID
		$db = JFactory::getDbo();

		$query = $db->getQuery(true);
		$query->select('extension_id')
			->from('#__extensions')
			->where($db->qn('element').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($query);
		$eid = $db->loadResult();

		if (!$eid)
		{
			return;
		}

		// Get the schema version
		$query = $db->getQuery(true);
		$query->select('version_id')
			->from('#__schemas')
			->where('extension_id = ' . $eid);
		$db->setQuery($query);
		$version = $db->loadResult();

		// If there is a schema version it's not a false update
		if ($version)
		{
			return;
		}

		// Execute the installation SQL file. Since I don't have access to
		// the manifest, I will improvise (again!)
		$dbDriver = strtolower($db->name);

		if ($dbDriver == 'mysqli')
		{
			$dbDriver = 'mysql';
		}
		elseif($dbDriver == 'sqlsrv')
		{
			$dbDriver = 'sqlazure';
		}

		// Get the name of the sql file to process
		$sqlfile = $parent->getParent()->getPath('source') . '/backend/sql/install/' . $dbDriver . '/install.sql';
		if (file_exists($sqlfile))
		{
			$buffer = file_get_contents($sqlfile);
			if ($buffer === false)
			{
				return;
			}

			$queries = JInstallerHelper::splitSql($buffer);

			if (count($queries) == 0)
			{
				// No queries to process
				return;
			}

			// Process each query in the $queries array (split out of sql file).
			foreach ($queries as $query)
			{
				$query = trim($query);

				if ($query != '' && $query{0} != '#')
				{
					$db->setQuery($query);

					if (!$db->execute())
					{
						JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));

						return false;
					}
				}
			}
		}

		// Update #__schemas to the latest version. Again, since I don't have
		// access to the manifest I have to improvise...
		$path = $parent->getParent()->getPath('source') . '/backend/sql/update/' . $dbDriver;
		$files = str_replace('.sql', '', JFolder::files($path, '\.sql$'));
		if(count($files) > 0)
		{
			usort($files, 'version_compare');
			$version = array_pop($files);
		}
		else
		{
			$version = '0.0.1-2007-08-15';
		}

		$query = $db->getQuery(true);
		$query->insert($db->quoteName('#__schemas'));
		$query->columns(array($db->quoteName('extension_id'), $db->quoteName('version_id')));
		$query->values($eid . ', ' . $db->quote($version));
		$db->setQuery($query);
		$db->execute();
	}

	/**
	 * Deletes the Live Update information, forcing its reload during the first
	 * run of the component. This makes sure that the Live Update doesn't show
	 * an update available right after installing the component.
	 */
	private function _resetLiveUpdate()
	{
		// Load the component parameters, not using JComponentHelper to avoid conflicts ;)
		JLoader::import('joomla.html.parameter');
		JLoader::import('joomla.application.component.helper');
		$db = JFactory::getDbo();
		$sql = $db->getQuery(true)
			->select($db->qn('params'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q($this->_akeeba_extension));
		$db->setQuery($sql);
		$rawparams = $db->loadResult();
		$params = new JRegistry();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$params->loadString($rawparams, 'JSON');
		} else {
			$params->loadJSON($rawparams);
		}

		// Reset the liveupdate key
		$params->set('liveupdate', null);

		// Save the modified component parameters
		$data = $params->toString();
		$sql = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params').' = '.$db->q($data))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q($this->_akeeba_extension));

		$db->setQuery($sql);
		$db->execute();
	}
}