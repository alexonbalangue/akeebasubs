<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * Adds a subscriptionRefresh() method to subscription rows, allowing the component
 * to call onAKUserRefresh for all users with subscriptions, re-applying all
 * integrations with third party components.
 *
 * @author nicholas
 */
class ComAkeebasubsDatabaseBehaviorSubrefresh extends KDatabaseBehaviorAbstract
{
	protected $_cache = array();

	public function __construct( KConfig $config = null)
	{
		parent::__construct($config);
	
		// Load the "akeebasubs" plugins
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');	
	}

	public function subscriptionRefresh()
	{
		$user_id = $this->mixer->user_id;

		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKUserRefresh', array($user_id));
		
		return true;
	}	
}