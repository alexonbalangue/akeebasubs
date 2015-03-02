<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

// Include the component versioning
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/version.php';

// Include F0F
include_once JPATH_LIBRARIES.'/f0f/include.php';
if(!defined('F0F_INCLUDED')): ?>
<h1>Akeeba Subscriptions</h1>
<h2>Incomplete installation detected</h2>
<p>Please visit your site's back-end and click on Components, Akeeba Subscriptions
for further information.</p>
<?php return; endif;

if(version_compare(phpversion(), '5.3.0', 'lt')) {
?><h1>Akeeba Subscriptions</h1>
<h2>Incompatible PHP version</h2>
<p>Please visit your site's back-end and click on Components, Akeeba Subscriptions
for further information.</p>
<?php return; }

// Dispatch
F0FDispatcher::getTmpInstance('com_akeebasubs')->dispatch();