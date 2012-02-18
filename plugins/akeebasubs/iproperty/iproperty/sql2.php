<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

/*
 * This trick allows us to extend the correct class, based on whether it's Joomla! 1.5 or 1.6
 */
if(!class_exists('ASElementBase')) {
        if(version_compare(JVERSION,'1.6.0','ge')) {
                class ASElementBase extends JFormField {
                        public function getInput() {}
                }               
        } else {
                class ASElementBase extends JElement {}
        }
}

/**
 * Our main element class, creating a multi-select list out of an SQL statement
 */
class ASElementSQL2 extends ASElementBase
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'SQL2';

	function fetchElement($name, $value, &$node, $control_name)
	{
		$db			= & JFactory::getDBO();
		$db->setQuery($node->attributes('query'));
		$key = ($node->attributes('key_field') ? $node->attributes('key_field') : 'value');
		$val = ($node->attributes('value_field') ? $node->attributes('value_field') : $name);
		return JHTML::_('select.genericlist',  $db->loadObjectList(), ''.$control_name.'['.$name.'][]', 'class="inputbox" multiple="multiple" size="5"', $key, $val, $value, $control_name.$name);
	}
	
	function getInput()
	{
		$db			= & JFactory::getDBO();
		$db->setQuery($this->element['query']);
		$key = ($this->element['key_field'] ? $this->element['key_field'] : 'value');
		$val = ($this->element['value_field'] ? $this->element['value_field'] : $this->name);
		return JHTML::_('select.genericlist',  $db->loadObjectList(), $this->name.'[]', 'class="inputbox" multiple="multiple" size="5"', $key, $val, $this->value, $this->id);
	}
}

/*
 * Part two of our trick; we define the proper element name, depending on whether it's Joomla! 1.5 or 1.6
 */
if(version_compare(JVERSION,'1.6.0','ge')) {
        class JFormFieldSQL2 extends ASElementSQL2 {}
} else {
        class JElementSQL2 extends ASElementSQL2 {}                
}