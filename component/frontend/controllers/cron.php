<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */
defined('_JEXEC') or die();

class AkeebasubsControllerCron extends FOFController
{

	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->cacheableTasks = array();
	}

	public function execute($task)
	{
		$task = 'cron';
		$this->input->set('task', 'cron');
		parent::execute($task);
	}

	public function cron($cachable = false)
	{
		// Makes sure SiteGround's SuperCache doesn't cache the CRON view
		JResponse::setHeader('X-Cache-Control', 'False', true);

		require_once FOFTemplateUtils::parsePath('admin://components/com_akeebasubs/helpers/cparams.php', true);
		$configuredSecret = AkeebasubsHelperCparams::getParam('secret', '');

		if (empty($configuredSecret))
		{
			header('HTTP/1.1 503 Service unavailable due to configuration');
			JFactory::getApplication()->close();
		}

		$secret = $this->input->get('secret', null, 'raw');

		if ($secret != $configuredSecret)
		{
			header('HTTP/1.1 403 Forbidden');
			JFactory::getApplication()->close();
		}

		$command = $this->input->get('command', null, 'raw');
		$command = trim(strtolower($command));

		if (empty($command))
		{
			header('HTTP/1.1 501 Not implemented');
			JFactory::getApplication()->close();
		}

		FOFPlatform::getInstance()->importPlugin('system');
		FOFPlatform::getInstance()->runPlugins('onAkeebasubsCronTask', array(
			$command,
			array(
				'time_limit'	=> 10
			)
		));

		echo "$command OK";

		JFactory::getApplication()->close();
	}

	public function onBeforeRead()
	{
		return true;
	}

}