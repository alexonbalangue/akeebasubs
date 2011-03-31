<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerValidate extends ComAkeebasubsControllerDefault
{
	public function __construct(KConfig $config)
	{
		$config->append(array(
			'model'		=> KFactory::get('site::com.akeebasubs.model.subscribes')
		));
		
		parent::__construct($config);
		
		$this->registerCallback('before.browse', array($this, '_denyAccess'));
		$this->registerCallback('before.edit', array($this, '_denyAccess'));
		$this->registerCallback('before.add', array($this, '_denyAccess'));
		$this->registerCallback('before.delete', array($this, '_denyAccess'));
	}
	
	public function _actionRead(KCommandContext $context)
	{
		$data = $this->getModel()
			->set('action','validate')
			->getValidation();
		echo json_encode($data);
		
		KFactory::get('lib.joomla.application')->close();
	}
	
	public function _denyAccess()
	{
		return false;
	}
}