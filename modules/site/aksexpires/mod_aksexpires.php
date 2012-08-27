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

// TODO : Put this in a shared file and load with JLoader
$jlang = JFactory::getLanguage();
$jlang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
$jlang->load('com_akeebasubs', JPATH_SITE, $jlang->getDefault(), true);
$jlang->load('com_akeebasubs', JPATH_SITE, null, true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);

if(JFactory::getUser()->guest) {
	echo '&nbsp;';
} else {
	$list = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
		->user_id(JFactory::getUser()->id)
		->enabled(1)
		->getList();

	if(empty($list)) {
		echo "&nbsp;";
		return;
	}

	jimport('joomla.utilities.date');

	$expires = 0;
	$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
	foreach($list as $s) {
		if(!preg_match($regex, $s->publish_down)) {
			$s->publish_down = '2037-01-01';
		}
		$ed = new JDate($s->publish_down);
		$ex = $ed->toUnix();
		if($ex > $expires) $expires = $ex;
	}

	$ed = new JDate($expires);
	echo JText::sprintf('MOD_AKSEXPIRES_EXPIRESON', $ed->format('d/m/Y', true));
}