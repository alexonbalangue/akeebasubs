<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

/**
 * Our main element class, creating a multi-select list out of an SQL statement
 */
class JFormFieldSQL3 extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'SQL3';
	
	function getInput()
	{
		$db			= JFactory::getDBO();
		$db->setQuery($this->element['query']);
		$key = (string) ($this->element['key_field'] ? $this->element['key_field'] : 'value');
		$val = (string) ($this->element['value_field'] ? $this->element['value_field'] : $this->name);
		$objectList = $db->loadObjectList();
		if(!is_array($objectList)) $objectList = array();
		$defaultEntry = array();
		$defaultEntry[$key] = 0;
		$defaultEntry[$val] = '&mdash;&mdash;&mdash;';
		$defaultList = array((object)$defaultEntry);
		$objectList = array_merge($defaultList, $objectList);
		return JHTML::_('select.genericlist',  $objectList, $this->name.'[]', 'class="inputbox" multiple="multiple" size="5"', $key, $val, $this->value, $this->id);
	}
}