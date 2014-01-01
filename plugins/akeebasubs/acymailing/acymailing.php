<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2014 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsAcymailing extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'acymailing';

		parent::__construct($subject, $name, $config, $templatePath);
	}

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
}