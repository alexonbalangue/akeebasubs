<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

// Include the component versioning
require_once JPATH_COMPONENT_ADMINISTRATOR.'/version.php';

// Include FOF
include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/fof/include.php';
if(!defined('FOF_INCLUDED')) {?>
<h1>Akeeba Subscriptions</h1>
<h2>Incomplete installation detected</h2>
<p>
	Akeeba Subscriptions can not load because an incomplete installation was
	detected. In order to fix this problem, please follow these steps:
</p>
<ol>
	<li>
		Download Akeeba Subscriptions' installation ZIP package from our
		<a href="https://www.akeebabackup.com/download/official/akeeba-subscriptions.html">Downloads page</a>.
	</li>
	<li>
		Go to <a href="<?php echo JURI::base() ?>index.php?option=com_installer">Extensions &gt;
		<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? "Install/Uninstall" : "Manage"; ?>
		</a>, click on &quot;Browse...&quot;, find the ZIP file you downloaded and double click on it.
	</li>
	<li>
		Click on &quot;Upload &amp; Install&quot;
	</li>
</ol>
<p>
	This will install all of the missing files while preserving your existing
	settings and subscriptions.
</p>
<p>
	<strong>IMPORTANT!</strong> Do not uninstall the component before following
	the procedure above. When you uninstall the component, all of your existing
	settings and all of the subscriptions will be <em>removed</em>.
</p>
<?php return; }

// Dispatch
FOFDispatcher::getTmpInstance('com_akeebasubs')->dispatch();