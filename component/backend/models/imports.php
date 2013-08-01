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
	 * Parses a CSV file, importing every row as a new user
	 *
	 * @param   string  $file           Uploaded file
	 * @param   int     $delimiter      Delimiter index, chosen by the user
	 * @param   int     $skipfirst      Should I skip the first row?
	 *
	 * @return bool|int     False if there is an error, otherwise the number of imported users.
	 */
	public function import($file, $delimiter, $skipfirst = 0)
	{
		$delimiters = array(1,2,3);
		$result     = 0;
		$i          = 0;

		if(!$file)
		{
			$this->setError(JText::_('COM_AKEEBASUBS_USERS_IMPORT_ERR_FILE'));
			return false;
		}

		if(!$delimiter || !in_array($delimiter, $delimiters))
		{
			$this->setError(JText::_('COM_AKEEBASUBS_USERS_IMPORT_ERR_DELIMITER'));
			return false;
		}

		// At the moment I don't need the enclosure, it seems that fgetcsv works with or without it
		list($field, ) = $this->decodeDelimiterOptions($delimiter);

		$handle = fopen($file, 'r');
		while (($data = fgetcsv($handle, 0, $field)) !== false)
		{
			$i++;

			// Should I skip first line (ie there are headers in the file)?
			if($skipfirst && $i == 1)
			{
				continue;
			}

			// Perform integrity checks on current line (required fields, existing subscription etc etc)
			$check = $this->performImportChecks($data);
			if(!$check)
			{
				$this->setError(JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i));
				continue;
			}

			if(!$this->checkCustomer($data))
			{
				$this->setError(JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i));
				continue;
			}
		}

		fclose($handle);

		return $result;
	}

	protected function checkCustomer($data)
	{
		static $cache = array();

		// No email? Get out
		if(!$data[1])
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
		if(!isset($cache['email'][$data[1]]))
		{
			// Username already used... let's stop here
			if(isset($cache['user'][$data[0]]))
			{
				return false;
			}

			$params = array(
				'name'			=> $data[3],
				'username'		=> $data[0],
				'email'			=> $data[1],
				'password'		=> $data[2],
				'password2'		=> $data[2]
			);

			// Error while creating the user
			if(!($userid = $usermodel->createNewUser($params)))
			{
				return false;
			}
		}
		else
		{
			$userid = $cache['email'][$data[1]]->id;
		}

		return true;
	}

	/**
	 * Decodes a single value (1,2,3) to an array containing the field delimiter and enclosure
	 *
	 * @param   int     $delimiter
	 *
	 * @return  array    [0] => field delimiter, [1] => enclosure char
	 */
	protected function decodeDelimiterOptions($delimiter)
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
	 * @param   array   $data   Columns got from parsing a CSV line
	 *
	 * @return  bool    Is everything alright?
	 */
	protected function performImportChecks(array $data)
	{
		static $cache = array();

		// Do I have all the columns?
		if(count($data) != 32)
		{
			return false;
		}

		// Required fields as: username, email, password, name, subscription_level, publish_up
		if(!$data[0] || !$data[1] || !$data[2] || !$data[3] || !$data[15] || !$data[16])
		{
			return false;
		}

		if(!$cache)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)->select('title')->from('#__akeebasubs_levels');
			$rows  = $db->setQuery($query)->loadObjectList('title');
			$cache = array_change_key_case($rows, CASE_UPPER);
		}

		// Is the subscrption level existing?
		if(!isset($cache[strtoupper($data[15])]))
		{
			return false;
		}

		return true;
	}
}
