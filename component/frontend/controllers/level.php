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
		
		$this->registerCallback('before.edit', array($this, '_denyAccess'));
		$this->registerCallback('before.add', array($this, '_denyAccess'));
		$this->registerCallback('before.delete', array($this, '_denyAccess'));
			
	}
	
	public function _beforeBrowse(KCommandContext $context)
	{
		// Make sure we only show active levels based on their ordering.
		$this->getModel()->getState()->enabled = 1;
		$this->getModel()->getState()->order = 'ordering';		
	}

	public function _beforeRead(KCommandContext $context)
	{
		// Make sure Joomla! loads mooTools
		JHTML::_('behavior.mootools');
	
		$view = $this->getView();
		
		// Fetch the subscription slug from page parameters
		$params	= KFactory::get('lib.joomla.application')->getPageParameters();
		$slug	= $params->get('slug','');
		if(!empty($slug)) {
			$this->getModel()->slug($slug);
		}

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
		// Get the validation results
		if(empty($slug)) {
			$slug = KRequest::get('get.slug','cmd',0);
		}
		$vModel = KFactory::get('site::com.akeebasubs.model.subscribes');
		$vModel->getState()->setData($view->cache);
		$vModel->slug($slug);
		$view->assign('validation',
				$vModel->getValidation()
		);
	}
	
	public function _denyAccess()
	{
		return false;
	}
	
} 
