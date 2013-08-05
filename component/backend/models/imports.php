<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelImports extends FOFModel
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
				else
				{
					$searchCharacter = "\n";
				}

				// Roll back the file
				$pos = strpos($line, $searchCharacter);
				$rollback = strlen($line) - strpos($line, $searchCharacter);
				fseek($handle, -$rollback + strlen($searchCharacter), SEEK_CUR);
				// And chop the line
				$line = substr($line, 0, $pos);
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
		static $cache = array();

		// No email? Get out
		if(!$this->getCsvData('email'))
		{
			return false;
		}

		$usermodel = FOFModel::getTmpInstance('Jusers', 'AkeebasubsModel');

		// Let's cache the users
		if(!$cache)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)->select('*')->from('#__users');
			$cache['email'] = $db->setQuery($query)->loadObjectList('email');
			$cache['user']  = $db->setQuery($query)->loadObjectList('username');
		}

		// No user? Let's create it
		if(!isset($cache['email'][$this->getCsvData('email')]))
		{
			// Username already used... let's stop here
			if(isset($cache['user'][$this->getCsvData('username')]))
			{
				return false;
			}

			$params = array(
				'name'			=> $this->getCsvData('name'),
				'username'		=> $this->getCsvData('username'),
				'email'			=> $this->getCsvData('email'),
				'password'		=> $this->getCsvData('password'),
				'password2'		=> $this->getCsvData('password')
			);

			// Error while creating the user
			if(!($userid = $usermodel->createNewUser($params)))
			{
				return false;
			}
		}
		else
		{
			$userid = $cache['email'][$this->getCsvData('email')]->id;
		}

		// Ok, in a way or in another I have the Joomla user. Now it's time to update AS user
		$ASuser = FOFTable::getAnInstance('User', 'AkeebasubsTable');

		// Let's try loading it using Joomla id. Using the table object will assure me that I'll automatically update/create the user
		$ASuser->load(array('user_id' => $userid));

		$ASuser->isbusiness     = (int) $this->getCsvData('isbusiness');
		$ASuser->businessname   = $this->getCsvData('businessname');
		$ASuser->occupation     = $this->getCsvData('occupation');
		$ASuser->vatnumber      = $this->getCsvData('vatnumber');
		$ASuser->viesregistered = (int) $this->getCsvData('viesregistered');
		$ASuser->address1       = $this->getCsvData('address1');
		$ASuser->address2       = $this->getCsvData('address2');
		$ASuser->city           = $this->getCsvData('city');
		$ASuser->state          = $this->getCsvData('state');
		$ASuser->zip            = $this->getCsvData('zip');
		$ASuser->country        = $this->getCsvData('country');

		if(!$ASuser->store())
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
			$levelCache = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')->createTitleLookup();
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

		$sub = FOFTable::getAnInstance('Subscription', 'AkeebasubsTable');

		$bind['user_id']             = $userid;
		$bind['akeebasubs_level_id'] = $level->akeebasubs_level_id;
		$bind['publish_up']          = $publish_up->toSql();
		$bind['publish_down']        = $publish_down->toSql();
		$bind['enabled']             = $this->getCsvData('enabled', 1);
		$bind['processor']           = $this->getCsvData('processor', 'import');
		$bind['processor_key']       = $this->getCsvData('processor_key', md5(microtime().$app->getHash(JUserHelper::genRandomPassword())));
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
			$cache = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')->createTitleLookup();
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
