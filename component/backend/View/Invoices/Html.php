<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\View\Invoices;

use Akeeba\Subscriptions\Admin\Model\Invoices;

defined('_JEXEC') or die;

class Html extends \FOF30\View\DataView\Html
{
	public function onRead($tpl = null)
	{
		$this->setPreRender(false);
		$this->setPostRender(false);
	}

	protected function onBrowse($tpl = null)
	{
		/** @var Invoices $model */
		$model = $this->getModel();

		$this->invoicetemplates = $model->getInvoiceTemplateNames();
	}
}