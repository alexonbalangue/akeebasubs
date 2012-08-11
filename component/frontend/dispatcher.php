<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsDispatcher extends FOFDispatcher
{
	private $allowedViews = array(
		'levels','messages','subscribes','subscriptions','validates','callbacks','userinfos'
	);
	
	public function __construct($config = array()) {
		parent::__construct($config);
		
		$this->defaultView = 'levels';
	}
	
	public function onBeforeDispatch() {
		if($result = parent::onBeforeDispatch()) {
			// Merge the language overrides
			$paths = array(JPATH_ADMINISTRATOR, JPATH_ROOT);
			$jlang = JFactory::getLanguage();
			$jlang->load($this->component, $paths[0], 'en-GB', true);
			$jlang->load($this->component, $paths[0], null, true);
			$jlang->load($this->component, $paths[1], 'en-GB', true);
			$jlang->load($this->component, $paths[1], null, true);
			
			$jlang->load($this->component.'.override', $paths[0], 'en-GB', true);
			$jlang->load($this->component.'.override', $paths[0], null, true);
			$jlang->load($this->component.'.override', $paths[1], 'en-GB', true);
			$jlang->load($this->component.'.override', $paths[1], null, true);
			
			// Load Akeeba Strapper
			if(!defined('AKEEBASUBSMEDIATAG')) {
				$staticFilesVersioningTag = md5(AKEEBASUBS_VERSION.AKEEBASUBS_DATE);
				define('AKEEBASUBSMEDIATAG', $staticFilesVersioningTag);
			}
			include_once JPATH_ROOT.'/media/akeeba_strapper/strapper.php';
			AkeebaStrapper::$tag = AKEEBASUBSMEDIATAG;
			AkeebaStrapper::bootstrap();
			AkeebaStrapper::jQueryUI();
			AkeebaStrapper::addCSSfile('media://com_akeebasubs/css/frontend.css');
			
			// Load helpers
			require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';

			// Default to the "levels" view
			$view = FOFInput::getCmd('view',$this->defaultView, $this->input);
			if(empty($view) || ($view == 'cpanel')) {
				$view = 'levels';
			}
			
			// Set the view, if it's allowed
			FOFInput::setVar('view',$view,$this->input);
			if(!in_array(FOFInflector::pluralize($view), $this->allowedViews)) $result = false;
		}
		
		return $result;
	}
	
	public function getTask($view) {
		$task = parent::getTask($view);
		
		switch($view) {
			case 'level':
				if($task == 'add') $task = 'read';
		}
		
		return $task;
	}
}