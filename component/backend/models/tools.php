<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelTools extends FOFModel
{
	public function __construct($config = array()) {
		// This is a dirty trick to avoid getting warning PHP messages by the
		// JDatabase layer
		$config['table'] = 'levels';
		
		parent::__construct($config);
		
		$this->scanConverters();
	}
	
	public function &getItemList($overrideLimits = false, $group = '')
	{
		return $this->list;
	}
	
	public function setId($id = null) {
		$this->id = $id;
	}
	
	public function &getItem($id = null) {
		$dummy = null;
		
		if(is_null($id)) $id = $this->id;
		
		if(!array_key_exists($id, $this->list)) return $dummy;
		
		return $this->list[$id];
	}
	
	private function scanConverters()
	{
		JLoader::import('joomla.filesystem.folder');
		$folder = JPATH_COMPONENT_ADMINISTRATOR.'/converter';
		$files = JFolder::files($folder, '.php', false, true, array('abstract.php','interface.php'));
		
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/converter/interface.php';
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/converter/abstract.php';
		
		foreach($files as $file) {
			$name = basename($file,'.php');
			$className = 'AkeebasubsConverter'.ucfirst($name);
			require_once $file;
			$o = new $className;
			if($o->canConvert()) {
				$this->list[$name] = $o;
			}
		}
		
		if(empty($this->list)) {
			$this->list = array();
		} else {
			ksort($this->list);
		}
	}
}