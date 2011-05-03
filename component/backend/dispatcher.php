<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsDispatcher extends ComDefaultDispatcher
{
    protected function _initialize(KConfig $config)
    {
		include_once JPATH_COMPONENT_ADMINISTRATOR.DS.'version.php';

		// Magic: merge the default translation with the current translation
		$jlang =& JFactory::getLanguage();
		$jlang->load('com_akeebasubs', JPATH_SITE, 'en-GB', true);
		$jlang->load('com_akeebasubs', JPATH_SITE, $jlang->getDefault(), true);
		$jlang->load('com_akeebasubs', JPATH_SITE, null, true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_akeebasubs', JPATH_ADMINISTRATOR, null, true);
		
		// I hate myself for doing this... but using map() didn't work for me!
		$view = JRequest::getCmd('view','');
		if(empty($view) || ($view == 'akeebasubs')) {
			$_GET['view'] = 'dashboard';
		}

        $config->append(array(
                'controller_default' => 'dashboard'
        ));
        parent::_initialize($config);
    }
       
}