<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerCpanels extends FOFController
{
	public function execute($task) {
		$task = 'browse';
		parent::execute($task);
	}
	
	protected function onBeforeBrowse() {
		$result = parent::onBeforeBrowse();
		
		if($result) {
			$db = JFactory::getDbo();
			$columns = $db->getTableColumns('#__akeebasubs_levels', true);
			if(!array_key_exists('akeebasubs_levelgroup_id', $columns)) {
				$sql = JFile::read(JPATH_ADMINISTRATOR.'/components/com_akeebasubs/sql/updates/mysql/2.3.0-2012-06-15.sql');
				if($sql) {
					$commands = explode(';', $sql);
					foreach($commands as $query) {
						$db->setQuery($query);
						$db->query();
					}
				}
			}
		}
		
		return $result;
	}
}