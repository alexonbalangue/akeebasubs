<?php
/**
 *  @package	akeebasubs
 *  @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
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

include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/version.php';
include_once JPATH_LIBRARIES.'/fof/include.php';
if(!defined('FOF_INCLUDED')) return;
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/format.php';
require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';

$layout = $params->get('layout','awesome');
$ids = $params->get('ids',array());

$config = array(
	'option'	=> 'com_akeebasubs',
	'view'		=> 'levels',
	'layout'	=> $layout,
	'input'		=> array(
		'savestate'	=> 0,
		'limit'		=> 0,
		'limitstart'=> 0,
		'no_clear'	=> true,
		'only_once'	=> true,
		'task'		=> 'browse',
		'filter_order' => 'ordering',
		'filter_order_Dir' => 'ASC',
		'enabled'	=> 1,
		'caching'	=> false
	)
);
if(!empty($ids)) $config['input']['id'] = $ids;

//$fp = fopen(JPATH_SITE.'/logs/backtrace.txt', 'at');fwrite($fp, "\n\n\n".  str_repeat('*', 78)."\n\n\n");fclose($fp);
FOFDispatcher::getTmpInstance('com_akeebasubs', 'levels', $config)->dispatch();
//$fp = fopen(JPATH_SITE.'/logs/backtrace.txt', 'at');fwrite($fp, "\n\n\n".  str_repeat('~', 78)."\n\n\n");fclose($fp);