<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsModelConfigs extends KModelAbstract
{
	/** @var array Configuration options definitions, read from config.json */
	private $definitions = array();
	
	/** @var array Default values of different configuration options, deducted from the JSON file */
	private $defaultConfig = array(
	);
	
	/**
	 * Initialises the class with the parameter definitions found in config.json in the
	 * extension's root and also populates the $defaultConfig class variable.
	 */
	public function _initialize(KConfig $config)
	{
		jimport('joomla.filesystem.file');
		$filename = dirname(__FILE__).'/../config.json';
		if(!JFile::exists($filename)) {
			$filename = JPATH_ADMINISTRATOR.'/components/com_akeebasubs/config.json';
		}
		$json_data = JFile::read($filename);
		
		if($json_data !== false) {
			$this->definitions = json_decode($json_data);
		}
		if(!empty($this->definitions)) {
			// Get the defaults from the definitions
			foreach($this->definitions as $sectionTitle => $sectionData) {
				if(!empty($sectionData->options)) {
					foreach($sectionData->options as $key => $params) {
						if(!empty($params->default)) {
							$this->defaultConfig[$key] = $params->default;
						} else {
							$this->defaultConfig[$key] = '';
						}
					}
				}
			}
		}
				
		parent::_initialize($config);
	}
	
	/**
	 * Returns the component configuration settings as a KConfig instance
	 */
	public function getConfig()
	{
		$component =& JComponentHelper::getComponent( 'com_akeebasubs' );
		$params = new JParameter($component->params);
		$params = $params->toArray();
		
		$config = array_key_exists('params',$params) ? $params['params'] : '';
		if(!empty($config)) {
			$config = @json_decode($config, true);
		} else {
			$config = null;
		}
		
		if(empty($config)) {
			$config = array();
		}
		
		$config = array_merge($this->defaultConfig, $config);
		
		$config = new KConfig($config);
		return $config;
	}
	
	/**
	 * Saves the component parameters in the database
	 * 
	 * @todo Maybe use our own table instead of Joomla!'s #__components/#__parameters table?
	 */
	public function saveConfig($data)
	{
		// Convert data to array
		if($data instanceof KConfig) {
			$data = $data->toArray();
		}

		// Get field - filter map
		$map = array();
		if(!empty($this->definitions)) {
			foreach($this->definitions as $sectionTitle => $sectionData) {
				if(!empty($sectionData->options)) {
					foreach($sectionData->options as $key => $params) {
						if(!empty($params->filter)) {
							$map[$key] = $params->filter;
						} else {
							// Yikes! Should never happen! Throw a warning.
							trigger_error("Field $key uses implicit raw filtering", E_WARNING);
							$map[$key] = 'raw';
						}
					}
				}
			}
		}
		
		// Data validation
		$temp = array();
		foreach($data as $key => $value) {
			// Skip inexistent configuration keys
			if(!array_key_exists($key, $map)) continue;
			// Data validation
			$temp[$key] = KFactory::tmp('lib.koowa.filter.'.$map[$key])->sanitize($value);
		}
		$data = $temp;
		
		$component =& JComponentHelper::getComponent( 'com_akeebasubs' );
		$params = new JParameter($component->params);
		$cparams = $params->toArray();
		
		$config = array_key_exists('params', $params) ? $cparams['params'] : '';
		if(!empty($config)) {
			$config = @json_decode($params);
		} else {
			$config = null;
		}
		
		if(empty($config)) {
			$config = array();
		}
		
		$config = array_merge($this->defaultConfig, $config, $data);
		$config = json_encode($config);
		
		$params->set('params', $config);
		$serialized = $params->toString();
		
		$db =& JFactory::getDBO();
		if( version_compare(JVERSION,'1.6.0','ge') ) {
			// Joomla! 1.6
			$sql = 'UPDATE `#__extensions` SET `params` = '.$db->Quote($serialized).' WHERE '.
				"`element` = 'com_akeebasubs' AND `type` = 'component'";
		} else {
			// Joomla! 1.5
			$sql = 'UPDATE `#__components` SET `params` = '.$db->Quote($serialized).' WHERE '.
				"`option` = 'com_akeebasubs' AND `parent` = 0 AND `menuid` = 0";
		}
		
		$db->setQuery($sql);
		return $db->query() !== false;
	}
	
	public function getDefinitions()
	{
		return $this->definitions;
	}
}
