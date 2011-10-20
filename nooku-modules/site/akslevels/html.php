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

class ModAkslevelsHtml extends ModDefaultHtml
{
	public function display()
	{
		// TODO : Put this in a shared file and load with JLoader
		$jlang = JFactory::getLanguage();
		$jlang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
		$jlang->load('com_akeebasubs', JPATH_SITE, $jlang->getDefault(), true);
		$jlang->load('com_akeebasubs', JPATH_SITE, null, true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);

		// TODO : Put this in a shared file and load with JLoader
		KIdentifier::map('com://site/akeebasubs.model.levels',			'com://admin/akeebasubs.model.levels');
		KIdentifier::map('com://site/akeebasubs.model.configs',			'com://admin/akeebasubs.model.configs');

		// Otherwise, the stylesheet is not loaded :(
		JFactory::getDocument()->addStylesheet(JURI::base().'media/com_akeebasubs/css/frontend.css');
		
		$layout = $this->params->get('layout','awesome');

		$controller = KFactory::get('com://site/akeebasubs.controller.levels' );

		$ids = $this->params->get('ids');
		if(!empty($ids)) {
			return $controller
				->id($ids)
				->view('levels')
				->layout($layout)
				->limit(0)
				->display();
		} else {
			return $controller
				->view('levels')
				->layout($layout)
				->limit(0)
				->display();
		}
	}
}