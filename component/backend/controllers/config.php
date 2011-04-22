<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

class ComAkeebasubsControllerConfig extends ComDefaultControllerDefault 
{
	function _actionRead(KCommandContext $context)
	{
		$model	= $this->getModel();
		$html	= KFactory::tmp('admin::com.akeebasubs.simpleform.default')
					->setData($model->getConfig())
					->setDefinitions($model->getDefinitions())
					->renderHtml();
		
		$view = $this->getView();
		$view->assign('formhtml', $html);
		
		return $view->display();
	}
	
	function _actionAdd(KCommandContext $context)
	{
		$model	= $this->getModel();
		$model->saveConfig(KConfig::toData($context->data));

		// I don't know why this doesn't work!
		//$this->setRedirect('index.php?option=com_akeebasubs&view=dashboard');
		
		$app = JFactory::getApplication();
		$app->redirect('index.php?option=com_akeebasubs&view=dashboard');
	}
	
	function _actionCancel(KCommandContext $context)
	{
		$app = JFactory::getApplication();
		$app->redirect('index.php?option=com_akeebasubs&view=dashboard');
	}
}