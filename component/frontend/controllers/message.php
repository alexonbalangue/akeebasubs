<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerMessage extends ComAkeebasubsControllerDefault
{

	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this->registerCallback('before.browse', array($this, '_denyAccess'));
		$this->registerCallback('before.edit', array($this, '_denyAccess'));
		//$this->registerCallback('before.add', array($this, '_denyAccess'));
		$this->registerCallback('before.delete', array($this, '_denyAccess'));
	}
	
	public function _actionAdd(KCommandContext $context)
	{
		$id = KRequest::get('get.id','int','-1');
		$layout = KRequest::get('get.layout','cmd','');
		if($id > 0) {
			$this->setRedirect(JRoute::_('index.php?option=com_akeebasubs&view=message&id='.$id.'&layout='.$layout));
			return false;
		}
		
		return false;
	}
	
	public function _denyAccess()
	{
		return false;
	}
} 
