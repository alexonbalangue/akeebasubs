<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

// This stupid crap is required due to some stupid Joomla! naming conflicts. Bleh...
if(!class_exists('JTableUser')) {
	require_once (version_compare(JVERSION, '1.6.0', 'ge') ? JPATH_LIBRARIES : JPATH_ROOT.'/libraries').'/joomla/database/table/user.php';
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
			jimport('joomla.plugin.helper');
			JPluginHelper::importPlugin('akeebasubs');
			$app = JFactory::getApplication();
			$jResponse = $app->triggerEvent('onAKUserSaveData', array($this));
		}
		
		return $result;
	}
}