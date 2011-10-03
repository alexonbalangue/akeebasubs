<?php

/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerBehaviorEditable extends KControllerBehaviorEditable
{
	/**
	 * Save action
	 * 
	 * This function wraps around the edit or add action. If the model state is
	 * unique a edit action will be executed, if not unique an add action will be
	 * executed.
	 * 
	 * This function also sets the redirect to the referrer.
	 *
	 * @param   KCommandContext	A command context object
	 * @return 	KDatabaseRow 	A row object containing the saved data
	 */
	protected function _actionSave(KCommandContext $context)
	{
		$data = parent::_actionSave($context);
		
		$url = clone KRequest::referrer();
		$url->query['view'] = KInflector::pluralize($url->query['view']);
		unset($url->query[$data->getIdentityColumn()]);
		
		$this->setRedirect($url);
		
		return $data;
	}

	/**
	 * Apply action
	 * 
	 * This function wraps around the edit or add action. If the model state is
	 * unique a edit action will be executed, if not unique an add action will be
	 * executed.
	 * 
	 * This function also sets the redirect to the current url
	 *
	 * @param	KCommandContext	A command context object
	 * @return 	KDatabaseRow 	A row object containing the saved data
	 */
	protected function _actionApply(KCommandContext $context)
	{
		$data = parent::_actionApply($context);
		
		$action = $this->getModel()->getState()->isUnique() ? 'edit' : 'add';
		$url = clone KRequest::referrer();
		
		if($this->getModel()->getState()->isUnique()) {
			$states = $this->getModel()->getState()->getData(true);
			
			foreach($states as $key => $value) {
		        $url->query[$key] = $data->get($key);
		    }
		}
		else {
			$url->query[$data->getIdentityColumn()] = $data->get($data->getIdentityColumn());
		}
		
		$this->setRedirect($url);
		
		return $data;
	}
	
	/**
	 * Cancel action
	 * 
	 * This function will unlock the row(s) and set the redirect to the referrer
	 *
	 * @param	KCommandContext	A command context object
	 * @return 	KDatabaseRow	A row object containing the data of the cancelled object
	 */
	protected function _actionCancel(KCommandContext $context)
	{
		$data = parent::_actionCancel($context);
		
		$url = clone KRequest::referrer();
		$url->query['view'] = KInflector::pluralize($url->query['view']);
		unset($url->query[$data->getIdentityColumn()]);
		
		$this->setRedirect($url);
	
		return $data;
	}
}