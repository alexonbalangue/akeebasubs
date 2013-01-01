<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

/**
 * Abstract base class for custom fields
 * 
 * @since 2.6.0
 */
abstract class AkeebasubsCustomFieldAbstract
{
	/** The field type, set by the constructor */
	protected $fieldType = null;
	
	/**
	 * Create a custom field object instance
	 * 
	 * @param   array                       $config  Custom configuration parameters
	 */
	public function __construct(array $config = array())
	{
		// Set up the field type
		if (!isset($config['field_type']))
		{
			if (empty($this->fieldType))
			{
				$parts = FOFInflector::explode(get_called_class());
				$type = strtolower(array_pop($parts));
				$config['field_type'] = $type;
			}
			else
			{
				$config['field_type'] = $this->fieldType;
			}
		}
		$this->fieldType = $config['field_type'];
	}
	
	/**
	 * Creates a custom field array which will be used by the renderer to be
	 * shown on the front- or back-end page
	 * 
	 * @param	AkeebasubsTableCustomfield	$item	     A custom field definition
	 * @param	array						$cache	     The values cache
	 * @param	stdClass					$userparams  User parameters
	 */
	abstract public function getField($item, $cache, $userparams);
	
	/**
	 * Create the necessary Javascript and add it to the page
	 * 
	 * @param	AkeebasubsTableCustomfield	$item	The item to render the Javascript for
	 */
	abstract public function getJavascript($item);
	
	/**
	 * Validate a field
	 * 
	 * @param AkeebasubsTableCustomfield	$item	The custom field to validate
	 * @param array							$custom	The custom fields' values array
	 * 
	 * @return int 1 if the field is valid, 0 otherwise
	 */
	abstract public function validate($item, $custom);
}