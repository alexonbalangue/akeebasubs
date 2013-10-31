<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class AkeebasubsControllerCallbacks extends FOFController
{

	public function __construct($config = array()) {
		parent::__construct($config);

		$this->setThisModelName('AkeebasubsModelSubscribes');
		$this->csrfProtection = false;

		$this->cacheableTasks = array();
	}


	public function execute($task)
	{
		if(!in_array($task, array('read', 'cancel')))
		{
			$task = 'read';
			$this->input->set('task','read');
		}

		parent::execute($task);
	}

	public function read($cachable = false)
	{
		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		JResponse::setHeader('X-Cache-Control', 'False', true);

		$result = FOFModel::getTmpInstance('Subscribes', 'AkeebasubsModel')
			->paymentmethod($this->input->getCmd('paymentmethod','none'))
			->runCallback();

		echo $result ? 'OK' : 'FAILED';

		JFactory::getApplication()->close();
	}

	public function cancel()
	{
		// Makes sure SiteGround's SuperCache doesn't cache the subscription page
		JResponse::setHeader('X-Cache-Control', 'False', true);

		$msg   = null;
		$type  = null;

		$subid = $this->input->getInt('sid');

		// No subscription id? Let's stop here
		if(!$subid)
		{
			$url  = 'index.php';
			$msg  = JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_FAILED_CANCELLING');
			$type = 'error';
		}
		else
		{
			$sub   = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')->getTable();
			$sub->load($subid);

			$level = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')->getTable();
			$level->load($sub->akeebasubs_level_id);

			$url = JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$level->slug.'&layout=cancel&subid='.$subid);

			$result = FOFModel::getTmpInstance('Subscribes', 'AkeebasubsModel')
						->paymentmethod($this->input->getCmd('paymentmethod','none'))
						->runCancelRecurring();

			if(!$result)
			{
				$msg  = JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_FAILED_CANCELLING');
				$type = 'error';
			}
		}

		$this->setRedirect($url, $msg, $type);
	}

	public function onBeforeRead()
	{
		return true;
	}
}