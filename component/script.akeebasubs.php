<?php

class Com_AdmintoolsInstallerScript {
	function postflight($type, $parent) {
		define('_AKEEBA_HACK', 1);
		require_once('install.admintools.php');
	}
}