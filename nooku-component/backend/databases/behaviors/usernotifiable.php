<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * Unblocks the associated user account when a subscription is activated
 *
 * @author nicholas
 */
class ComAkeebasubsDatabaseBehaviorUsernotifiable extends KDatabaseBehaviorAbstract
{
	public function _beforeTableInsert(KCommandContext $context)
	{
		// Notify our plugins that we are saving user data
		if($context->data instanceof KDatabaseRowAbstract) {
			$rows = array($context->data);
		} else {
			if(!($context->data instanceof KDatabaseRowsetAbstract)) return true;
			$rows = $context->data;
		}
		
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		foreach($rows as $row)
		{
			$jResponse = $app->triggerEvent('onAKUserSaveData', array($row));
		}
		
		return true;
	}
	
	public function _beforeTableUpdate(KCommandContext $context)
	{
		return $this->_beforeTableInsert($context);
	}
}