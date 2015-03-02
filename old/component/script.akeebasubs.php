<?php
/**
 * @package      akeebasubs
 * @copyright    Copyright (c)2010-2015 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license      GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 * @version      $Id$
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

// Load FOF if not already loaded
if (!defined('F0F_INCLUDED'))
{
	$paths = array(
		(defined('JPATH_LIBRARIES') ? JPATH_LIBRARIES : JPATH_ROOT . '/libraries') . '/f0f/include.php',
		__DIR__ . '/fof/include.php',
	);

	foreach ($paths as $filePath)
	{
		if (!defined('F0F_INCLUDED') && file_exists($filePath))
		{
			@include_once $filePath;
		}
	}
}

// Pre-load the installer script class from our own copy of FOF
if (!class_exists('F0FUtilsInstallscript', false))
{
	@include_once __DIR__ . '/fof/utils/installscript/installscript.php';
}

// Pre-load the database schema installer class from our own copy of FOF
if (!class_exists('F0FDatabaseInstaller', false))
{
	@include_once __DIR__ . '/fof/database/installer.php';
}

// Pre-load the update utility class from our own copy of FOF
if (!class_exists('F0FUtilsUpdate', false))
{
	@include_once __DIR__ . '/fof/utils/update/update.php';
}

// Pre-load the cache cleaner utility class from our own copy of FOF
if (!class_exists('F0FUtilsCacheCleaner', false))
{
	@include_once __DIR__ . '/fof/utils/cache/cleaner.php';
}

class Com_AkeebasubsInstallerScript extends F0FUtilsInstallscript
{
	/**
	 * The component's name
	 *
	 * @var   string
	 */
	protected $componentName = 'com_akeebasubs';

	/**
	 * The title of the component (printed on installation and uninstallation messages)
	 *
	 * @var string
	 */
	protected $componentTitle = 'Akeeba Subscriptions';

	/**
	 * The minimum PHP version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumPHPVersion = '5.3.4';

	/**
	 * The minimum Joomla! version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumJoomlaVersion = '3.2.1';

	/**
	 * The list of extra modules and plugins to install on component installation / update and remove on component
	 * uninstallation.
	 *
	 * @var   array
	 */
	protected $installation_queue = array(
		// modules => { (folder) => { (module) => { (position), (published) } }* }*
		'modules' => array(
			'admin' => array(
				'akeebasubs' => array('cpanel', 1)
			),
			'site'  => array(
				'aksexpires' => array('left', 0),
				'akslevels'  => array('left', 0),
				'aksubslist' => array('left', 0),
				'aktaxcountry' => array('akeebasubscriptionslistheader', 0),
			)
		),
		// plugins => { (folder) => { (element) => (published) }* }*
		'plugins' => array(
			'akeebasubs'         => array(
				'adminemails'             => 0,
				'agreetoeu'               => 0,
				'agreetotos'              => 0,
				'atscredits'              => 0,
				'autocity'                => 0,
				'canalyticscommerce'      => 0,
				'contentpublish'          => 0,
				'customfields'            => 1,
				'invoices'                => 0,
				'iproperty'               => 0,
				'joomla'                  => 1,
				'joomlaprofilesync'       => 1,
				'needslogout'             => 1,
				'recaptcha'               => 0,
				'slavesubs'               => 1,
				'sql'                     => 0,
				'subscriptionemails'      => 1,
				'subscriptionemailsdebug' => 0,
			),
			'akpayment'          => array(
				'2checkout'         => 0,
				'2conew'            => 0,
				'none'              => 0,
				'offline'           => 0,
				'paymill'           => 0,
				'paypal'            => 1,
				'paypalpaymentspro' => 0,
				'paypalproexpress'  => 0,
				'skrill'            => 0,
				'stripe'            => 0,
				'viva'              => 0,
			),
			'content'            => array(
				'aslink'         => 1,
				'asrestricted'   => 1,
				'astimedrelease' => 1,
			),
			'sh404sefextplugins' => array(
				'com_akeebasubs' => 1
			),
			'system'             => array(
				'as2cocollation'      => 0,
				'asexpirationcontrol' => 1,
				'asexpirationnotify'  => 1,
				'aslogoutuser'        => 0,
				'asuserregredir'      => 0,
			),
			'user'               => array(
				'aslogoutuser' => 1,
			),
		)
	);

	/**
	 * Obsolete files and folders to remove from both paid and free releases. This is used when you refactor code and
	 * some files inevitably become obsolete and need to be removed.
	 *
	 * @var   array
	 */
	protected $removeFilesAllVersions = array(
		'files'   => array(
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

			// Affiliates feature
			'administrator/components/com_akeebasubs/fields/akeebasubsaffiliatesowed.php',
			'administrator/components/com_akeebasubs/models/affiliates.php',
			'administrator/components/com_akeebasubs/models/affpayments.php',
			'administrator/components/com_akeebasubs/tables/affiliate.php',
			'administrator/components/com_akeebasubs/tables/affpayment.php',

			// Import data
			'administrator/components/com_akeebasubs/controllers/tools.php',
			'administrator/components/com_akeebasubs/models/tools.php',

			// Joomla! 2.5 stuff
			'administrator/components/com_akeebasubs/views/coupons/tmpl/form.default.j3.xml',
			'administrator/components/com_akeebasubs/views/customfields/tmpl/form.default.j3.xml',
			'administrator/components/com_akeebasubs/views/emailtemplates/tmpl/form.default.j3.xml',
			'administrator/components/com_akeebasubs/views/invoicetemplates/tmpl/form.default.j3.xml',
			'administrator/components/com_akeebasubs/views/levels/tmpl/form.default.j3.xml',
			'administrator/components/com_akeebasubs/views/relations/tmpl/form.default.j3.xml',
			'administrator/components/com_akeebasubs/views/upgrades/tmpl/form.default.j3.xml',
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

			// Affiliates feature
			'administrator/components/com_akeebasubs/views/affiliate',
			'administrator/components/com_akeebasubs/views/affiliates',
			'administrator/components/com_akeebasubs/views/affpayment',
			'administrator/components/com_akeebasubs/views/affpayments',

			// Import data
			'administrator/components/com_akeebasubs/converter',
			'administrator/components/com_akeebasubs/views/tools',
		)
	);

	/**
	 * A list of scripts to be copied to the "cli" directory of the site
	 *
	 * @var   array
	 */
	protected $cliScriptFiles = array(
		'akeebasubs-expiration-notify.php',
		'akeebasubs-update.php',
	);

	/**
	 * Renders the post-installation message
	 */
	protected function renderPostInstallation($status, $fofInstallationStatus, $strapperInstallationStatus, $parent)
	{
		$this->warnAboutJSNPowerAdmin();

		?>
		<h1>Akeeba Subscriptions</h1>

		<img src="../media/com_akeebasubs/images/akeebasubs-48.png" width="48" height="48" alt="Akeeba Subscriptions"
			 align="left"/>
		<h2 style="font-size: 14pt; font-weight: black; padding: 0; margin: 0 0 0.5em;">Welcome to Akeeba
			Subscriptions!</h2>
		<span>The easiest way to sell subscriptions on your Joomla! site</span>

		<div style="margin: 1em; font-size: 14pt; background-color: #fffff9; color: black">
			You can download translation files <a href="http://cdn.akeebabackup.com/language/akeebasubs/index.html">directly
				from our CDN page</a>.
		</div>

		<?php
		parent::renderPostInstallation($status, $fofInstallationStatus, $strapperInstallationStatus, $parent);
	}

	protected function renderPostUninstallation($status, $parent)
	{
		?>
		<h2 style="font-size: 14pt; font-weight: black; padding: 0; margin: 0 0 0.5em;">&nbsp;Akeeba Subscriptions
			Uninstallation</h2>
		<p>We are sorry that you decided to uninstall Akeeba Subscriptions. Please let us know why by using the Contact
			Us form on our site. We appreciate your feedback; it helps us develop better software!</p>

		<?php
		parent::renderPostUninstallation($status, $parent);
	}


	/**
	 * The PowerAdmin extension makes menu items disappear. People assume it's our fault. JSN PowerAdmin authors don't
	 * own up to their software's issue. I have no choice but to warn our users about the faulty third party software.
	 */
	private function warnAboutJSNPowerAdmin()
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('component'))
			->where($db->qn('element') . ' = ' . $db->q('com_poweradmin'))
			->where($db->qn('enabled') . ' = ' . $db->q('1'));
		$hasPowerAdmin = $db->setQuery($query)->loadResult();

		if (!$hasPowerAdmin)
		{
			return;
		}

		echo <<< HTML
<div class="well" style="margin: 2em 0;">
<h1 style="font-size: 32pt; line-height: 120%; color: red; margin-bottom: 1em">WARNING: Menu items for {$this->componentName} might not be displayed on your site.</h1>
<p style="font-size: 18pt; line-height: 150%; margin-bottom: 1.5em">
	We have detected that you are using JSN PowerAdmin on your site. This software ignores Joomla! standards and
	<b>hides</b> the Component menu items to {$this->componentName} in the administrator backend of your site. Unfortunately we
	can't provide support for third party software. Please contact the developers of JSN PowerAdmin for support
	regarding this issue.
</p>
<p style="font-size: 18pt; line-height: 120%; color: green;">
	Tip: You can disable JSN PowerAdmin to see the menu items to Akeeba Backup.
</p>
</div>

HTML;

	}

}