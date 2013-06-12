<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akeebasubsinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akeebasubs.php';
if(!$akeebasubsinclude) { unset($akeebasubsinclude); return; } else { unset($akeebasubsinclude); }

class plgAkeebasubsMailchimp extends plgAkeebasubsAbstract
{
	private $mcApi;
	private $delete_member = false;
	private $send_goodbye = true;
	private $send_notify = true;
	private $email_type = 'html';
	private $double_optin = true;
	private $send_welcome = false;
	protected $customFields = array();
	// MC groups
	protected $addMCGroups = array();
	protected $removeMCGroups = array();
	protected $groupingsGroupMap = array();
	protected $groupingsListMap = array();
	protected $groupingsGroupName = array();

	public function __construct(& $subject, $config = array())
	{
		$templatePath = dirname(__FILE__);
		$name = 'mailchimp';

		parent::__construct($subject, $name, $config, $templatePath);

		// Load the MailChimp library
		require_once dirname(__FILE__).'/library/MCAPI.class.php';

		$configParams = @json_decode($config['params']);
		$apiKey = $configParams->mailchimp_key;
		$this->mcApi = new MCAPI($apiKey);
		$this->delete_member = $configParams->delete_member;
		$this->send_goodbye = $configParams->send_goodbye;
		$this->send_notify = $configParams->send_notify;
		$this->email_type = $configParams->email_type;
		$this->double_optin = $configParams->double_optin;
		$this->send_welcome = $configParams->send_welcome;

		// Do we have values from the Olden Days?
		$strAddGroups = $configParams->addlists;
		$strRemoveGroups = $configParams->removelists;
		if(!empty($strAddGroups) || !empty($strAddGroups)) {
			// Load level to group mapping from plugin parameters
			$this->addGroups = $this->parseGroups($strAddGroups);
			$this->removeGroups = $this->parseGroups($strRemoveGroups);
			// Do a transparent upgrade
			$this->upgradeSettings($config);
		}

		// Load custom fields
		$this->loadCustomFieldsAssignments();

		// Load MC group assignments
		$this->loadMCGroupAssignments();
	}

	protected function loadMCGroupAssignments()
	{
		$this->addMCGroups = array();
		$this->removeMCGroups = array();

		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$addMCGroupsKey = strtolower($this->name).'_addmcgroups';
		$removeMCGroupsKey = strtolower($this->name).'_removemcgroups';
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					continue;
				}
				if(property_exists($level->params, $addMCGroupsKey))
				{
					$this->addMCGroups[$level->akeebasubs_level_id] = array_filter($level->params->$addMCGroupsKey);
				}
				if(property_exists($level->params, $removeMCGroupsKey))
				{
					$this->removeMCGroups[$level->akeebasubs_level_id] = array_filter($level->params->$removeMCGroupsKey);
				}
			}
		}
	}

	protected function loadCustomFieldsAssignments()
	{
		$this->customFields = array();

		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$customFieldsKey = strtolower($this->name).'_customfields';
		if(!empty($levels)) {
			foreach($levels as $level) {
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					continue;
				}
				if(property_exists($level->params, $customFieldsKey)) {
					$this->customFields[$level->akeebasubs_level_id] = array_filter($level->params->$customFieldsKey);
				}
			}
		}
	}

	public function onAKUserRefresh($user_id)
	{
		// Load lists
		$addMCLists = array();
		$removeMCLists = array();
		$this->loadUserGroups($user_id, $addMCLists, $removeMCLists);

		// Load groups
		$addMCGroups = array();
		$removeMCGroups = array();
		$this->loadUserGroups($user_id, $addMCGroups, $removeMCGroups, 'addMCGroups', 'removeMCGroups');
		$this->initMCGroups();

		// Find all custom fields to add
		foreach($this->addGroups as $level => $lists) {
			$customFields = $this->getCustomFields($level);
			foreach($customFields as $fieldTitle => $fieldId) {
				$customField = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
					->enabled(1)
					->setId($fieldId)
					->getItem();
				// Loop through the lists in order to check if custom field exists as merge tag
				foreach($lists as $list) {
					$currentMergeTags = $this->mcApi->listMergeVars($list);
					$mergeTagExists = false;
					$customFieldSlug = strtoupper($customField->slug);
					foreach($currentMergeTags as $currentMergeTag) {
						$tag = strtoupper($currentMergeTag['tag']);
						if($customFieldSlug == $tag) {
							$mergeTagExists = true;
							break;
						}
					}
					// Create new merge tag
					if(! $mergeTagExists) {
						$this->mcApi->listMergeVarAdd(
								$list,
								$customFieldSlug,
								$customField->title);
					}
				}
			}
		}

		// Get the user's name and email
		$user = JUser::getInstance($user_id);
		$nameParts = explode(' ', trim($user->name), 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		$email = $user->email;

		// Get the user's MailChimp lists
		$currentLists = $this->mcApi->listsForEmail($email);

		// Get the session
		$session = JFactory::getSession();

		// Remove from MailChimp list
		if(!empty($removeMCLists)) {
			foreach($removeMCLists as $mcListToRemove) {
				if(is_array($currentLists) && in_array($mcListToRemove, $currentLists)) {
					$mcSubscribeId = $user_id . ':' . $mcListToRemove;
					$this->mcApi->listUnsubscribe(
							$mcListToRemove,
							$email,
							$this->delete_member,
							$this->send_goodbye,
							$this->send_notify);
				}
				$mcSubscribeId = $user_id . ':' . $mcListToRemove;
				$session->clear('mailchimp.' . $mcSubscribeId, 'com_akeebasubs');
			}
		}

		// Add to MailChimp list
		if(!empty($addMCLists)) {
			// Get custom field values of last subscription
			$subs = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
				->user_id($user_id)
				->getList();
			$lastSubscription = $subs[0];
			$params = json_decode($lastSubscription->userparams);

			if(isset($this->customFields[$lastSubscription->akeebasubs_level_id]))
			{
				$customFieldsLastSub = $this->customFields[$lastSubscription->akeebasubs_level_id];
			}
			else
			{
				$customFieldsLastSub = array();
			}

			$subscriptionMergeVals = array();
			foreach($customFieldsLastSub as $customFieldId) {
				$customField = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
					->enabled(1)
					->setId($customFieldId)
					->getItem();
				$customFieldSlug = $customField->slug;
				$val = $params->$customFieldSlug;
				if(! empty($val)) {
					$subscriptionMergeVals[strtoupper($customFieldSlug)] = $val;
				}
			}

			// Add subscriber to lists
			foreach($addMCLists as $mcListToAdd) {
				if(! (is_array($currentLists) && in_array($mcListToAdd, $currentLists))) {
					$mcSubscribeId = $user_id . ':' . $mcListToAdd;
					if($session->get('mailchimp.' . $mcSubscribeId, '', 'com_akeebasubs') != 'new') {
						// Subscribe if email is not already in the MailChimp list and
						// if the subscription is not already sent for that user (but not confirmed yet)
						$mergeVals = array(
									'FNAME'		=> $firstName,
									'LNAME'		=> $lastName
									);

						// Add custom field values
						if(! empty($subscriptionMergeVals)) {
							$lists = $this->addGroups[$lastSubscription->akeebasubs_level_id];
							foreach($lists as $list) {
								if($list == $mcListToAdd) {
									$mergeVals = array_merge($mergeVals, $subscriptionMergeVals);
									break;
								}
							}
						}

						// Add MC groups to new subscription
						$groupings = array();
						if(!empty($addMCGroups)) {
							foreach($addMCGroups as $mcGroupId) {
								$groupName = str_replace(',', '\,', $this->groupingsGroupName[$mcGroupId]);
								// No group name
								if(empty($groupName)) continue;
								$groupingId = $this->groupingsGroupMap[$mcGroupId];
								// No correspnding grouping
								if(empty($groupingId)) continue;
								$listId = $this->groupingsListMap[$groupingId];
								// No correspnding list
								if(empty($listId)) continue;
								// Group not related to this list
								if($listId != $mcListToAdd) continue;
								// We passed all checks: Add the group to the array
								if(!array_key_exists($groupingId, $groupings)) {
									$groupings[$groupingId] = array();
								}
								$groupings[$groupingId][] = $groupName;
							}
						}
						// Add the new groups to the $mergeVals
						if(!empty($groupings)) {
							foreach($groupings as $groupingId => $newGroups) {
								$newGrouping = array();
								$newGrouping['id'] = $groupingId;
								$newGrouping['groups'] = implode(",", $newGroups);
								$mergeVals['GROUPINGS'][] = $newGrouping;
							}
						}

						// Subscribe to MC list
						if($this->mcApi->listSubscribe(
								$mcListToAdd,
								$email,
								$mergeVals,
								$this->email_type,
								$this->double_optin,
								true,
								false,
								$this->send_welcome)) {
							// Add new MailChimp subscription to session to avoid that MailChimp sends multiple
							// emails for one subscription (before subscription is confirmed by the user)
							$session->set('mailchimp.' . $mcSubscribeId , 'new', 'com_akeebasubs');
						}
					}
				}
			}
		}

		// Get the user's MailChimp lists
		$currentLists = $this->mcApi->listsForEmail($email);

		// Remove MC group from existing list subscription
		if(!empty($removeMCGroups) && is_array($currentLists)) {
			foreach($removeMCGroups as $mcGroupId) {
				$groupName = str_replace(',', '\,', $this->groupingsGroupName[$mcGroupId]);
				// No group name
				if(empty($groupName)) continue;
				$groupingId = $this->groupingsGroupMap[$mcGroupId];
				// No correspnding grouping
				if(empty($groupingId)) continue;
				$listId = $this->groupingsListMap[$groupingId];
				// No correspnding list
				if(empty($listId)) continue;
				// User is not subscribed to this list
				if(!in_array($listId, $currentLists)) continue;
				// We passed all checks: Remove the group
				$this->removeMCGroup($email, $listId, $groupingId, $groupName);
			}
		}

		// Add MC group to existing list subscription
		if(!empty($addMCGroups) && is_array($currentLists)) {
			foreach($addMCGroups as $mcGroupId) {
				$groupName = str_replace(',', '\,', $this->groupingsGroupName[$mcGroupId]);
				// No group name
				if(empty($groupName)) continue;
				$groupingId = $this->groupingsGroupMap[$mcGroupId];
				// No correspnding grouping
				if(empty($groupingId)) continue;
				$listId = $this->groupingsListMap[$groupingId];
				// No correspnding list
				if(empty($listId)) continue;
				// User is not subscribed to this list
				if(!in_array($listId, $currentLists)) continue;
				// We passed all checks: Add the group
				$this->addMCGroup($email, $listId, $groupingId, $groupName);
			}
		}
	}

	/**
	 * =========================================================================
	 * !!! CRUFT WARNING !!!
	 * =========================================================================
	 *
	 * The following methods are leftovers from the Olden Days (before 2.4.5).
	 * At some point (most likely 2.6) they will be removed. For now they will
	 * stay here so that we can do a transparent migration.
	 */

	/**
	 * Moves this plugin's settings from the plugin into each subscription
	 * level's configuration parameters.
	 */
	protected function upgradeSettings($config = array())
	{
		$model = FOFModel::getTmpInstance('Levels','AkeebasubsModel');
		$levels = $model->getList(true);
		$addgroupsKey = strtolower($this->name).'_addgroups';
		$removegroupsKey = strtolower($this->name).'_removegroups';
		if(!empty($levels)) {
			foreach($levels as $level)
			{
				$save = false;
				if(is_string($level->params)) {
					$level->params = @json_decode($level->params);
					if(empty($level->params)) {
						$level->params = new stdClass();
					}
				} elseif(empty($level->params)) {
					$level->params = new stdClass();
				}
				if(array_key_exists($level->akeebasubs_level_id, $this->addGroups)) {
					if(empty($level->params->$addgroupsKey)) {
						$level->params->$addgroupsKey = $this->addGroups[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				if(array_key_exists($level->akeebasubs_level_id, $this->removeGroups)) {
					if(empty($level->params->$removegroupsKey)) {
						$level->params->$removegroupsKey = $this->removeGroups[$level->akeebasubs_level_id];
						$save = true;
					}
				}
				if($save) {
					$level->params = json_encode($level->params);
					$result = $model->setId($level->akeebasubs_level_id)->save( $level );
				}
			}
		}

		// Remove the plugin parameters
		if(isset($config['params'])) {
			$configParams = @json_decode($config['params']);
			unset($configParams->addlists);
			unset($configParams->removelists);
			$param_string = @json_encode($configParams);

			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
				->update($db->qn('#__extensions'))
				->where($db->qn('type').'='.$db->q('plugin'))
				->where($db->qn('element').'='.$db->q(strtolower($this->name)))
				->where($db->qn('folder').'='.$db->q('akeebasubs'))
				->set($db->qn('params').' = '.$db->q($param_string));
			$db->setQuery($query);
			$db->execute();
		}
	}

	/*
	 * Removes a MailChimp user to a MailChimp group.
	 */
	private function removeMCGroup($userEmail, $listId, $groupingId, $groupName)
	{
		$userMCInfo = $this->mcApi->listMemberInfo($listId, $userEmail);
		$userMCData = $userMCInfo['data'][0];
		$userMergeVars = $userMCData['merges'];
		if(isset($userMergeVars['GROUPINGS']) && is_array($userMergeVars['GROUPINGS'])) {
			$groupings = $userMergeVars['GROUPINGS'];
			foreach($groupings as $key => $grouping) {
				if($groupingId == $grouping['id']) {
					$newGroups = array();
					$groupsChanged = false;
					$existingGroupsString = $grouping['groups'];
					$existingGroupsArray = $this->mcGroupsToArray($existingGroupsString);
					foreach($existingGroupsArray as $existingGroup) {
						$existingGroup = trim($existingGroup);
						if($existingGroup != $groupName) {
							// If this is not the group to be removed, add it again
							$newGroups[] = $existingGroup;
						} else {
							// The group that needs to be removed is there
							$groupsChanged = true;
						}
					}
					if($groupsChanged) {
						// Update MailChimp using the new groups
						if(empty($newGroups)) {
							$newGroupsString = '';
						} else {
							$newGroupsString = implode(",", $newGroups);
						}
						$userMergeVars['GROUPINGS'][$key]['groups'] = $newGroupsString;
						$this->mcApi->listUpdateMember($listId, $userEmail, $userMergeVars);
					}
				}
			}
		}
	}

	/*
	 * Adds a MailChimp user to a MailChimp group.
	 */
	private function addMCGroup($userEmail, $listId, $groupingId, $groupName)
	{
		$userMCInfo = $this->mcApi->listMemberInfo($listId, $userEmail);
		$userMCData = $userMCInfo['data'][0];
		$userMergeVars = $userMCData['merges'];
		if(isset($userMergeVars['GROUPINGS']) && is_array($userMergeVars['GROUPINGS'])) {
			$groupings = $userMergeVars['GROUPINGS'];
			foreach($groupings as $key => $grouping) {
				if($groupingId == $grouping['id']) {
					$newGroups = array();
					$groupsChanged = true;
					$existingGroupsString = $grouping['groups'];
					$existingGroupsArray = $this->mcGroupsToArray($existingGroupsString);
					$newGroups = $groupsArray;
					foreach($existingGroupsArray as $existingGroup) {
						$existingGroup = trim($existingGroup);
						if($existingGroup == $groupName) {
							// The group that needs to be added is already there - nothing to do
							$groupsChanged = false;
							break;
						}
					}
					if($groupsChanged) {
						// Use the existing groups, add the new one, and update MailChimp
						$newGroups = $existingGroupsArray;
						$newGroups[] = $groupName;
						$newGroupsString = implode(",", $newGroups);
						$userMergeVars['GROUPINGS'][$key]['groups'] = $newGroupsString;
						$this->mcApi->listUpdateMember($listId, $userEmail, $userMergeVars);
					}
				}
			}
		}
	}

	private function mcGroupsToArray($groupsString)
	{
		$groupsArray = array();
		$groupStringToBeParsed = $groupsString;
		while(true) {
			$pos = strpos($groupStringToBeParsed, ',');
			if(! $pos) break;
			$charBeforeComma = substr($groupStringToBeParsed, ($pos - 1), 1);
			// Check for '\,'
			if($charBeforeComma != '\\') {
				$groupsArray[] = trim(substr($groupStringToBeParsed, 0, $pos));
				$groupStringToBeParsed = trim(substr($groupStringToBeParsed, ($pos + 1)));
			}
		}
		if(!empty($groupStringToBeParsed)) {
			$groupsArray[] = $groupStringToBeParsed;
		}
		return $groupsArray;
	}

	/*
	 * Returns the MailChimp lists that exist at the MC account.
	 */
	protected function getGroups() {
		static $groups = null;

		if(is_null($groups)) {
			$groups = array();

			$start = 0;
			$limit = 100;
			$mcLists = $this->mcApi->lists(array(), $start, $limit);
			$total = $mcLists['total'];
			while(true) {
				if (is_array($mcLists['data']) && count($mcLists['data']))
				{
					foreach($mcLists['data'] as $list) {
						$listTitle = $list['name'];
						$listId = $list['id'];
						$groups[$listTitle] = $listId;
					}
				}
				if(($start + $limit) < $total) {
					$start += $limit;
					$mcLists = $this->mcApi->lists(array(), $start, $limit);
				} else {
					break;
				}
			}
		}

		return $groups;
	}

	private function initMCGroups()
	{
		$addLevels = array_keys($this->addMCGroups);
		$removeLevels = array_keys($this->removeMCGroups);
		$addAndRemoveLevels = array_merge($addLevels, $removeLevels);
		$allLevels = array_unique($addAndRemoveLevels);
		foreach($allLevels as $levelId) {
			$this->getMCGroups($levelId);
		}
	}

	/*
	 * Returns the MailChimp groups that exist at the MC account.
	 */
	private function getMCGroups($levelId)
	{
		static $mcGroups = array();

		if(! array_key_exists($levelId, $mcGroups)) {
			$mcGroups[$levelId] = array();
		}

		if(empty($mcGroups[$levelId])) {
			$mcLists = $this->getGroups();
			foreach($mcLists as $listTitle => $listId) {
				if(array_key_exists($levelId, $this->addGroups) && in_array($listId, $this->addGroups[$levelId])) {
					$interestGroupings = $this->mcApi->listInterestGroupings($listId);
					if($interestGroupings) {
						foreach($interestGroupings as $groupings) {
							$groupingsId = $groupings['id'];
							$groupingsName = trim($groupings['name']);
							// Add to grouping-list map
							$this->groupingsListMap[$groupingsId] = $listId;
							foreach($groupings['groups'] as $g) {
								$groupName = trim($g['name']);
								$groupName = trim(preg_replace('/<[^>]+>/', "", $groupName));
								$title =  $groupName . ' ( ' . $groupingsName . ' - ' . $listTitle . ' )';
								$id = md5($title);
								$mcGroups[$levelId][$title] = $id;
								// Add to grouping-group map
								$this->groupingsGroupMap[$id] = $groupingsId;
								// Add group name
								$this->groupingsGroupName[$id] = $g['name'];
							}
						}
					}
				}
			}
		}

		return $mcGroups[$levelId];
	}

	/*
	 * Return the custom fields for this subscription level.
	 */
	protected function getCustomFields($levelId)
	{
		static $customFields = array();

		if(empty($customFields[$levelId])) {
			$customFields[$levelId] = array();
			$items = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
				->enabled(1)
				->getItemList(true);

			// Loop through the items
			foreach($items as $item) {
				if($item->show == 'all' || $item->akeebasubs_level_id == $levelId) {
					$customFields[$levelId][$item->title] = $item->akeebasubs_customfield_id;
				}
			}
		}

		return $customFields[$levelId];
	}

	/*
	 * Return the custom fields as a HTML select field.
	 */
	protected function getMergeTagSelectField($level)
	{
		$customFields = $this->getCustomFields($level->akeebasubs_level_id);
		$options = array();
		$options[] = JHTML::_('select.option','',JText::_('PLG_AKEEBASUBS_' . strtoupper($this->name) . '_NONE'));
		foreach($customFields as $title => $id) {
			$options[] = JHTML::_('select.option',$id,$title);
		}
		// Set pre-selected values
		$selected = array();
		if(! empty($this->customFields[$level->akeebasubs_level_id])) {
			$selected = $this->customFields[$level->akeebasubs_level_id];
		}
		// Create the select field
		return JHtmlSelect::genericlist($options, 'params[' . strtolower($this->name) . '_customfields][]', 'multiple="multiple" size="8" class="input-large"', 'value', 'text', $selected);
	}

	/*
	 * Return the MailChimp lists as a HTML select field.
	 */
	protected function getMCGroupSelectField($level, $type)
	{
		if(! in_array($type, array('add', 'remove'))) return '';
		// Put groups in select field
		$groups = $this->getMCGroups($level->akeebasubs_level_id);
		$options = array();
		$options[] = JHTML::_('select.option','',JText::_('PLG_AKEEBASUBS_' . strtoupper($this->name) . '_NONE'));
		foreach($groups as $title => $id) {
			$options[] = JHTML::_('select.option',$id,$title);
		}
		// Set pre-selected values
		$selected = array();
		if($type == 'add') {
			if(! empty($this->addMCGroups[$level->akeebasubs_level_id])) {
				$selected = $this->addMCGroups[$level->akeebasubs_level_id];
			}
		} else {
			if(! empty($this->removeMCGroups[$level->akeebasubs_level_id])) {
				$selected = $this->removeMCGroups[$level->akeebasubs_level_id];
			}
		}
		// Create the select field
		return JHtmlSelect::genericlist($options, 'params[' . strtolower($this->name) . '_' . $type . 'mcgroups][]', 'multiple="multiple" size="8" class="input-xxlarge"', 'value', 'text', $selected);
	}
}