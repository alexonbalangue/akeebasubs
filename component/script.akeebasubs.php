<?php

defined('_JEXEC') or die('');

class Com_AkeebasubsInstallerScript {
	function postflight($type, $parent) {
		define('_AKEEBA_HACK', 1);
		require_once('install.akeebasubs.php');
	}
}