<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsDispatcher extends FOFDispatcher
{
	public function onBeforeDispatch() {
		$result = parent::onBeforeDispatch();

		if($result) {
			// Merge the language overrides
			$paths = array(JPATH_ROOT, JPATH_ADMINISTRATOR);
			$jlang = JFactory::getLanguage();
			$jlang->load($this->component, $paths[0], 'en-GB', true);
			$jlang->load($this->component, $paths[0], null, true);
			$jlang->load($this->component, $paths[1], 'en-GB', true);
			$jlang->load($this->component, $paths[1], null, true);

			$jlang->load($this->component.'.override', $paths[0], 'en-GB', true);
			$jlang->load($this->component.'.override', $paths[0], null, true);
			$jlang->load($this->component.'.override', $paths[1], 'en-GB', true);
			$jlang->load($this->component.'.override', $paths[1], null, true);
			// Live Update translation
			$jlang->load('liveupdate', JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'liveupdate', 'en-GB', true);
			$jlang->load('liveupdate', JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'liveupdate', $jlang->getDefault(), true);
			$jlang->load('liveupdate', JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'liveupdate', null, true);

			// Load Akeeba Strapper
			if(!defined('AKEEBASUBSMEDIATAG')) {
				$staticFilesVersioningTag = md5(AKEEBASUBS_VERSION.AKEEBASUBS_DATE);
				define('AKEEBASUBSMEDIATAG', $staticFilesVersioningTag);
			}
			include_once JPATH_ROOT.'/media/akeeba_strapper/strapper.php';
			AkeebaStrapper::$tag = AKEEBASUBSMEDIATAG;
			AkeebaStrapper::bootstrap();
			AkeebaStrapper::jQueryUI();
			AkeebaStrapper::addCSSfile('media://com_akeebasubs/css/backend.css');
			AkeebaStrapper::addJSfile('media://com_akeebasubs/js/backend.js');
		}

		return $result;
	}

	public function dispatch() {
		// Handle Live Update requests
		if(!class_exists('LiveUpdate')) {
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/liveupdate/liveupdate.php';
			if(($this->input->getCmd('view','') == 'liveupdate')) {
				LiveUpdate::handleRequest();
				return;
			}
		}

		parent::dispatch();
	}
}