<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerSubscribe extends ComAkeebasubsControllerDefault
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		$this->registerCallback('before.save', array($this, '_beforeSave'));
	}
	
	protected function _actionValidate(KCommandContext $context)
	{
		// Set the model action to "validate" so that the JSON view knows what to do
		$model = $this->getModel();
		$model->set('action','validate');
		// Return nada. Let the view sort it out (which is wrong, because the view now
		// partially becomes a controller).
		return true;
	}
	
	protected function _actionAdd(KCommandContext $context)
	{
		$result = $this->getModel()->createNewSubscription();

		if($result) {
			// Show the auto-submitting form to the user
			$view = $this->getView();
			$view->setLayout('form');
			return $view->display();
		} else {
			// Redirect to the level page
			$url = 'index.php?option=com_akeebasubs&view=level&id='.$this->getModel()->get('id','int',0);
			$this->setRedirect($url);
			return false;
		}
	}
	
	protected function _actionCallback(KCommandContext $context)
	{
		$result = $this->getModel()->runCallback();
		if($result) die('Success');
		die('Failed');
	}
	
	/**
	 * Disallow saving from public front-end
	 */
	public function _beforeSave(KCommandContext $context)
	{
		return false;
	}
	
	protected function _actionBrowse()
	{
		JError::raiseWarning(403, 'Forbidden');
		return $this;
	}
	
	protected function _actionEdit()
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