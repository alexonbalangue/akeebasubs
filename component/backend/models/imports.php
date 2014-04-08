<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelImports extends F0FModel
{
	/**
	 * Column mapping for imported data
	 *
	 * @var array
	 */
	protected $columnMap   = array();

	/**
	 * Current csv row we're going to import
	 *
	 * @var array
	 */
	protected $currentData = array();

	/**
	 * Parses a CSV file, importing every row as a new user
	 *
	 * @param   string  $file               Uploaded file
	 * @param   string  $fieldDelimiter     Fields separator, such as ";" or ","
	 * @param   string  $fieldEnlosure      Field enclosurem such as " or '
	 *
	 * @return bool|int     False if there is an error, otherwise the number of imported users.
	 */
	public function import($file, $fieldDelimiter, $fieldEnlosure)
	{
		$result     = 0;
		$i          = 0;

		if(!$file)
		{
			$this->setError(JText::_('COM_AKEEBASUBS_USERS_IMPORT_ERR_FILE'));
			return false;
		}

		// At the moment I don't need the enclosure, it seems that fgetcsv works with or without it
		//list($field, ) = $this->decodeDelimiterOptions($delimiter);

		$handle = fopen($file, 'r');
		while (true)
		{
			// Read the next line
			$line = '';
			while (!feof($handle) && (strpos($line, "\n") === false) && (strpos($line, "\r") === false))
			{
				$line .= fgets($handle, 65536);
			}

			// Past EOF and no data read? Break.
			if (empty($line) && feof($handle))
			{
				break;
			}

			// Did we read more than one line?
			if (!in_array(substr($line, -1), array("\r", "\n")))
			{
				// Get the position of linefeed and carriage return characters in the line read
				$posLF = strpos($line, "\n");
				$posCR = strpos($line, "\r");

				// Determine line ending
				if (($posCR !== false) && ($posLF !== false))
				{
					// We have both \r and \n. Are they strung together?
					if ($posLF - $posCR == 1)
					{
						// Yes. Windows/DOS line termination.
						$searchCharacter = "\r\n";
					}
					else
					{
						// Nope. It's either Mac OS Classic or UNIX. Which one?
						if ($posCR < $posLF)
						{
							// Mac OS Classic
							$searchCharacter = "\r";
						}
						else
						{
							// UNIX
							$searchCharacter = "\n";
						}
					}
				}
				elseif ($posCR !== false)
				{
					$searchCharacter = "\r";
				}
				elseif ($posLF !== false)
				{
					$searchCharacter = "\n";
				}
				else
				{
					$searchCharacter = null;
				}

				// Roll back the file
				if (!is_null($searchCharacter))
				{
					$pos = strpos($line, $searchCharacter);
					$rollback = strlen($line) - strpos($line, $searchCharacter);
					fseek($handle, -$rollback + strlen($searchCharacter), SEEK_CUR);
					// And chop the line
					$line = substr($line, 0, $pos);
				}
			}

			// Handle DOS and Mac OS classic linebreaks
			$line = str_replace("\r\n", "\n", $line);
			$line = str_replace("\r", "\n", $line);
			$line = trim($line);

			if (empty($line))
			{
				continue;
			}

			// I have to use this weird structure because if an user passes an empty char as field enclosure
			// str_getcsv will return false, so I have to omit it, forcing PHP to use the function default one
			if($fieldEnlosure)
			{
				$this->currentData = str_getcsv($line, $fieldDelimiter, $fieldEnlosure);
			}
			else
			{
				$this->currentData = str_getcsv($line, $fieldDelimiter);
			}

			if($this->currentData === false)
			{
				break;
			}

			$i++;

			// Skip first line, there are headers in the file, so let's map them and then continue
			if($i == 1)
			{
				$this->readColumns();
				continue;
			}

			$this->performDataMapping();

			// Perform integrity checks on current line (required fields, existing subscription etc etc)
			$check = $this->performImportChecks();
			if(!$check)
			{
				$this->setError(JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i));
				continue;
			}

			if(!($userid = $this->importCustomer()))
			{
				$this->setError(JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i));
				continue;
			}

			if(!$this->importSubscription($userid))
			{
				$this->setError(JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i));
				continue;
			}

			$result++;
		}

		fclose($handle);

		return $result;
	}

	/**
	 * Imports the user, creating if there isn't and updating the AS user table.
	 *
	 * @return  bool|int        Joomla user_id if successful, otherwise false
	 */
	protected function importCustomer()
	{
		static $cache = array(
			'email'	=> array(),
			'user'	=> array(),
		);

		// No email? Get out
		if(!$this->getCsvData('email'))
		{
			return false;
		}

		$usermodel = F0FModel::getTmpInstance('Jusers', 'AkeebasubsModel');
		$subusermodel = F0FModel::getTmpInstance('Users', 'AkeebasubsModel');

		$userid = 0;
		$email = $this->getCsvData('email');
		$username = $this->getCsvData('username');

		if (!empty($cache['email']))
		{
			if (array_key_exists($email, $cache['email']))
			{
				$userid = $cache['email'][$email]->id;
			}
		}

		if(($userid == 0) && !empty($cache['user']))
		{
			if (array_key_exists($username, $cache['user']))
			{
				$userid = $cache['user'][$username]->id;
			}
		}

		if($userid == 0)
		{
			// Maybe the user does exist?
			$db = $this->getDbo();
			$query = $db->getQuery(true)
				->select('id')
				->from($db->qn('#__users'))
				->where($db->qn('email') . ' = ' . $db->q($email), 'OR')
				->where($db->qn('username') . ' = ' . $db->q($username), 'OR');
			$db->setQuery($query);
			$userid = $db->loadResult();

			if (empty($userid))
			{
				$userid = 0;
			}
			else
			{
				$cache['email'][$email] = $userid;
				$cache['user'][$username] = $userid;
			}
		}

		// No user? Let's create it
		if($userid == 0)
		{
			$params = array(
				'name'			=> $this->getCsvData('name'),
				'username'		=> $username,
				'email'			=> $email,
				'password'		=> $this->getCsvData('password'),
				'password2'		=> $this->getCsvData('password')
			);

			// Error while creating the user
			$userid = $usermodel->createNewUser($params);

			if(!$userid)
			{
				return false;
			}

			// Cache this user
			$cache['email'][$email] = $userid;
			$cache['user'][$username] = $userid;
		}

		// Ok, in a way or in another I have the Joomla user. Now it's time to update AS user
		$ASuser = clone $subusermodel->getTable();

		// Let's try loading it using Joomla id. Using the table object will assure me that I'll automatically update/create the user
		$ASuser->load(array('user_id' => $userid));

		$updates = array(
			'user_id'			=> (int) $userid,
			'isbusiness'		=> (int) $this->getCsvData('isbusiness'),
			'businessname'		=> $this->getCsvData('businessname'),
			'occupation'		=> $this->getCsvData('occupation'),
			'vatnumber'			=> $this->getCsvData('vatnumber'),
			'viesregistered'	=> (int) $this->getCsvData('viesregistered'),
			'address1'			=> $this->getCsvData('address1'),
			'address2'			=> $this->getCsvData('address2'),
			'city'				=> $this->getCsvData('city'),
			'state'				=> $this->getCsvData('state'),
			'zip'				=> $this->getCsvData('zip'),
			'country'			=> $this->getCsvData('country'),
		);

		if(!$ASuser->save($updates))
		{
			return false;
		}

		return $userid;
	}

	/**
	 * Imports a subscription for a given user, using data coming from a CSV file
	 *
	 * @param   int     $userid     Joomla user_id of the imported user
	 *
	 * @return  bool    True if successful
	 */
	protected function importSubscription($userid)
	{
		static $levelCache = array();

		JLoader::import('joomla.application.component.helper');

		if(!class_exists('AkeebasubsHelperFormat')){
			require_once JPATH_ROOT.'/administrator/components/com_akeebasubs/helpers/format.php';
		}

		if(!$levelCache)
		{
			$levelCache = F0FModel::getTmpInstance('Levels', 'AkeebasubsModel')->createTitleLookup();
		}

		$app        = JFactory::getApplication();
		$level      = $levelCache[strtoupper($this->getCsvData('subscription_level'))];
		$publish_up = AkeebasubsHelperFormat::checkDateFormat($this->getCsvData('publish_up'));

		if(!$publish_up)
		{
			return false;
		}

		// Publish down
		if($this->getCsvData('publish_down'))
		{
			$publish_down = AkeebasubsHelperFormat::checkDateFormat($this->getCsvData('publish_down'));
			if(!$publish_down)
			{
				return false;
			}
		}
		else
		{
			$temp = strtotime('+'.$level->duration.' days', $publish_up->toUnix());
			$publish_down = new JDate($temp);
		}

		// Created on
		if($this->getCsvData('created_on'))
		{
			$created_on = AkeebasubsHelperFormat::checkDateFormat($this->getCsvData('created_on'));
			if(!$created_on)
			{
				return false;
			}
		}
		else
		{
			$created_on = clone $publish_up;
		}

		$sub = clone F0FModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')->getTable();

		$randomString = JUserHelper::genRandomPassword();
		if (version_compare(JVERSION, '3.2', 'ge'))
		{
			$hash = JApplication::getHash($randomString);
		}
		else
		{
			$hash = JFactory::getApplication()->getHash($randomString);
		}

		$bind['user_id']             = $userid;
		$bind['akeebasubs_level_id'] = $level->akeebasubs_level_id;
		$bind['publish_up']          = $publish_up->toSql();
		$bind['publish_down']        = $publish_down->toSql();
		$bind['enabled']             = $this->getCsvData('enabled', 1);
		$bind['processor']           = $this->getCsvData('processor', 'import');
		$bind['processor_key']       = $this->getCsvData('processor_key', md5(microtime() . $hash));
		$bind['state']               = $this->getCsvData('status', 'C');
		$bind['net_amount']          = $this->getCsvData('net_amount', 0);
		$bind['tax_amount']          = $this->getCsvData('tax_amount', 0);
		$bind['gross_amount']        = $this->getCsvData('gross_amount', $bind['net_amount'] + $bind['tax_amount']);
		$bind['recurring_amount']    = $this->getCsvData('recurring_amount', $bind['gross_amount']);
		$bind['tax_percent']         = $this->getCsvData('tax_percent', (100 * $bind['tax_amount'] / $bind['net_amount']));
		$bind['created_on']          = $created_on->toSql();
		$bind['prediscount_amount']  = $this->getCsvData('prediscount_amount', $bind['gross_amount']);
		$bind['discount_amount']     = $this->getCsvData('discount_amount', 0);
		$bind['contact_flag']        = $this->getCsvData('contact_flag', 0);

		return $sub->save($bind);
	}

	/**
	 * Decodes a single value (1,2,3) to an array containing the field delimiter and enclosure
	 *
	 * @param   int     $delimiter
	 *
	 * @return  array   [0] => field delimiter, [1] => enclosure char
	 */
	public function decodeDelimiterOptions($delimiter)
	{
		if($delimiter == 1)
		{
			return array(',', '');
		}
		elseif($delimiter == 2)
		{
			return array(';', '');
		}
		else
		{
			return array(';', '"');
		}
	}

	/**
	 * Performs checks on current columns got from the CSV, controlling that everything is alright
	 *
	 * @return  bool    Is everything alright?
	 */
	protected function performImportChecks()
	{
		static $cache = array();

		// Required fields as: username, email, password, name, subscription_level, publish_up
		if(!$this->getCsvData('username') || !$this->getCsvData('email') || !$this->getCsvData('password') ||
		   !$this->getCsvData('name') || !$this->getCsvData('subscription_level') || !$this->getCsvData('publish_up'))
		{
			return false;
		}

		if(!$cache)
		{
			$cache = F0FModel::getTmpInstance('Levels', 'AkeebasubsModel')->createTitleLookup();
		}

		// Is the subscrption level existing?
		if(!isset($cache[strtoupper($this->getCsvData('subscription_level'))]))
		{
			return false;
		}

		return true;
	}

	protected function readColumns()
	{
		for($i = 0; $i < count($this->currentData); $i++)
		{
			$this->columnMap[$this->currentData[$i]] = $i;
		}
	}

	protected function performDataMapping()
	{
		$mapping = array();

		foreach($this->columnMap as $column => $position)
		{
			if(!$column) continue;

			$mapping[$column] = $this->currentData[$position];
		}

		$this->currentData = $mapping;
	}

	protected function getCsvData($key, $default = '')
	{
		if(isset($this->currentData[$key]))
		{
			return $this->currentData[$key];
		}

		return $default;
	}
}
