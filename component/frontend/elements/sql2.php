<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

/**
 * Our main element class, creating a multi-select list out of an SQL statement
 */
class JFormFieldSQL2 extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'SQL2';
	
	function getInput()
	{
		$db			= JFactory::getDBO();
		$db->setQuery($this->element['query']);
		$key = ($this->element['key_field'] ? $this->element['key_field']->data() : 'value');
		$val = ($this->element['value_field'] ? $this->element['value_field']->data() : $this->name);
		$nodes = $db->loadObjectList();
		array_unshift($nodes, (object)array($key=>'',$val=>JText::_('COM_AKEEBASUBS_SELECT_GENERIC')));
		return JHTML::_('select.genericlist',  $nodes, $this->name.'[]', 'class="inputbox" multiple="multiple" size="5"', $key, $val, $this->value, $this->id);
	}
}