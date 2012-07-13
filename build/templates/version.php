<?php
defined('_JEXEC') or die();

define('AKEEBASUBS_VERSION', '##VERSION##');
define('AKEEBASUBS_DATE', '##DATE##');
define('AKEEBASUBS_PRO', '##PRO##');
if(version_compare(JVERSION, '3.0', 'ge')) {
	define('AKEEBASUBS_VERSIONHASH', md5(AKEEBASUBS_VERSION.AKEEBASUBS_DATE.JFactory::getConfig()->get('secret','')));
} else {
	define('AKEEBASUBS_VERSIONHASH', md5(AKEEBASUBS_VERSION.AKEEBASUBS_DATE.JFactory::getConfig()->getValue('secret','')));
}
?>