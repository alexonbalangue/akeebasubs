<?php
/**
 * @package AkeebaSubs
 * @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 * @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

class AkeebasubsModelInvoices extends FOFModel
{
	/**
	 * Create or update an invoice from a subscription
	 * 
	 * @param   object  $sub  The subscription record
	 */
	public function createInvoice($sub)
	{
		// Do we already have an invoice record?
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__akeebasubs_invoices'))
			->where($db->qn('akeebasubs_subscription_id').' = '.$db->q($sub->akeebasubs_subscription_id));
		$db->setQuery($query);
		$invoiceRecord = $db->loadObject();
		
		$existingRecord = is_object($invoiceRecord);
		
		$invoiceData = array();

		// Preload helper classes
		if (!class_exists('AkeebasubsHelperCparams'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/helpers/cparams.php';
		}
		if (!class_exists('AkeebasubsHelperFormat'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/helpers/format.php';
		}
		if (!class_exists('AkeebasubsHelperMessage'))
		{
			require_once JPATH_ROOT . '/components/com_akeebasubs/helpers/message.php';
		}
		
		
		// Get the configuration variables
		if (!$existingRecord)
		{
			$jInvoiceDate = JFactory::getDate();
			$invoiceData = array(
				'akeebasubs_subscription_id'	=> $sub->akeebasubs_subscription_id,
				'extension'						=> 'akeebasubs',
				'invoice_date'					=> $jInvoiceDate->toSql(),
				'enabled'						=> 1
			);
			
			$numberFormat = AkeebasubsHelperCparams::getParam('invoice_number_format', '[N:5]');
			$numberOverride = AkeebasubsHelperCparams::getParam('invoice_override', 0);

			if ($numberOverride)
			{
				// There's an override set. Use it and reset the override to 0.
				$invoice_no = $numberOverride;
				AkeebasubsHelperCparams::setParam('invoice_override', 0);
			}
			else
			{
				// Get the new invoice number by adding one to the previous number
				$query = $db->getQuery(true)
					->select('invoice_no')
					->from($db->qn('#__akeebasubs_invoices'))
					->where($db->qn('extension').' = '.$db->q('akeebasubs'))
					->order($db->q('created_on').' DESC');
				$db->setQuery($query, 0, 1);
				$invoice_no = $db->loadResult();
				
				if (empty($invoice_no))
				{
					$invoice_no = 0;
				}
				
				$invoice_no++;
			}
			
			// Parse the invoice number
			$formated_invoice_no = $this->formatInvoiceNumber($numberFormat, $invoice_no, $jInvoiceDate->toUnix());
			
			// Add the invoice number (plain and formatted) to the record
			$invoiceData['invoice_no'] = $invoice_no;
			$invoiceData['display_number'] = $formated_invoice_no;
		}
		else
		{
			// Existing record, make sure it's extension=akeebasubs or quit
			if ($invoiceRecord->extension != 'akeebasubs')
			{
				$this->setId(0);
				return 0;
			}
			$invoice_no = $invoiceRecord->invoice_no;
			$formated_invoice_no = $invoiceRecord->display_number;
			if (empty($formated_invoice_no))
			{
				$formated_invoice_no = $invoice_no;
			}
			$jInvoiceDate = JFactory::getDate($invoiceRecord->invoice_date);
		}
		
		// Get the template
		$template = $this->findTemplate($sub->akeebasubs_level_id);
		
		// Get the custom variables
		$vat_notice = '';
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($sub->user_id)
			->getFirstItem();
		$country = $kuser->country;
		$isbusiness = $kuser->isbusiness;
		$viesregistered = $kuser->viesregistered;
		$inEU = in_array($country, array('AT','BE','BG','CY','CZ','DK','EE','FI','FR','GB','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE'));
		if($inEU && $isbusiness && $viesregistered) {
			$vat_notice = AkeebasubsHelperCparams::getParam('invoice_vatnote', 'VAT liability is transferred to the recipient, pursuant EU Directive nr 2006/112/EC and local tax laws implementing this directive.');
		}

		$extras = array(
			'[INV:ID]'					=> $invoice_no,
			'[INV:PLAIN_NUMBER]'		=> $invoice_no,
			'[INV:NUMBER]'				=> $formated_invoice_no,
			'[INV:INVOICE_DATE]'		=> AkeebasubsHelperFormat::date($jInvoiceDate->toUnix()),
			'[INV:INVOICE_DATE_EU]'		=> $jInvoiceDate->format('d/m/Y', true),
			'[INV:INVOICE_DATE_USA]'	=> $jInvoiceDate->format('m/d/Y', true),
			'[INV:INVOICE_DATE_JAPAN]'	=> $jInvoiceDate->format('Y/m/d', true),
			'[VAT_NOTICE]'				=> $vat_notice,			
		);
		
		// Render the template into HTML
		$invoiceData['html'] = AkeebasubsHelperMessage::processSubscriptionTags($template, $sub, $extras);
		
		// Save the record
		if($existingRecord)
		{
			$o = (object)$invoiceData;
			$db->updateObject('#__akeebasubs_invoices', $o, 'akeebasubs_subscription_id');
		}
		else
		{
			$o = (object)$invoiceData;
			$db->insertObject('#__akeebasubs_invoices', $o);
		}
		
		// Set up the return value
		$ret = $invoice_no;
		$this->setId($invoice_no);
		
		// Create PDF
		$this->createPDF();
		
		// Update subscription record with the invoice number
		$updates = array(
			'akeebasubs_invoice_id'	=> $invoice_no
		);
		$sub->save($updates);
		
		// If auto-send is enabled, send the invoice by email
		$autoSend = AkeebasubsHelperCparams::getParam('invoice_autosend', 1);
		if ($autoSend)
		{
			$this->emailPDF();
		}
		
		return $ret;
	}
	
	/**
	 * Formats an invoice number
	 * 
	 * @param   string   $numberFormat  The invoice number format
	 * @param   integer  $invoice_no    The plain invoice number
	 * @param   integer  $timestamp     Optional timestamp, otherwise uses current timestamp
	 * 
	 * @return  string  The formatted invoice number
	 */
	public function formatInvoiceNumber($numberFormat, $invoice_no, $timestamp = null)
	{
		// Tokenise the number format
		$formatstring = $numberFormat;
		$tokens = array();
		$start = strpos($formatstring, "[");
		while ($start !== false)
		{
			if ($start != 0)
			{
				$tokens[] = array('s', substr($formatstring, 0, $start));
			}
			
			$end = strpos($formatstring, ']', $start);
			if ($end == false)
			{
				$tokens[] = array('s', substr($formatstring, $start));
				$formatstring = '';
				$start = false;
			}
			else
			{
				$innerContent = substr($formatstring, $start+1, $end-$start-1);
				$formatstring = substr($formatstring, $end + 1);
				$parts = explode(':', $innerContent, 2);
				$tokens[] = array(strtolower($parts[0]), $parts[1]);
			}
			
			$start = strpos($formatstring, "[");
		}
		
		// Parse the tokens
		if (empty($timestamp))
		{
			$timestamp = time();
		}
		$ret = '';
		foreach ($tokens as $token)
		{
			list($type, $param) = $token;
			switch ($type)
			{
				case 's':
					// String parameter
					$ret .= $param;
					break;
				case 'd':
					// Date parameter
					$ret .= date($param, $timestamp);
					break;
				case 'n':
					// Number format
					$param = (int)$param;
					$ret .= sprintf('%0'.$param.'u', $invoice_no);
					break;
			}
		}
		
		return $ret;
	}
	
	/**
	 * Find and return an invoice template based on the subscription level ID
	 * 
	 * @param   integer  $level_id  The susbcription level ID
	 * 
	 * @return  object  The invoice template record
	 */
	private function findTemplate($level_id)
	{
		$ret = '';
		
		// Load all enabled templates and check their levels
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select(array(
				$db->qn('template'),
				$db->qn('levels')
			))
			->from($db->qn('#__akeebasubs_invoicetemplates'))
			->where($db->qn('enabled').' = '.$db->q(1))
			->order($db->qn('ordering').' DESC');
		$db->setQuery($query);
		$templates = $db->loadObjectList();
		
		if (!empty($templates))
		{
			foreach ($templates as $template)
			{
				$levels = explode(',', $template->levels);
				if (in_array(-1, $levels))
				{
					// "No template" is selected
					continue;
				}
				$found = false;
				if (in_array(0, $levels))
				{
					// "All levels" is selected
					$found = true;
				}
				else
				{
					// Check if our level is included
					$found = in_array($level_id, $levels);
				}
				
				if (!$found)
				{
					continue;
				}
				
				$ret = $template->template;
			}
		}
		
		return $ret;
	}
	
	/**
	 * Create a PDF representation of an invoice.
	 * 
	 * @return  string  The (mangled) filename of the PDF file
	 */
	public function createPDF()
	{
		// Get the invoice number from the model's state
		$invoice_no = $this->getId();
		
		// Fetch the HTML from the database using the invoice number in $this->getId()
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__akeebasubs_invoices'))
			->where($db->qn('extension') . ' = ' . $db->q('akeebasubs'))
			->where($db->qn('invoice_no') . ' = ' . $db->q($invoice_no));
		$db->setQuery($query, 0, 1);
		$invoiceRecord = $db->loadObject();
		
		// Create the PDF
		$pdf = $this->getTCPDF();
		$pdf->AddPage();
		$pdf->writeHTML($invoiceRecord->html, true, false, true, false, '');
		$pdf->lastPage();
		$pdfData = $pdf->Output('', 'S');
		
		unset($pdf);
		
		// Write the PDF data to disk using JFile::write();
		jimport('joomla.filesystem.file');
		if (function_exists('openssl_random_pseudo_bytes'))
		{
			$rand = openssl_random_pseudo_bytes(16);
			if ($rand === false)
			{
				// Broken or old system
				$rand = mt_rand();
			}
		}
		else
		{
			$rand = mt_rand();
		}
		$hashThis = serialize($invoiceRecord) . microtime() . $rand;
		if (function_exists('hash'))
		{
			$hash = hash('sha256', $hashThis);
		}
		if (function_exists('sha1'))
		{
			$hash = sha1($hashThis);
		}
		else
		{
			$hash = md5($hashThis);
		}
		$name = $hash . '_' . $invoiceRecord->invoice_no . '.pdf';
		
		$path = JPATH_ADMINISTRATOR . '/components/com_akeebasubs/invoices/';
		
		$ret = JFile::write($path . $name, $pdfData);
		
		if ($ret)
		{
			// Delete the old invoice file
			$oldName = $invoiceRecord->filename;
			if (JFile::exists($path . $oldName))
			{
				JFile::delete($path . $oldName);
			}
			
			// Update the invoice record
			$invoiceRecord->filename = $name;
			$db->updateObject('#__akeebasubs_invoices', $invoiceRecord, 'akeebasubs_subscription_id');
			
			// return the name of the file
			return $name;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Send an invoice by email. If the invoice's PDF doesn't exist it will
	 * attempt to create it. If the extension != akeebasubs it will return
	 * false.
	 * 
	 * @return  string  The filename of the PDF or false if the creation failed.
	 */
	public function emailPDF()
	{
		// Get the invoice number from the model's state
		$invoice_no = $this->getId();
		
		// Fetch the HTML from the database using the invoice number in $this->getId()
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__akeebasubs_invoices'))
			->where($db->qn('extension') . ' = ' . $db->q('akeebasubs'))
			->where($db->qn('invoice_no') . ' = ' . $db->q($invoice_no));
		$db->setQuery($query, 0, 1);
		$invoiceRecord = $db->loadObject();
		
		if (empty($invoiceRecord->filename))
		{
			$invoiceRecord->filename = $this->createPDF();
		}
		
		if (empty($invoiceRecord->filename))
		{
			return false;
		}
		
		// Get the subscription record
		$sub = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
			->getItem($invoiceRecord->akeebasubs_subscription_id);
		
		// Get the mailer
		if (!class_exists('AkeebasubsHelperEmail'))
		{
			require_once JPATH_ROOT . '/components/com_akeebasubs/helpers/email.php';
		}
		$mailer = AkeebasubsHelperEmail::getPreloadedMailer($sub, 'PLG_AKEEBASUBS_INVOICING_EMAIL');
		
		// Attach the PDF invoice
		$path = JPATH_ADMINISTRATOR . '/components/com_akeebasubs/invoices/' . $invoiceRecord->filename;
		$mailer->AddAttachment($path, 'invoice.pdf', 'base64', 'application/pdf');
		
		// Set the recipient
		$mailer->addRecipient(JFactory::getUser($sub->user_id)->email);
		
		// Send it
		return $mailer->Send();
	}
	
	public function &getTCPDF()
	{
		$jreg = JFactory::getConfig();
		$tmpdir = $jreg->get('tmp_path');
		$siteName = $jreg->get('sitename');
		
		define('K_TCPDF_EXTERNAL_CONFIG', 1);
		
		define ('K_PATH_MAIN', __DIR__);
		define ('K_PATH_URL', JURI::base());
		define ('K_PATH_FONTS', JPATH_ROOT.'/media/com_akeebasubs/tcpdf/fonts/');
		define ('K_PATH_CACHE', $tmpdir);
		define ('K_PATH_URL_CACHE', $tmpdir);
		define ('K_PATH_IMAGES', JPATH_ROOT.'/media/com_akeebasubs/tcpdf/images/');
		define ('K_BLANK_IMAGE', K_PATH_IMAGES.'_blank.png');
		define ('PDF_PAGE_FORMAT', 'A4');
		define ('PDF_PAGE_ORIENTATION', 'P');
		define ('PDF_CREATOR', 'Akeeba Subscriptions');
		define ('PDF_AUTHOR', $siteName);
		define ('PDF_UNIT', 'mm');
		define ('PDF_MARGIN_HEADER', 5);
		define ('PDF_MARGIN_FOOTER', 10);
		define ('PDF_MARGIN_TOP', 27);
		define ('PDF_MARGIN_BOTTOM', 25);
		define ('PDF_MARGIN_LEFT', 15);
		define ('PDF_MARGIN_RIGHT', 15);
		define ('PDF_FONT_NAME_MAIN', 'helvetica');
		define ('PDF_FONT_SIZE_MAIN', 10);
		define ('PDF_FONT_NAME_DATA', 'helvetica');
		define ('PDF_FONT_SIZE_DATA', 8);
		define ('PDF_FONT_MONOSPACED', 'courier');
		define ('PDF_IMAGE_SCALE_RATIO', 1.25);
		define('HEAD_MAGNIFICATION', 1.1);
		define('K_CELL_HEIGHT_RATIO', 1.25);
		define('K_TITLE_MAGNIFICATION', 1.3);
		define('K_SMALL_RATIO', 2/3);
		define('K_THAI_TOPCHARS', true);
		define('K_TCPDF_CALLS_IN_HTML', false);
		
		require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/assets/tcpdf/tcpdf.php';
		
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor(PDF_AUTHOR);
		$pdf->SetTitle('Invoice');
		$pdf->SetSubject('Invoice');

		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		
		$pdf->SetFont('helvetica', '', 10);

		return $pdf;
	}
	
	/**
	 * Returns a list of known invoicing extensions
	 * 
	 * @param   integer  $style  0 = raw sections list, 1 = grouped list options, 2 = key/description array
	 * 
	 * @return array_string
	 */
	public function getExtensions($style = 0)
	{
		static $rawOptions = null;
		static $htmlOptions = null;
		static $shortlist = null;
		
		if (is_null($rawOptions))
		{
			$rawOptions = array();
			
			jimport('joomla.plugin.helper');
			JPluginHelper::importPlugin('akeebasubs');
			JPluginHelper::importPlugin('system');
			$app = JFactory::getApplication();
			$jResponse = $app->triggerEvent('onAKGetInvoicingOptions', array());
			if (is_array($jResponse) && !empty($jResponse))
			{
				foreach ($jResponse as $pResponse)
				{
					if(!is_array($pResponse)) continue;
					if(empty($pResponse)) continue;
					
					$rawOptions[$pResponse['extension']] = $pResponse;
				}
			}
		}
		
		if ($style == 0)
		{
			return $rawOptions;
		}
		
		if (is_null($htmlOptions))
		{
			$htmlOptions = array();
			
			foreach ($rawOptions as $def)
			{
				$htmlOptions[] = JHTML::_('select.option', $def['extension'], $def['title']);
				$shortlist[$def['extension']] = $def['title'];
			}
		}
		
		if ($style == 1)
		{
			return $htmlOptions;
		}
		else
		{
			return $shortlist;
		}
	}
}