<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id$
 */

defined('_JEXEC') or die();

/**
 * Get the ProjectFork categories and add 'Root' as default.
 */
class JFormFieldCategories extends JFormField
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'categories';
	
	function getInput()
	{
		$db	= JFactory::getDBO();
		$key = 'id';
		$val = 'title';
		$defaultOption = array(
			(object)array((string)$key=>'0',(string)$val=>'Root')
		);
		$db->setQuery('SELECT `' . $key . '`, `' . $val . '` FROM `#__categories` WHERE `extension` = "com_pfprojects"');
		$nodes = $db->loadObjectList();
		$nodes = array_merge($defaultOption, $nodes);
		if(version_compare(JVERSION, '3.0', 'lt')) {
			return JHTML::_('select.genericlist',  $nodes, $this->name.'[]', 'class="inputbox"', $key, $val, $this->value, $this->id);
		} else {
			return JHTML::_('select.genericlist',  $nodes, $this->name.'[]', '', $key, $val, $this->value, $this->id);
		}
		
	}
}