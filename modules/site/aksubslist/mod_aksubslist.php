<?php
/**
 *  @package	akeebasubs
 *  @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 *  @license	GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 *  @version 	$Id$
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// no direct access
defined('_JEXEC') or die('');

// Make sure Koowa is running
if(!defined('KOOWA')) {
	return;
}

echo KFactory::tmp('site::mod.aksubslist.html', array(
	'params'  => $params,
	'module'  => $module,
	'attribs' => $attribs
))->display();