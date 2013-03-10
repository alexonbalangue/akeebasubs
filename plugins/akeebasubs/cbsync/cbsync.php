<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsCbsync extends JPlugin
{
	/**
	 * This method is called whenever a user starts a new subscription and
	 * Akeeba Subscriptions wants to fetch user data. You can use it to fetch
	 * user information from additional sources and return them in an array.
	 * The values in the array will replace the values stored in the user's
	 * profile.
	 *
	 * @param object $userData The already fetched user information
	 *
	 * @return array A key/value array with user information overrides
	 */
	public function onAKUserGetData($userData)
	{
		if(empty($userData->username)) return array();
		$user_id = JFactory::getUser($userData->username)->id;

		$db = JFactory::getDbo();

		// Load existing #__comprofiler records
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__comprofiler'))
			->where($db->qn('user_id') . '=' . $db->q($user_id));
		$db->setQuery($query);
		$record = $db->loadObject();

		// If we don't have profile records just quit
		if (!is_object($record))
		{
			return array();
		}

		// Initialise return value
		$ret = array();

		// Make sure the select helper is loaded
		if(!class_exists('AkeebasubsHelperSelect'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/helpers/select.php';
		}

		// Special case: country
		if (isset($record->cb_country))
		{
			$country = $record->cb_country;
			if(in_array($country, AkeebasubsHelperSelect::$countries))
			{
				$record->cb_country = array_search($country, AkeebasubsHelperSelect::$countries);
			}
			else
			{
				$record->cb_country = 'US';
			}
		}

		// Special case: state
		if (isset($record->cb_state))
		{
			if (isset($record->cb_country))
			{
				$country = $record->cb_country;
			}
			else
			{
				$country = 'US';
			}
			$state = $record->cb_state;
			$states = AkeebasubsHelperSelect::$countries[$country];

			if(in_array($state, $states))
			{
				$state = array_search($state, $states);
			}
			else
			{
				$state = '';
			}
			$record->cb_state = $state;
		}

		// Check for basic information
		$basic_keys = array('isbusiness', 'businessname', 'occupation', 'vatnumber', 'viesregistered', 'taxauthority', 'address1', 'address2', 'city', 'state', 'zip', 'country');
		foreach($basic_keys as $key)
		{
			$key = 'cb_' . $key;
			if(isset($record->$key))
			{
				$ret[$key] = $record->$key;
				unset($record->$key);
			}
		}

		// The rest of the records is treated as extra fields
		$params = array();
		if (!empty($rows))
		{
			foreach($rows as $key => $row)
			{
				if(substr($key,0,3) != 'cb_')
				{
					continue;
				}
				$key = substr($key, 3);
				$ikey ='cb_'.$key;
				$params[$key] = $record->$ikey;
			}
		}
		$ret['params'] = $params;

		// Return result
		return $ret;
	}

	/**
	 * This method is called whenever Akeeba Subscriptions is updating the user
	 * record with new information, either during sign-up or when you manually
	 * update this information in the back-end.
	 *
	 * In this plugin, it does nothing, but it serves as an example for any
	 * developer interested in creating, for example, a "bridge" with a social
	 * component like Community Builder or JomSocial.
	 *
	 * @param AkeebasubsTableUser $userData The user data
	 */
	public function onAKUserSaveData($userData)
	{
		// Get the user ID
		$user_id = $userData->user_id;

		$db = JFactory::getDbo();

		// Load existing #__comprofiler records
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__comprofiler'))
			->where($db->qn('user_id') . '=' . $db->q($user_id));
		$db->setQuery($query);
		$record = $db->loadObject();

		// If we don't have profile records just quit
		if (!is_object($record))
		{
			return array();
		}

		// Initialise the data array
		$data = $userData->getData();

		// Remove the params field
		$params = array();
		if (isset($data['params']))
		{
			$params = $data['params'];
			if (is_string($params))
			{
				$params = json_decode($params, true);
			}
			elseif (is_object($params))
			{
				$params = (array)$params;
			}
			unset($data['params']);
		}

		// Remove some fields which must not be saved
		foreach (array('akeebasubs_user_id', 'user_id', 'notes') as $key)
		{
			if (isset($data[$key]))
			{
				unset($data[$key]);
			}
		}

		// Translate country and state
		if(!class_exists('AkeebasubsHelperSelect'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/helpers/select.php';
		}
		if (isset($data['state']))
		{
			$data['state'] = AkeebasubsHelperSelect::formatState($data['state']);
		}
		if (isset($data['country']))
		{
			$data['country'] = AkeebasubsHelperSelect::formatCountry($data['country']);
		}

		// Convert basic data
		foreach(array_keys($data) as $key)
		{
			$data['cb_'.$key] = $data[$key];
			unset($data[$key]);
		}

		// Explode the params field (unless it's an array or object)
		if (!empty($params))
		{
			foreach ($params as $k => $v)
			{
				$data['cb_'.$k] = json_encode($v);
			}
		}

		$result = true;

		// Loop through all keys, check if they already exist and create/replace them
		if (count($data))
		{
			$keys = get_object_vars($record);
			foreach ($keys as $k => $v)
			{
				if (substr($k, 0, 3) != 'cb_')
				{
					continue;
				}
				if (array_key_exists($k, $data))
				{
					$record->$k = $data[$k];
				}
			}

			$result = $db->updateObject('#__comprofiler', $record, 'id');
		}

		return $result;
	}
}