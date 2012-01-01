<?php
/**
 * @package AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 * @version $Id: header.php 218 2011-06-02 10:45:00Z nikosdion $
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
 * Our main element class
 */
class ASElementHeader extends ASElementBase
{
	/**
	* Element name
	*
	* @access	protected
	* @var		string
	*/
	var	$_name = 'header';

	function fetchElement($name, $value, &$node, $control_name)
	{
		return '<hr/>';
	}
	
	function getInput()
	{
		return '';
	}
}

/*
 * Part two of our trick; we define the proper element name, depending on whether it's Joomla! 1.5 or 1.6
 */
if(version_compare(JVERSION,'1.6.0','ge')) {
        class JFormFieldHeader extends ASElementHeader {}
} else {
        class JElementHeader extends ASElementHeader {}                
}