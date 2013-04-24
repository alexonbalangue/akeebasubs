<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsMijoshop extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'mijoshop';

		parent::__construct($subject, $name, $config, $templatePath);
	}

	public function onAKUserRefresh($user_id)
	{
		$mijoshop = JPATH_ROOT.'/components/com_mijoshop/mijoshop/mijoshop.php';
		if (!file_exists($mijoshop)) {
			return;
		}

		require_once($mijoshop);

		// Load groups
		$addGroups = array();
		$removeGroups = array();
		$this->loadUserGroups($user_id, $addGroups, $removeGroups);
		if(empty($addGroups) && empty($removeGroups)) return;

		// Get DB connection
		$db = JFactory::getDBO();

		$customer_id = MijoShop::get('user')->getOCustomerIdFromJUser($user_id);

		// Remove from MijoShop
		if(!empty($removeGroups)) {
			$protoQuery = $db->getQuery(true)
				->update($db->qn('#__mijoshop_customer'))
				->set($db->qn('customer_group_id').' = '.$db->q('1'))
				->where($db->qn('customer_id').' = '.$db->q($customer_id));

			foreach($removeGroups as $group) {
				$query = clone $protoQuery;
				$query->where($db->qn('customer_group_id').' = '.$db->q($group));
				$db->setQuery($query);
				$db->execute();
			}
		}

		// Add to MijoShop
		if(!empty($addGroups)) {
			$group = array_pop($addGroups);

			$query = $db->getQuery(true)
				->update($db->qn('#__mijoshop_customer'))
				->set($db->qn('customer_group_id').' = '.$db->q($group))
				->where($db->qn('customer_id').' = '.$db->q($customer_id));
			$db->setQuery($query);
			$db->execute();
		}
	}

	protected function getGroups()
	{
		static $groups = null;

		if(is_null($groups)) {
			$groups = array();

			$mijoshop = JPATH_ROOT.'/components/com_mijoshop/mijoshop/mijoshop.php';
			if (!file_exists($mijoshop)) {
				return $groups;
			}

			require_once($mijoshop);

			$language_id = (int) MijoShop::get('opencart')->get('config')->get('config_language_id');

			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->select(array(
					$db->qn('name').' AS '.$db->qn('title'),
					$db->qn('customer_group_id').' AS '.$db->qn('id'),
				))->from($db->qn('#__mijoshop_customer_group_description'))
				->where($db->qn('language_id') . ' = ' . $db->q($language_id));
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