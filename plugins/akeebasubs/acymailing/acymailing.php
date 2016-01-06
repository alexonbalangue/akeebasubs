<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Model\Levels;
use Akeeba\Subscriptions\Admin\Model\Subscriptions;
use Akeeba\Subscriptions\Admin\Model\Users;
use FOF30\Container\Container;

Container::getInstance('com_akeebasubs'); // This sets up the autoloader (if it's not already loaded)

class plgAkeebasubsAcymailing extends Akeeba\Subscriptions\Admin\PluginAbstracts\AkeebasubsBase
{
	/**
	 * Public constructor
	 *
	 * @param object $subject
	 * @param array  $config
	 */
	public function __construct(& $subject, $config = array())
	{
		$config['templatePath'] = dirname(__FILE__);
		$config['name']         = 'acymailing';

		parent::__construct($subject, $config);
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param   int $user_id The Joomla! user ID to refresh information for.
	 *
	 * @return  void
	 */
	public function onAKUserRefresh($user_id)
	{
		// No AcyMailing API? Stop here!
		if(!include_once(JPATH_ADMINISTRATOR.'/components/com_acymailing/helpers/helper.php'))
		{
			return;
		}

		// Load groups
		$addGroups       = array();
		$removeGroups    = array();
		$newSubscription = array();

		$this->loadUserGroups($user_id, $addGroups, $removeGroups);

		if(empty($addGroups) && empty($removeGroups))
		{
			return;
		}

		$userClass = acymailing_get('class.subscriber');

		if(!empty($addGroups))
		{
			foreach($addGroups as $listId)
			{
				$newList = array();
				$newList['status'] = 1;
				$newSubscription[$listId] = $newList;
			}
		}

		if(!empty($removeGroups))
		{
			foreach($removeGroups as $listId)
			{
				$newList = array();
				$newList['status'] = 0;
				$newSubscription[$listId] = $newList;
			}
		}

		if(empty($newSubscription))
		{
			return;
		}

		// this function returns the ID of the user stored in the AcyMailing table from a Joomla User ID
		// or an e-mail address
		$subid = $userClass->subid($user_id);

		//we didn't find the user in the AcyMailing tables
		if(empty($subid))
		{
			return;
		}

		$userClass->saveSubscription($subid,$newSubscription);
	}

	protected function getGroups()
	{
		static $groups = null;

		if(is_null($groups)) {
			$groups = array();

			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->select(array(
					$db->qn('listid').' AS '.$db->qn('id'),
					$db->qn('name').' AS '.$db->qn('title'),
				))->from($db->qn('#__acymailing_list'))
			;
			$db->setQuery($query);
			$res = $db->loadObjectList();

			if(!empty($res)) {
				foreach($res as $item) {
					$t = trim($item->title);
					$groups[$t] = $item->id;
				}
			}
		}

		return $groups;
	}

	/**
	 * Used by the template to render selection fields
	 *
	 * @param   \Akeeba\Subscriptions\Admin\Model\Levels  $level  The subscription level
	 * @param   string                                    $type   add or remove
	 *
	 * @return  string  The HTML for the drop-down field
	 */
	protected function getSelectField(\Akeeba\Subscriptions\Admin\Model\Levels $level, $type)
	{
		if (!in_array($type, ['add', 'remove']))
		{
			return '';
		}

		$key = "acymailing_{$type}groups";
		$name = "params[$key][]";

		if (isset($level->params[$key]))
		{
			$groupList = $level->params[$key];
		}
		else
		{
			$groupList = array();
		}

		$allGroups = $this->getGroups();

		foreach ($allGroups as $key => $group)
		{
			$options[] = JHtml::_('select.option', $group, $key);
		}

		return JHtml::_('select.genericlist', $options, $name, array(
			'multiple' => 'multiple',
			'size'     => 8,
			'class'    => 'input-large'
		), 'value', 'text', $groupList, false);

	}
}
