<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
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
		// Load groups
		$addGroups = array();
		$removeGroups = array();
		$this->loadUserGroups($user_id, $addGroups, $removeGroups);
		if(empty($addGroups) && empty($removeGroups)) return;
		
		// Get DB connection
		$db = JFactory::getDBO();
		
		// Does this user have an AcyMailing user record?
		$query = $db->getQuery(true)
			->select($db->qn('subid'))
			->from('#__acymailing_subscriber')
			->where($db->qn('userid').' = '.$db->q($user_id));
		$db->setQuery($query);
		$amsubid = $db->loadResult();
		
		if(empty($amsubid) || is_null($amsubid)) {
			// Create new AcyMailing subscriber record
			$user = JFactory::getUser($user_id);
			$amsubobject = (object)array(
				'email'		=> $user->email,
				'userid'	=> $user->id,
				'name'		=> $user->name,
				'created'	=> time(),
				'confirmed'	=> 1,
				'enabled'	=> 1,
				'accept'	=> 1,
				'html'		=> 1,
			);
			$db->insertObject('#__acymailing_subscriber', $amsubobject);
			$amsubid = $db->insertid();
		}
		
		// Add to AcyMailing
		if(!empty($addGroups)) {
			foreach($addGroups as $group) {
				// Check for old record
				$query = $db->getQuery(true)
					->select($db->qn('subid'))
					->from($db->qn('#__acymailing_listsub'))
					->where($db->qn('listid') .'='. $db->q($group))
					->where($db->qn('subid') .'='. $db->q($amsubid));
				$db->setQuery($query);
				$test = $db->loadResult();
				
				// Remove old record
				if(!empty($test) && !is_null($test)) {
					$query = $db->getQuery(true)
						->delete($db->qn('#__acymailing_listsub'))
						->where($db->qn('listid') .'='. $db->q($group))
						->where($db->qn('subid') .'='. $db->q($amsubid));
					$db->setQuery($query);
					$db->execute();
				}
				
				// Insert new record
				$query = $db->getQuery(true)
					->insert($db->qn('#__acymailing_listsub'))
					->values(
						$db->q($group).', '.$db->q($amsubid).', '.
						$db->q(time()).', '.$db->q(null).', '.
						$db->q(1)
					)->columns(array(
						$db->qn('listid'), $db->qn('subid'),
						$db->qn('subdate'), $db->qn('unsubdate'),
						$db->qn('status'),
					));
				;
				$db->setQuery($query);
				$db->execute();
			}
		}
		
		// Remove from AcyMailing
		if(!empty($removeGroups)) {
			foreach($removeGroups as $group) {
				$query = $db->getQuery(true)
					->delete($db->qn('#__acymailing_listsub'))
					->where($db->qn('listid') .'='. $db->q($group))
					->where($db->qn('subid') .'='. $db->q($amsubid));
				$db->setQuery($query);
				$db->execute();
			}
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