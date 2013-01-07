<?php
/**
 * @package    Tracktime
 * @copyright  Copyright (c)2011-2013 Davide Tampellini
 * @license    GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class JFormFieldMailtemplate extends JFormField
{
	function getInput()
	{
		$db = JFactory::getDbo();

		$noopt[] = array('value' => '', 'text' => ' - Select - ');

		$query = $db->getQuery(true)
					->select('id_templates as value, te_name as text')
					->from('#__tracktime_templates')
					->where('te_enabled = 1')
					->where('te_type = '.$db->quote('HTML'))
					->where('te_group = '.$db->quote('INVOICE'))
					->order('te_default DESC, te_name ASC');
		$options = array_merge( $noopt, $db->setQuery($query)->loadAssocList());

		return JHTML::_('select.genericlist', $options, 'jform[params][mail_template]', '', 'value', 'text', $this->value);
	}
}