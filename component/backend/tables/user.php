<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

// This stupid crap is required due to some stupid Joomla! naming conflicts. Bleh...
if(!class_exists('JTableUser')) {
	require_once JPATH_LIBRARIES.'/joomla/database/table/user.php';
}

class AkeebasubsTableUser extends FOFTable
{
	/**
	 * Run the onAKUserSaveData event on the plugins before saving a row
	 * 
	 * @param boolean $updateNulls
	 * @return bool
	 */
	function onBeforeStore($updateNulls) {
		if($result = parent::onBeforeStore($updateNulls)) {
			JLoader::import('joomla.plugin.helper');
			JPluginHelper::importPlugin('akeebasubs');
			$app = JFactory::getApplication();
			$jResponse = $app->triggerEvent('onAKUserSaveData', array($this));
		}
		
		return $result;
	}
}