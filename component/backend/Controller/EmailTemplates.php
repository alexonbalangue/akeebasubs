<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Controller;

defined('_JEXEC') or die;

use Akeeba\Subscriptions\Admin\Helper\Email;
use FOF30\Controller\DataController;
use FOF30\Model\DataModel;
use JFactory;
use JText;

class EmailTemplates extends DataController
{
	public function testTemplate()
	{
		require_once JPATH_ROOT . '/components/com_akeebasubs/helpers/email.php';

		$db = JFactory::getDbo();
		$id = $this->input->getInt('akeebasubs_emailtemplate_id', 0);

		// No id? What??
		if (!$id)
		{
			$this->setRedirect('index.php?option=com_akeebasubs&view=emailtemplates', JText::_('COM_AKEEBASUBS_EMAILTEMPLATES_CHOOSE_TEMPLATE'), 'notice');
			$this->redirect();
		}

		$url = 'index.php?option=com_akeebasubs&view=emailtemplate&id=' . $id;

		$template = $this->getModel()->getClone()->savestate(false)->setIgnoreRequest(true);
		$template->findOrFail($id);

		// Let's grab the first published level
		/** @var DataModel $levelsModel */
		$levelsModel = $this->container->factory
			->model('Levels')->setIgnoreRequest(true)->savestate(false);
		$level = $levelsModel->enabled(1)->firstOrFail();

		// No level? So what's the point?
		if (!$level)
		{
			$this->setRedirect($url, JText::_('COM_AKEEBASUBS_EMAILTEMPLATES_NOENABLEDLEVELS'), 'notice');
			$this->redirect();
		}

		// Let's get a dummy subscription
		/** @var DataModel $sub */
		$sub = $this->container->factory
			->model('Subscriptions')->setIgnoreRequest(true)->savestate(false);

		$sub->akeebasubs_subscription_id = 999999;
		$sub->user_id                    = JFactory::getUser()->id;
		$sub->akeebasubs_level_id        = $level;
		$sub->publish_up                 = date('Y-m-d H:i:s');
		$sub->publish_down               = date('Y-m-d H:i:s', strtotime('+1 month'));
		$sub->notes                      = 'This is just a dummy subscription for email testing';
		$sub->enabled                    = 1;
		$sub->processor                  = 'Dummy processor';
		$sub->processor_key              = 'Dummy processor key';
		$sub->setFieldValue('state', 'C'); // Can't use ->state because of a naming collision
		$sub->net_amount                 = 1234.56;
		$sub->tax_amount                 = 123.456;
		$sub->gross_amount               = 1358.016;
		$sub->recurring_amount           = 0;
		$sub->tax_percent                = 10;
		$sub->created_on                 = date('Y-m-d H:i:s');

		$mailer = Email::getPreloadedMailer($sub, 'plg_akeebasubs_' . $template->key);

		$mailer->addRecipient(JFactory::getUser()->email);

		if ($mailer->Send())
		{
			$this->setRedirect($url, JText::_('COM_AKEEBASUBS_EMAILTEMPLATES_TEST_SENT'));
		}
		else
		{
			$this->setRedirect($url, JText::_('COM_AKEEBASUBS_EMAILTEMPLATES_TEST_ERROR'), 'notice');
		}
	}
}