<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsConstantcontact extends plgAkeebasubsAbstract
{
	private $ccContact = null;
	private $ccList = null;
	private $emailType = 'HTML';
	
	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'constantcontact';
		
		parent::__construct($subject, $name, $config, $templatePath);
		
		// Load CC library and create CC object
		require_once dirname(__FILE__).'/library/cc_class.php';
		$configParams = @json_decode($config['params']);
		$user = trim($configParams->user);
		$password = trim($configParams->password);
		$key = trim($configParams->key);
		$this->ccContact = new CC_Contact($user, $password, $key);
		$this->ccList = new CC_List($user, $password, $key);
		$this->emailType = $configParams->email_type;
	}
	
	public function onAKUserRefresh($user_id)
	{
		// Load groups
		$addLists = array();
		$removeLists = array();
		$this->loadUserGroups($user_id, $addLists, $removeLists);
		if(empty($addLists) && empty($removeLists)) return;
		
		// Get the user
		$user = JUser::getInstance($user_id);
		
		if(! $this->ccContact->subscriberExists(urlencode($user->email))) {
			// Create new CC user if it doesn't exist and if we have lists to add
			if(!empty($addLists)) {
				$nameParts = explode(' ', trim($user->name), 2);
				$firstName = $nameParts[0];
				if(count($nameParts) > 1) {
					$lastName = $nameParts[1];
				} else {
					$lastName = '';
				}
				$newUserData = array();
				$newUserData['email_address'] = $user->email;
				$newUserData['first_name'] = $firstName;
				$newUserData['last_name'] = $lastName;
				$newUserData['mail_type'] = $this->emailType;
				$newUserData['lists'] = array();
				foreach($addLists as $listIdToAdd) {
					$newUserData['lists'][] = $this->getCCList($listIdToAdd);
				}
				$newUserXml = $this->ccContact->createContactXML('', $newUserData);
				$this->ccContact->addSubscriber($newUserXml);
			}
		} else {
			// Load existing CC user
			$userCCDetails = $this->ccContact->getSubscriberDetails(urlencode($user->email));
			$currentLists = $userCCDetails['lists'];
			$userCCId = (String)$userCCDetails['id'];
			$userCCUrl = urldecode($userCCId);

			// Remove from CC list
			if(!empty($removeLists)) {
				foreach($removeLists as $listIdToRemove) {
					foreach($currentLists as $key => $currentList) {
						if($this->endsWith('/' . $listIdToRemove, $currentList)) {
							unset($currentLists[$key]);
						}
					}
					// Save user/subscriber with new lists
					$userCCDetails['lists'] = $currentLists;
					$userCCXml = $this->ccContact->createContactXML($userCCId, $userCCDetails);
					$this->ccContact->editSubscriber($userCCUrl, $userCCXml);
				}
			}

			// Add to CC list
			if(!empty($addLists)) {
				foreach($addLists as $listIdToAdd) {
					$needsToBeAdded = true;
					foreach($currentLists as $currentList) {
						if($this->endsWith('/' . $listIdToAdd, $currentList)) {
							$needsToBeAdded = false;
							break;
						}
					}
					if($needsToBeAdded) {
						$currentLists[] = $this->getCCList($listIdToAdd);
					}
				}
				// Save user/subscriber with new lists
				$userCCDetails['lists'] = $currentLists;
				$userCCXml = $this->ccContact->createContactXML($userCCId, $userCCDetails);
				$this->ccContact->editSubscriber($userCCUrl, $userCCXml);
			}
		}
	}

	protected function getGroups()
	{
		static $groups = null;
		
		if(is_null($groups)) {
			$groups = array();
			$lists = $this->ccList->getLists();
			foreach($lists as $list) {
				$link = $list['id'];
				$matches = array();
				$pattern = '/([0-9]+$)/';
				preg_match($pattern, $link, $matches);
				if(isset($matches[1])) {
					$groups[$list['title']] = $matches[1];
				}
			}
		}
		
		return $groups;
	}
	
	private function getCCList($id)
	{
		foreach($this->ccList->getLists() as $list) {
			if($this->endsWith('/' . $id, $list['id'])) {
				return $list['id'];
			}
		}
		return null;
	}
	
	private function endsWith($needle, $haystack)
	{
		return preg_match('/' . preg_quote($needle, '/') . '$/', $haystack);
	}
}