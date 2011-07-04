<?php

defined('_JEXEC') or die('');

class Com_AkeebasubsInstallerScript {
	function postflight($type, $parent) {
		define('_AKEEBA_HACK', 1);
		require_once('install.akeebasubs.php');
	}
	
	/*
	 * Joomla! 1.6 doesn't run the SQL commands on update. Workaround dully applied!
	 */
	function update($parent) {
		$db = JFactory::getDBO();
		if(method_exists($parent, 'extension_root')) {
			$sqlfile = $parent->getPath('extension_root').DS.'install'.DS.'install.sql';
		} else {
			$sqlfile = $parent->getParent()->getPath('extension_root').DS.'install'.DS.'install.sql';
		}
		$buffer = file_get_contents($sqlfile);
		if ($buffer !== false) {
			jimport('joomla.installer.helper');
			$queries = JInstallerHelper::splitSql($buffer);
			if (count($queries) != 0) {
				foreach ($queries as $query)
				{
					$query = trim($query);
					if ($query != '' && $query{0} != '#') {
						$db->setQuery($query);
						if (!$db->query()) {
							JError::raiseWarning(1, JText::sprintf('JLIB_INSTALLER_ERROR_SQL_ERROR', $db->stderr(true)));
							return false;
						}
					}
				}
			}
		}
	}
}