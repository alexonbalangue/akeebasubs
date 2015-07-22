<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Subscriptions\Admin\Form\Field;

use Akeeba\Subscriptions\Admin\Model\Invoices;
use FOF30\Form\Field\Text;

defined('_JEXEC') or die;

class InvoiceNumber extends Text
{
	public function getRepeatable()
	{
		static $invoicetemplates = null;

		if (is_null($invoicetemplates))
		{
			/** @var Invoices $model */
			$model = $this->form->getModel();

			$invoicetemplates = $model->getInvoiceTemplateNames();
		}

		$value = '';

		if (
			($this->item->extension == 'akeebasubs')
			&& array_key_exists($this->item->akeebasubs_invoicetemplate_id, $invoicetemplates)
		)
		{
			$value .= '<span class="label label-info">' . $invoicetemplates[$this->item->akeebasubs_invoicetemplate_id]->title . '</span> ';
		}

		if (!empty($this->item->display_number))
		{
			$value .= htmlentities($this->item->display_number, ENT_COMPAT, 'UTF-8');
		}
		else
		{
			$value .= htmlentities($this->item->invoice_no, ENT_COMPAT, 'UTF-8');
		}

		return $value;
	}
}