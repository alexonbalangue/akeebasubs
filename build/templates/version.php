<?php
defined('KOOWA') or die();

define('AKEEBASUBS_VERSION', '##VERSION##');
define('AKEEBASUBS_DATE', '##DATE##');
define('AKEEBASUBS_PRO', '##PRO##');
define('AKEEBASUBS_VERSIONHASH', md5(AKEEBASUBS_VERSION.AKEEBASUBS_DATE.JFactory::getConfig()->getValue('secret','')));
?>