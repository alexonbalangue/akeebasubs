<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsControllerSubscriptions extends FOFController
{
	public function browse($cachable = false) {
		// When groupbydate is set to 1 we force a JSON view which returns the
		// sales info (subscriptions, net amount) grouped by date. You can use
		// the since/until or other filter in the URL to filter the whole lot
		$groupbydate = $this->input->getInt('groupbydate',0);
		$groupbylevel = $this->input->getInt('groupbylevel',0);
		if(($groupbydate == 1) || ($groupbylevel == 1)) {
			if(JFactory::getUser()->guest) {
				return false;
			} else {
				$list = $this->getThisModel()
					->savestate(0)
					->limit(0)
					->limitstart(0)
					->getItemList();
				header('Content-type: application/json');
				echo json_encode($list);
				JFactory::getApplication()->close();
			}
		}

		// Limit what a front-end user can do
		if(JFactory::getApplication()->isSite()) {
			if(JFactory::getUser()->guest) {
				return false;
			} else {
				$this->input->set('user_id',JFactory::getUser()->id);
			}
		}

		// If it's the back-end CSV view, force no limits
		if(JFactory::getApplication()->isAdmin() && ($this->input->getCmd('format','html') == 'csv')) {
			$this->getThisModel()
				->savestate(0)
				->limit(0)
				->limitstart(0);
		}

		return parent::browse($cachable);
	}

	public function publish()
	{
		$this->noop();
	}

	public function unpublish()
	{
		$this->noop();
	}

	public function noop()
	{
		if($customURL = $this->input->getString('returnurl','')) $customURL = base64_decode($customURL);
		$url = !empty($customURL) ? $customURL : 'index.php?option='.$this->component.'&view='.FOFInflector::pluralize($this->view);
		$this->setRedirect($url);
	}
}