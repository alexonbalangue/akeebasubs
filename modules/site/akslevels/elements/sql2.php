<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die('Restricted Access');

if(!class_exists('ASElementBase')) {
        if(version_compare(JVERSION,'1.6.0','ge')) {
                class ASElementBase extends JFormField {
                        public function getInput() {}
                }               
        } else {
                class ASElementBase extends JElement {}
        }
}

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
}

if(version_compare(JVERSION,'1.6.0','ge')) {
        class JFormFieldSQL2 extends ASElementSQL2 {}
} else {
        class JElementSQL2 extends ASElementSQL2 {}                
}