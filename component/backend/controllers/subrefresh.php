<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerSubrefresh extends ComAkeebasubsControllerDefault 
{
	public function __construct(KConfig $config)
	{
		$config->append(array(
			'model'		=> KFactory::get('admin::com.akeebasubs.model.subscriptions')
		));
		
		parent::__construct($config);
		
		$this->registerCallback('before.browse', array($this, '_denyAccess'));
		$this->registerCallback('before.read', array($this, '_denyAccess'));
		$this->registerCallback('before.edit', array($this, '_denyAccess'));
		$this->registerCallback('before.add', array($this, '_denyAccess'));
		$this->registerCallback('before.delete', array($this, '_denyAccess'));
	}

	public function _actionProcess()
	{
		// Run the plugin events on the list
		$this->getModel()->set('forceoffset', KRequest::get('post.forceoffset','int') );
		$this->getModel()->set('forcelimit', KRequest::get('post.forcelimit','int') );
		$list = $this->getModel()->refresh(1)->getList()->subscriptionRefresh();

		$response = array(
			'total'	=> $this->getModel()->getTotal(),
			'processed'	=> count($this->getModel()->getList())
		);
		
		echo json_encode($response);
		
		// Return
		KFactory::get('lib.joomla.application')->close();
	}
	
	public function _denyAccess()
	{
		return false;
	}
}