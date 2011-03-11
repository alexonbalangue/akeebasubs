<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerLevel extends ComAkeebasubsControllerDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this->registerCallback('before.browse', array($this, '_beforeBrowse'));
		$this->registerCallback('before.read', array($this, '_beforeRead'));
		$this->registerCallback('before.save', array($this, '_beforeSave'));
	}
	
	public function _beforeBrowse(KCommandContext $context)
	{
		// Make sure we only show active levels based on their ordering.
		$this->getModel()->getState()->enabled = 1;
		$this->getModel()->getState()->order = 'ordering';		
	}

	public function _beforeRead(KCommandContext $context)
	{
		$view = $this->getView(); 
		// Get the user model and load the user data
		$view->assign('userparams',
			KFactory::get('site::com.akeebasubs.model.users')
				->user_id(KFactory::get('lib.joomla.user')->id)
				->getMergedData()
		);
		// Load any cached user supplied information
		$view->assign('cache',
			KFactory::get('site::com.akeebasubs.model.subscribes')
				->getData()
		);
	}
	
	/**
	 * Disallow saving from public front-end
	 */
	public function _beforeSave(KCommandContext $context)
	{
		return false;
	}
	
	protected function _actionEdit()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}
	
	protected function _actionAdd()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}
	
	protected function _actionDelete()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}	
} 
