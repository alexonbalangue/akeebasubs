<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

// Include the component versioning
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/version.php';

// Include FOF
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/fof/include.php';

// Dispatch
FOFDispatcher::getAnInstance('com_akeebasubs')->dispatch();