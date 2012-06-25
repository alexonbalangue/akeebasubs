<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

// Include the component versioning
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/version.php';

// Include FOF
include_once JPATH_LIBRARIES.'/fof/include.php';
if(!defined('FOF_INCLUDED')): ?>
<h1>Akeeba Subscriptions</h1>
<h2>Incomplete installation detected</h2>
<p>Please visit your site's back-end and click on Components, Akeeba Subscriptions
for further information.</p>
<?php endif;

// Dispatch
FOFDispatcher::getTmpInstance('com_akeebasubs')->dispatch();