<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * Subscriptions importer model. Adapted from NinjaBoard's import functionality,
 * originally written by Stian Didriksen and the NinjaForge team. Modified to fit
 * the purposes of Akeeba Subscriptions by Nicholas K. Dionysopoulos.
 * 
 * Thank you, Stian, for writting this awesome code and making it GPLv3!
 *
 */
class ComAkeebasubsModelTools extends KModelTable
{
	protected $list = array();
	
	/**
	 * Constructor
	 *
	 * @param 	object 	An optional KConfig object with configuration options
	 */
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		//Get a list over the default converters shipping with Akeeba Subscriptions
		$exclude	= array('abstract.php', 'exception.php', 'interface.php', 'index.html');
		$converters = JFolder::files(JPATH_COMPONENT_ADMINISTRATOR.'/databases/converters/', '.', false, false, $exclude);
		foreach($converters as $name)
		{
			$name		= str_replace('.php', '', $name);
			$converter	= KFactory::get('com://admin/akeebasubs.databases.converters.'.$name);
			if($converter->canConvert()) $this->list[$name] = $converter;
		}

		$this->_total = count($this->list);

		$this->_state
					->insert('import', 'cmd', 'demo')
					->insert('limit', 'int');
					
		$this->areYouKiddingMe = 'Yes, it is crazy';
	}
	
	/**
	 * Add converter to the list
	 *
	 * @param  string	 Name of the converter
	 * @param  interface ComAkeebasubsDatabaseConvertersInterface
	 * @return $this
	 */
	public function addConverter($name, $converter)
	{
		$this->list[$name] = $converter;
		
		return $this;
	}

	/**
	 * Get a list over converters
	 *
	 * @return array
	 */
	public function getList()
	{
		//Sort list by key
		if(!empty($this->list)) ksort($this->list);
	
		return $this->list;
	}

	/**
	 * Get a single converter
	 *
	 * @return interface ComAkeebasubsDatabaseConvertersInterface
	 */
	public function getItem()
	{
		if(!isset($this->list[$this->_state->import])) return false;
	
		return $this->list[$this->_state->import];
	}
}