<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsAceshop extends plgAkeebasubsAbstract
{
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'aceshop';
		
		parent::__construct($subject, $name, $config, $templatePath);
	}

	public function onAKUserRefresh($user_id)
	{
		$aceshop = JPATH_ROOT.'/components/com_aceshop/aceshop/aceshop.php';
		if (!file_exists($aceshop)) {
			return;
		}
		
		require_once($aceshop);
		
		// Load groups
		$addGroups = array();
		$removeGroups = array();
		$this->loadUserGroups($user_id, $addGroups, $removeGroups);
		if(empty($addGroups) && empty($removeGroups)) return;
		
		// Get DB connection
		$db = JFactory::getDBO();
		
		$customer_id = AceShop::get('user')->getOCustomerIdFromJUser($user_id);
		
		// Remove from AceShop
		if(!empty($removeGroups)) {
			$protoQuery = $db->getQuery(true)
				->update($db->qn('#__aceshop_customer'))
				->set($db->qn('customer_group_id').' = '.$db->q('1'))
				->where($db->qn('customer_id').' = '.$db->q($customer_id));
				
			foreach($removeGroups as $group) {
				$query = clone $protoQuery;
				$query->where($db->qn('customer_group_id').' = '.$db->q($group));
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		// Add to AceShop
		if(!empty($addGroups)) {
			$group = array_pop($addGroups);

			$query = $db->getQuery(true)
				->update($db->qn('#__aceshop_customer'))
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
			
			$db = JFactory::getDBO();
			$query = $db->getQuery(true)
				->select(array(
					$db->qn('name').' AS '.$db->qn('title'),
					$db->qn('customer_group_id').' AS '.$db->qn('id'),
				))->from($db->qn('#__aceshop_customer_group'));
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