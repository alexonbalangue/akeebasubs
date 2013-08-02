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

			if(!($userid = $this->importCustomer($data)))
			{
				$this->setError(JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i));
				continue;
			}

			if(!$this->importSubscription($userid, $data))
			{
				$this->setError(JText::sprintf('COM_AKEEBASUBS_USERS_IMPORT_ERR_LINE', $i));
				continue;
			}

			$result++;
		}

		fclose($handle);

		return $result;
	}

	protected function importCustomer($data)
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

		// Ok, in a way or in another I have the Joomla user. Now it's time to update AS user
		$ASuser = FOFTable::getAnInstance('User', 'AkeebasubsTable');

		// Let's try loading it using Joomla id. Using the table object will assure me that I'll automatically update/create the user
		$ASuser->load(array('user_id' => $userid));

		$ASuser->isbusiness     = (int)$data[4];
		$ASuser->businessname   = $data[5];
		$ASuser->occupation     = $data[6];
		$ASuser->vatnumber      = $data[7];
		$ASuser->viesregistered = (int)$data[8];
		$ASuser->address1       = $data[9];
		$ASuser->address2       = $data[10];
		$ASuser->city           = $data[11];
		$ASuser->state          = $data[12];
		$ASuser->zip            = $data[13];
		$ASuser->country        = $data[14];

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
	 * @param   array   $data       Array coming from parsing a CSV file
	 *
	 * @return  bool    True if successful
	 */
	protected function importSubscription($userid, array $data)
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
		$level      = $levelCache[strtoupper($data[15])];
		$publish_up = AkeebasubsHelperFormat::checkDateFormat($data[16]);

		if(!$publish_up)
		{
			return false;
		}

		// Publish down
		if($data[17])
		{
			$publish_down = AkeebasubsHelperFormat::checkDateFormat($data[17]);
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
		if($data[27])
		{
			$created_on = AkeebasubsHelperFormat::checkDateFormat($data[27]);
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
		$bind['enabled']             = $data[18] != '' ? $data[18] : 1;
		$bind['processor']           = $data[19] ? $data[19] : 'import';
		$bind['processor_key']       = $data[20] ? $data[20] : md5(microtime().$app->getHash(JUserHelper::genRandomPassword()));
		$bind['state']               = $data[21] ? $data[21] : 'C';
		$bind['net_amount']          = $data[22] ? $data[22] : 0;
		$bind['tax_amount']          = $data[23] ? $data[23] : 0;
		$bind['gross_amount']        = $data[24] ? $data[24] : $bind['net_amount'] + $bind['tax_amount'];
		$bind['recurring_amount']    = $data[25] ? $data[25] : $bind['gross_amount'];
		$bind['tax_percent']         = $data[26] ? $data[26] : (100 * $bind['tax_amount'] / $bind['net_amount']);
		$bind['created_on']          = $created_on->toSql();
		$bind['prediscount_amount']  = $data[28] ? $data[28] : $bind['gross_amount'];
		$bind['discount_amount']     = $data[29] ? $data[29] : 0;
		$bind['contact_flag']        = $data[30] ? $data[30] : 0;

		return $sub->save($bind);
	}

	/**
	 * Decodes a single value (1,2,3) to an array containing the field delimiter and enclosure
	 *
	 * @param   int     $delimiter
	 *
	 * @return  array   [0] => field delimiter, [1] => enclosure char
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
			$cache = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')->createTitleLookup();
		}

		// Is the subscrption level existing?
		if(!isset($cache[strtoupper($data[15])]))
		{
			return false;
		}

		return true;
	}
}
