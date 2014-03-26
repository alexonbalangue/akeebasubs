<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
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
			$dispatcher = JEventDispatcher::getInstance();
			$jResponse = $dispatcher->trigger('onAKUserSaveData', array($this));

			if (in_array(false, $jResponse))
			{
				$this->setError($dispatcher->getError());

				return false;
			}
		}
		
		return $result;
	}
}
