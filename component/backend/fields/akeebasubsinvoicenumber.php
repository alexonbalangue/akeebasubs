<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

/**
 * Renders the price of a subscription level and its optional sign-up fee
 */
class FOFFormFieldAkeebasubsinvoicenumber extends FOFFormFieldText
{
	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.0
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
		static $invoicetemplates = null;

		if (is_null($invoicetemplates))
		{
			$invoicetemplates = FOFModel::getTmpInstance('Invoices', 'AkeebasubsModel')
				->getInvoiceTemplateNames();
		}

		$value = '';

		if (
			($this->item->extension == 'akeebasubs')
			&& array_key_exists($this->item->akeebasubs_invoicetemplate_id, $invoicetemplates)
		)
		{
			$value .= '<span class="label label-info">' . $invoicetemplates[$this->item->akeebasubs_invoicetemplate_id]->title . '</span> ';
		}

		if (!empty($invoice->display_number))
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
