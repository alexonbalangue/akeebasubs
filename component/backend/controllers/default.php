<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerDefault extends ComDefaultControllerDefault 
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		$command = KFactory::get('admin::com.akeebasubs.command.validate');
		$this->getCommandChain()->enqueue($command);
	}
	
	public function _initialize(KConfig $config) {
		$config->append(array(
			'behaviors' => array('discoverable','executable','commandable','editable')
		));
		parent::_initialize($config);
	}	
}