<?php

/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */
// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsDispatcher extends FOFDispatcher
{

	public function onBeforeDispatch()
	{
		// You can't fix stupid… but you can try working around it
		if ((!function_exists('json_encode')) || (!function_exists('json_decode')))
		{
			require_once JPATH_ADMINISTRATOR . '/components/' . $this->component . '/helpers/jsonlib.php';
		}
		
		$result = parent::onBeforeDispatch();

		if ($result)
		{
			// Merge the language overrides
			$paths	 = array(JPATH_ROOT, JPATH_ADMINISTRATOR);
			$jlang	 = JFactory::getLanguage();
			$jlang->load($this->component, $paths[0], 'en-GB', true);
			$jlang->load($this->component, $paths[0], null, true);
			$jlang->load($this->component, $paths[1], 'en-GB', true);
			$jlang->load($this->component, $paths[1], null, true);

			$jlang->load($this->component . '.override', $paths[0], 'en-GB', true);
			$jlang->load($this->component . '.override', $paths[0], null, true);
			$jlang->load($this->component . '.override', $paths[1], 'en-GB', true);
			$jlang->load($this->component . '.override', $paths[1], null, true);

			// Load Akeeba Strapper
			if (!defined('AKEEBASUBSMEDIATAG'))
			{
				$staticFilesVersioningTag = md5(AKEEBASUBS_VERSION . AKEEBASUBS_DATE);
				define('AKEEBASUBSMEDIATAG', $staticFilesVersioningTag);
			}
			include_once JPATH_ROOT . '/media/akeeba_strapper/strapper.php';
			AkeebaStrapper::$tag = AKEEBASUBSMEDIATAG;
			AkeebaStrapper::bootstrap();
			AkeebaStrapper::jQueryUI();
			AkeebaStrapper::addCSSfile('media://com_akeebasubs/css/backend.css');
			AkeebaStrapper::addJSfile('media://com_akeebasubs/js/backend.js');
		}

		return $result;
	}
}