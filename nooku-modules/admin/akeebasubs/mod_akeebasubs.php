<?php
/**
 * @package		mod_akeebasubs
 * @copyright 	Copyright (c) 2011 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */

echo KFactory::get('mod://admin/akeebasubs.view')
	->module($module)
	->params($params)
	->attribs($attribs)
	->display();