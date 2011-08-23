<?php
/**
 * @package		mod_akeebasubs
 * @copyright 	Copyright (c) 2011 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */
 
echo KFactory::get('admin::mod.akeebasubs.view', array(
	'params'  => $params,
	'module'  => $module,
	'attribs' => $attribs
))->display();