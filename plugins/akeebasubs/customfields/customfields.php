<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * A sample plugin which creates two extra fields, age group and gender.
 * The former is mandatory, the latter is not
 */
class plgAkeebasubsCustomfields extends JPlugin
{
	private $fieldTypes = array();

	function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);

		$this->_loadFields();
	}

	/**
	 * Renders per-subscription custom fields in the form
	 *
	 * @param  array  $cache
	 *
	 * @return  array  The custom fields definitions
	 */
	public function onSubscriptionFormRenderPerSubFields($cache)
	{
		return $this->_customFieldRender('subscription', $cache);
	}

	/**
	 * Renders per-user custom fields in the form
	 *
	 * @param  array  $userparams
	 * @param  array  $cache
	 *
	 * @return  array  The custom fields definitions
	 */
	public function onSubscriptionFormRender($userparams, $cache)
	{
		return $this->_customFieldRender('user', $cache, $userparams);
	}

	/**
	 * Renders per-subscription or per-user custom fields
	 *
	 * @param   string  $fieldType  'user' or 'subscription'
	 * @param   array   $cache
	 * @param   array   $userparams
	 *
	 * @return  array
	 */
	private function _customFieldRender($fieldType, $cache, $userparams = null)
	{
		// Load the language
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_customfields', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_customfields', JPATH_ADMINISTRATOR, null, true);

		// Init the fields array which will be returned
		$fields = array();

		// Which subscription level is that?
		if(!array_key_exists('subscriptionlevel', $cache)) $cache['subscriptionlevel'] = null;

		// Load field definitions
		$items = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
			->enabled(1)
			->filter_order('ordering')
			->filter_order_Dir('ASC')
			->limitstart(0)
			->limit(0)
			->getItemList(false);

		if(empty($items)) return $fields;

		// Loop through the items
		foreach($items as $item) {
			// If it's not something shown in this level, skip it
			if (in_array($item->show, array('level', 'notlevel')))
			{
				if ($cache['subscriptionlevel'] != -1)
				{
					$fieldlevels = explode(',', $item->akeebasubs_level_id);

					if (($item->show == 'level') && !in_array($cache['subscriptionlevel'], $fieldlevels))
					{
						continue;
					}
					elseif (($item->show == 'notlevel') && in_array($cache['subscriptionlevel'], $fieldlevels))
					{
						continue;
					}
				}
			}

			// Get the names of the methods to use
			$type = $item->type;
			$class = 'AkeebasubsCustomField' . ucfirst($type);

			if (!class_exists($class))
			{
				continue;
			}
			$object = new $class;

			// Add the field to the list
			switch($fieldType)
			{
				case 'user':
					$result = $object->getField($item, $cache, $userparams);
					break;

				case 'subscription':
					$result = $object->getPerSubscriptionField($item, $cache);
					break;
			}

			if(is_null($result) || empty($result)) {
				continue;
			} else {
				$fields[] = $result;
			}

			// Add Javascript for the field
			$object->getJavascript($item);
		}

		// ----- RETURN THE FIELDS -----
		return $fields;
	}

	public function onValidate($data)
	{
		$response = array(
			'valid'				=> true,
			'isValid'			=> true,
			'custom_validation'	=> array()
		);

		// Fetch the custom data
		$custom = $data->custom;

		// Load field definitions
		$items = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
			->enabled(1)
			->filter_order('ordering')
			->filter_order_Dir('ASC')
			->getItemList(true);

		// If there are no custom fields return true (all valid)
		if(empty($items)) return $response;

		// Loop through each custom field
		foreach($items as $item) {
			// Make sure it's supposed to be shown in the particular level
			if (in_array($item->show, array('level','notlevel')))
			{
				if(is_null($data->id)) continue;

				$fieldlevels = explode(',', $item->akeebasubs_level_id);

				if (($item->show == 'level') && !in_array($data->id, $fieldlevels))
				{
					continue;
				}
				elseif (($item->show == 'notlevel') && in_array($data->id, $fieldlevels))
				{
					continue;
				}
			}

			// Make sure there is a validation method for this type of field
			$type = $item->type;
			$class = 'AkeebasubsCustomField' . ucfirst($type);

			if (!class_exists($class))
			{
				continue;
			}
			$object = new $class;

			// Get the validation result and save it in the $response array
			$response['custom_validation'][$item->slug] = $object->validate($item, $custom);

			if(is_null($response['custom_validation'][$item->slug]))
			{
				unset($response['custom_validation'][$item->slug]);
			}
			elseif(!$item->allow_empty)
			{
				$response['isValid'] = $response['isValid'] && $response['custom_validation'][$item->slug];
			}
		}

		// Update the master "valid" reponse. If one of the fields is invalid,
		// the entire plugin's result is invalid (the form should not be submitted)
		$response['valid'] = $response['isValid'];

		return $response;
	}

	public function onValidatePerSubscription($data)
	{
		$response = array(
			'valid'								=> true,
			'isValid'							=> true,
			'subscription_custom_validation'	=> array()
		);

		// Make sure we have a subscription level ID
		if($data->id <= 0)
		{
			return $response;
		}

		// Fetch the custom data
		$subcustom = $data->subcustom;

		// Load field definitions
		$items = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
			->enabled(1)
			->filter_order('ordering')
			->filter_order_Dir('ASC')
			->getItemList(true);

		// If there are no custom fields return true (all valid)
		if(empty($items)) return $response;

		// Loop through each custom field
		foreach($items as $item) {
			// Make sure it's supposed to be shown in the particular level
			if (in_array($item->show, array('level','notlevel')))
			{
				if(is_null($data->id)) continue;

				$fieldlevels = explode(',', $item->akeebasubs_level_id);

				if (($item->show == 'level') && !in_array($data->id, $fieldlevels))
				{
					continue;
				}
				elseif (($item->show == 'notlevel') && in_array($data->id, $fieldlevels))
				{
					continue;
				}
			}

			// Make sure there is a validation method for this type of field
			$type = $item->type;
			$class = 'AkeebasubsCustomField' . ucfirst($type);

			if (!class_exists($class))
			{
				continue;
			}
			$object = new $class;

			// Get the validation result and save it in the $response array
			$response['subscription_custom_validation'][$item->slug] = $object->validatePerSubscription($item, $subcustom);

			if(is_null($response['subscription_custom_validation'][$item->slug]))
			{
				unset($response['subscription_custom_validation'][$item->slug]);
			}
			elseif(!$item->allow_empty)
			{
				$response['isValid'] = $response['isValid'] && $response['subscription_custom_validation'][$item->slug];
			}
		}

		// Update the master "valid" reponse. If one of the fields is invalid,
		// the entire plugin's result is invalid (the form should not be submitted)
		$response['valid'] = $response['isValid'];

		return $response;
	}

	public function onValidateSubscriptionPrice($data)
	{
		$response = null;

		// Make sure we have a subscription level ID
		if($data->id <= 0)
		{
			return $response;
		}

		// Load field definitions
		$items = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
			->enabled(1)
			->filter_order('ordering')
			->filter_order_Dir('ASC')
			->getItemList(true);

		// If there are no custom fields return true (all valid)
		if(empty($items)) return $response;

		$response = 0;

		// Loop through each custom field
		foreach($items as $item) {
			// Make sure it's supposed to be shown in the particular level
			if (in_array($item->show, array('level','notlevel')))
			{
				if(is_null($data->id)) continue;

				$fieldlevels = explode(',', $item->akeebasubs_level_id);

				if (($item->show == 'level') && !in_array($data->id, $fieldlevels))
				{
					continue;
				}
				elseif (($item->show == 'notlevel') && in_array($data->id, $fieldlevels))
				{
					continue;
				}
			}

			// Make sure there is a validation method for this type of field
			$type = $item->type;
			$class = 'AkeebasubsCustomField' . ucfirst($type);

			if (!class_exists($class))
			{
				continue;
			}
			$object = new $class;

			// Get the validation result and save it in the $response array
			$res = $object->validatePrice($item, $data);
			if(!is_null($res))
			{
				$response += $res;
			}
		}

		return $response;
	}

	public function onValidateSubscriptionLength($data)
	{
		$response = null;

		// Make sure we have a subscription level ID
		if($data->id <= 0)
		{
			return $response;
		}

		// Load field definitions
		$items = FOFModel::getTmpInstance('Customfields','AkeebasubsModel')
			->enabled(1)
			->filter_order('ordering')
			->filter_order_Dir('ASC')
			->getItemList(true);

		// If there are no custom fields return true (all valid)
		if(empty($items)) return $response;

		$response = 0;

		// Loop through each custom field
		foreach($items as $item) {
			// Make sure it's supposed to be shown in the particular level
			if (in_array($item->show, array('level','notlevel')))
			{
				if(is_null($data->id)) continue;

				$fieldlevels = explode(',', $item->akeebasubs_level_id);

				if (($item->show == 'level') && !in_array($data->id, $fieldlevels))
				{
					continue;
				}
				elseif (($item->show == 'notelevel') && in_array($data->id, $fieldlevels))
				{
					continue;
				}
			}

			// Make sure there is a validation method for this type of field
			$type = $item->type;
			$class = 'AkeebasubsCustomField' . ucfirst($type);

			if (!class_exists($class))
			{
				continue;
			}
			$object = new $class;

			// Get the validation result and save it in the $response array
			$res = $object->validateLength($item, $data);
			if(!is_null($res))
			{
				$response += $res;
			}
		}

		return $response;
	}

	private function _loadFields()
	{
		$this->fieldTypes = array();

		JLoader::import('joomla.filesystem.folder');
		$basepath = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/customfields';
		$files = JFolder::files($basepath, '.php');
		foreach($files as $file)
		{
			if ($file === 'abstract.php')
			{
				continue;
			}

			require_once $basepath.'/'.$file;
			$type = substr($file, 0, -4);
			$class = 'AkeebasubsCustomField' . ucfirst($type);
			if (class_exists($class))
			{
				$this->fieldTypes[] = $type;
			}
		}
	}
}