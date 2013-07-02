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
class FOFFormFieldAkeebasubsinvoiceactions extends FOFFormFieldText
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
		static $extensions = null;

		if (is_null($extensions))
		{
			$extensions = FOFModel::getTmpInstance('Invoices', 'AkeebasubsModel')
				->getExtensions();
		}

		$html = '';

		if ($this->item->extension == 'akeebasubs')
		{
			$html .= '<a href="index.php?option=com_akeebasubs&view=invoices&task=read&id=' .
				htmlspecialchars($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
				'&tmpl=component" class="btn btn-info modal" rel="{handler: \'iframe\', size: {x: 800, y: 500}}" title="' .
				JText::_('COM_AKEEBASUBS_INVOICES_ACTION_PREVIEW') . '"><span class="icon icon-file icon-white"></span></a>' .
				"\n";
			$html .= '<a href="index.php?option=com_akeebasubs&view=invoices&task=download&id=' .
				htmlspecialchars($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
				'" class="btn btn-primary" title="' .
				JText::_('COM_AKEEBASUBS_INVOICES_ACTION_DOWNLOAD')
				. '"><span class="icon icon-download-alt icon-white"></span></a>' . "\n";
			$html .= '<a href="index.php?option=com_akeebasubs&view=invoices&task=send&id=' .
				htmlentities($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
				'" class="btn btn-success" title="' .
				JText::_('COM_AKEEBASUBS_INVOICES_ACTION_RESEND') .
				'"><span class="icon icon-envelope icon-white"></span></a>'
				. "\n";

			$db = JFactory::getDbo();
			if (empty($this->item->sent_on) || ($this->item->sent_on == $db->getNullDate()))
			{
				$html .= '<span class="label"><span class="icon icon-white icon-warning-sign"></span>' .
					JText::_('COM_AKEEBASUBS_INVOICES_LBL_NOTSENT') . '</span>' . "\n";
			}
			else
			{
				$html .= '<span class="label label-success"><span class="icon icon-white icon-ok"></span>' .
					JText::_('COM_AKEEBASUBS_INVOICES_LBL_SENT') . '</span>' . "\n";
			}

			$html .= '<a href="index.php?option=com_akeebasubs&view=invoices&task=generate&id=' .
				htmlentities($this->item->akeebasubs_subscription_id, ENT_COMPAT, 'UTF-8') .
				'" class="btn btn-mini btn-warning" title="' .
				JText::_('COM_AKEEBASUBS_INVOICES_ACTION_REGENERATE') .
				'"><span class="icon icon-retweet icon-white"></span></a>'
				. "\n";
		}
		elseif(array_key_exists($this->item->extension, $extensions))
		{
			$html .= '<a class="btn" href="' .
				sprintf($extensions[$this->item->extension]['backendurl'], $this->item->invoice_no) .
				'"><span class="icon icon-share-alt"></span>' .
				JText::_('COM_AKEEBASUBS_INVOICES_LBL_OPENEXTERNAL') .
				'</a>' . "\n";
		}
		else
		{
			$html .= '<span class="label">' . JText::_('COM_AKEEBASUBS_INVOICES_LBL_NOACTIONS') . '</span>' . "\n";
		}

		return $html;
	}
}
