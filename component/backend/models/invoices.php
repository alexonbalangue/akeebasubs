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
	private function getFilterValues()
	{
		$enabled = $this->getState('enabled','','cmd');
		
		return (object)array(
			// Default filters
			'akeebasubs_subscription_id'	=> $this->getState('akeebasubs_subscription_id', null, 'int'),
			'extension'		=> $this->getState('extension', null, 'cmd'),
			'invoice_no'	=> $this->getState('invoice_no', null, 'int'),
			'invoice_date'	=> $this->getState('invoice_date', null, 'string'),
			'display_number'=> $this->getState('display_number', null, 'string'),
			'html'			=> $this->getState('html', null, 'string'),
			'atxt'			=> $this->getState('atxt', null, 'string'),
			'btxt'			=> $this->getState('btxt', null, 'string'),
			'filename'		=> $this->getState('filename', null, 'string'),
			'sent_on'		=> $this->getState('sent_on', null, 'string'),
			
			// Custom filters
			'user_id'		=> $this->getState('user_id', null, 'int'),
			'user'			=> $this->getState('user', null, 'string'),
			'invoice_number'=> $this->getState('invoice_number', null, 'string'),
			'sent_on_before'=> $this->getState('sent_on_before', null, 'string'),
			'sent_on_after' => $this->getState('sent_on_after', null, 'string'),
			'invoice_date_before'=> $this->getState('invoice_date_before', null, 'string'),
			'invoice_date_after' => $this->getState('invoice_date_after', null, 'string'),
		);
	}
	
	protected function _buildQueryJoins(&$query)
	{
		$db = $this->getDbo();
		$query
			->join('INNER', $db->qn('#__akeebasubs_subscriptions').' AS '.$db->qn('s').' ON '.
					$db->qn('s').'.'.$db->qn('akeebasubs_subscription_id').' = '.
					$db->qn('tbl').'.'.$db->qn('akeebasubs_subscription_id'))
			->join('LEFT OUTER', $db->qn('#__users').' AS '.$db->qn('u').' ON '.
					$db->qn('u').'.'.$db->qn('id').' = '.
					$db->qn('s').'.'.$db->qn('user_id'))
			->join('LEFT OUTER', $db->qn('#__akeebasubs_users').' AS '.$db->qn('a').' ON '.
					$db->qn('a').'.'.$db->qn('user_id').' = '.
					$db->qn('s').'.'.$db->qn('user_id'))
		;
	}
	
	protected function _buildQueryColumns(&$query)
	{
		$db = $this->getDbo();

		$query->select(array(
			$db->qn('tbl').'.*',
			$db->qn('s').'.'.$db->qn('user_id'),
			$db->qn('u').'.'.$db->qn('name'),
			$db->qn('u').'.'.$db->qn('username'),
			$db->qn('u').'.'.$db->qn('email'),
			$db->qn('u').'.'.$db->qn('block'),
			$db->qn('a').'.'.$db->qn('isbusiness'),
			$db->qn('a').'.'.$db->qn('businessname'),
			$db->qn('a').'.'.$db->qn('occupation'),
			$db->qn('a').'.'.$db->qn('vatnumber'),
			$db->qn('a').'.'.$db->qn('viesregistered'),
			$db->qn('a').'.'.$db->qn('taxauthority'),
			$db->qn('a').'.'.$db->qn('address1'),
			$db->qn('a').'.'.$db->qn('address2'),
			$db->qn('a').'.'.$db->qn('city'),
			$db->qn('a').'.'.$db->qn('state').' AS '.$db->qn('userstate'),
			$db->qn('a').'.'.$db->qn('zip'),
			$db->qn('a').'.'.$db->qn('country'),
			$db->qn('a').'.'.$db->qn('params').' AS '.$db->qn('userparams'),
			$db->qn('a').'.'.$db->qn('notes').' AS '.$db->qn('usernotes'),
		));

		$order = $this->getState('filter_order', 'akeebasubs_subscription_id', 'cmd');
		if(!in_array($order, array_keys($this->getTable()->getData()))) $order = 'akeebasubs_subscription_id';
		$dir = $this->getState('filter_order_Dir', 'DESC', 'cmd');
		$query->order($order.' '.$dir);
	}
	
	protected function _buildQueryWhere($query)
	{
		$db = $this->getDbo();
		$state = $this->getFilterValues();
		
		jimport('joomla.utilities.date');
		
		if (is_numeric($state->akeebasubs_subscription_id) && ($state->akeebasubs_subscription_id > 0))
		{
			$query->where(
				$db->qn('tbl').'.'.$db->qn('akeebasubs_subscription_id').' = '.
					$db->q((int)$state->akeebasubs_subscription_id)
			);
		}
		
		if (!empty($state->extension))
		{
			$query->where(
				$db->qn('tbl').'.'.$db->qn('extension').' = '.
					$db->q($state->extension)
			);
		}
		
		if (!empty($state->invoice_number))
		{
			// Unified invoice / display number search
			$query->where(
					'(('.
					$db->qn('tbl').'.'.$db->qn('invoice_no').' = '.
						$db->q((int)$state->invoice_number)
					. ') OR (' .
					$db->qn('tbl').'.'.$db->qn('display_number').' LIKE '.
						$db->q('%'.$state->invoice_number.'%')
					. '))'
				);
		}
		else
		{
			// Separate searches for invoice number and dispay number
			if (is_numeric($state->invoice_no) && $state->invoice_no)
			{
				$query->where(
					$db->qn('tbl').'.'.$db->qn('invoice_no').' = '.
						$db->q((int)$state->invoice_no)
				);
			}
			if (!empty($state->display_number))
			{
				$query->where(
					$db->qn('tbl').'.'.$db->qn('display_number').' LIKE '.
						$db->q('%'.$state->display_number.'%')
				);
			}
		}

		if (!empty($state->html))
		{
			$query->where(
				$db->qn('tbl').'.'.$db->qn('html').' LIKE '.
					$db->q('%'.$state->html.'%')
			);
		}
		
		if (!empty($state->atxt))
		{
			$query->where(
				$db->qn('tbl').'.'.$db->qn('atxt').' LIKE '.
					$db->q('%'.$state->atxt.'%')
			);
		}
		
		if (!empty($state->btxt))
		{
			$query->where(
				$db->qn('tbl').'.'.$db->qn('btxt').' LIKE '.
					$db->q('%'.$state->btxt.'%')
			);
		}
		
		if (!empty($state->filename))
		{
			$query->where(
				$db->qn('tbl').'.'.$db->qn('filename').' LIKE '.
					$db->q('%'.$state->filename.'%')
			);
		}
		
		if (is_numeric($state->user_id) && $state->user_id)
		{
			$query->where(
				$db->qn('s').'.'.$db->qn('user_id').' = '.
					$db->q((int)$state->user_id)
			);
		}
		
		if (!empty($state->user))
		{
			$search = '%'.$state->user.'%';
			$query->where(
				'CONCAT(IF(u.name IS NULL,"",u.name),IF(u.username IS NULL,"",u.username),IF(u.email IS NULL, "", u.email),IF(a.businessname IS NULL, "", a.businessname), IF(a.vatnumber IS NULL,"",a.vatnumber)) LIKE '.
					$db->q($search)
			);
		}
		
		$regex = '/^\d{1,4}(\/|-)\d{1,2}(\/|-)\d{2,4}[[:space:]]{0,}(\d{1,2}:\d{1,2}(:\d{1,2}){0,1}){0,1}$/';
		if (!empty($state->invoice_date) && preg_match($regex, $state->invoice_date))
		{
			$jFrom = JFactory::getDate($state->invoice_date);
			$jFrom->setTime(0, 0, 0);
			$jTo = clone $jFrom;
			$jTo->setTime(23,59,59);
			
			$query->where(
				$db->qn('invoice_date') . ' BETWEEN ' . $db->q($jFrom->toSql()) .
				' AND ' . $db->q($jTo->toSql())
			);
		}
		elseif(!empty($state->invoice_date_before) || !empty($state->invoice_date_after))
		{
			if(!empty($state->invoice_date_before) && preg_match($date_regex, $state->invoice_date_before))
			{
				$jDate = JFactory::getDate($state->invoice_date_before);
				$query->where($db->qn('invoice_date') . ' <= ' . $db->q($jDate->toSql()));
			}
			if(!empty($state->invoice_date_after) && preg_match($date_regex, $state->invoice_date_after))
			{
				$jDate = JFactory::getDate($state->invoice_date_after);
				$query->where($db->qn('invoice_date') . ' >= ' . $db->q($jDate->toSql()));
			}
		}
		
		if (!empty($state->sent_on) && preg_match($regex, $state->sent_on))
		{
			$jFrom = JFactory::getDate($state->sent_on);
			$jFrom->setTime(0, 0, 0);
			$jTo = clone $jFrom;
			$jTo->setTime(23,59,59);
			
			$query->where(
				$db->qn('sent_on') . ' BETWEEN ' . $db->q($jFrom->toSql()) .
				' AND ' . $db->q($jTo->toSql())
			);
		}
		elseif(!empty($state->sent_on_before) || !empty($state->sent_on_after))
		{
			if(!empty($state->sent_on_before) && preg_match($date_regex, $state->sent_on_before))
			{
				$jDate = JFactory::getDate($state->sent_on_before);
				$query->where($db->qn('sent_on') . ' <= ' . $db->q($jDate->toSql()));
			}
			if(!empty($state->sent_on_after) && preg_match($date_regex, $state->sent_on_after))
			{
				$jDate = JFactory::getDate($state->sent_on_after);
				$query->where($db->qn('sent_on') . ' >= ' . $db->q($jDate->toSql()));
			}
		}
	}
	
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->from($db->qn('#__akeebasubs_invoices').' AS '.$db->qn('tbl'));
		
		$this->_buildQueryColumns($query);
		$this->_buildQueryJoins($query);
		$this->_buildQueryWhere($query);
		
		return $query;
	}
	
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
				return false;
			}
			$invoice_no = $invoiceRecord->invoice_no;
			$formated_invoice_no = $invoiceRecord->display_number;
			if (empty($formated_invoice_no))
			{
				$formated_invoice_no = $invoice_no;
			}
			$jInvoiceDate = JFactory::getDate($invoiceRecord->invoice_date);
			
			$invoiceData = (array)$invoiceRecord;
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
		$this->setId($sub->akeebasubs_subscription_id);
		
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
		
		return true;
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
		$akeebasubs_subscription_id = $this->getId();
		
		// Fetch the HTML from the database using the invoice number in $this->getId()
		$db = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__akeebasubs_invoices'))
			->where($db->qn('extension') . ' = ' . $db->q('akeebasubs'))
			->where($db->qn('akeebasubs_subscription_id') . ' = ' . $db->q($akeebasubs_subscription_id));
		$db->setQuery($query, 0, 1);
		$invoiceRecord = $db->loadObject();
		
		$invoice_no = $invoiceRecord->invoice_no;
		
		// Create the PDF
		$pdf = $this->getTCPDF();
		$pdf->AddPage();
		
		if (function_exists('tidy_repair_string'))
		{
			$invoiceRecord->html = tidy_repair_string($invoiceRecord->html, null, 'utf8');
		}
		
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
			->where($db->qn('akeebasubs_subscription_id') . ' = ' . $db->q($invoice_no));
		$db->setQuery($query, 0, 1);
		$invoiceRecord = $db->loadObject();
		
		jimport('joomla.filesystem.file');
		$path = JPATH_ADMINISTRATOR . '/components/com_akeebasubs/invoices/';

		if (empty($invoiceRecord->filename) || !JFile::exists($path.$invoiceRecord->filename))
		{
			$invoiceRecord->filename = $this->createPDF();
		}
		
		if (empty($invoiceRecord->filename) || !JFile::exists($path.$invoiceRecord->filename))
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
		$mailer = AkeebasubsHelperEmail::getPreloadedMailer($sub, 'PLG_AKEEBASUBS_INVOICES_EMAIL');
		
		// Attach the PDF invoice
		$mailer->AddAttachment($path . $invoiceRecord->filename, 'invoice.pdf', 'base64', 'application/pdf');
		
		// Set the recipient
		$mailer->addRecipient(JFactory::getUser($sub->user_id)->email);
		
		// Send it
		$result = $mailer->Send();
		
		if ($result == true)
		{
			$invoiceRecord->sent_on = JFactory::getDate()->toSql();
			$db->updateObject('#__akeebasubs_invoices', $invoiceRecord, 'akeebasubs_subscription_id');
		}
		
		return $result;
	}
	
	public function &getTCPDF()
	{
		// Load PDF signing certificates
		if (!class_exists('AkeebasubsHelperCparams'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_akeebasubs/helpers/cparams.php';
		}
		
		$certificateFile = AkeebasubsHelperCparams::getParam('invoice_certificatefile', 'certificate.cer');
		$secretKeyFile = AkeebasubsHelperCparams::getParam('invoice_secretkeyfile', 'secret.cer');
		$secretKeyPass = AkeebasubsHelperCparams::getParam('invoice_secretkeypass', '');
		$extraCertFile = AkeebasubsHelperCparams::getParam('invoice_extracert', 'extra.cer');
		
		$certificate = '';
		$secretkey = '';
		$extracerts = '';
		
		$path = JPATH_ADMINISTRATOR . '/components/com_akeebasubs/assets/tcpdf/certificates/';
		if (JFile::exists($path.$certificateFile))
		{
			$certificate = JFile::read($path.$certificateFile);
		}
		if (!empty($certificate))
		{
			if (JFile::exists($path.$secretKeyFile))
			{
				$secretkey = JFile::read($path.$secretKeyFile);
			}
			if (empty($secretkey))
			{
				$secretkey = $certificate;
			}
			
			if (JFile::exists($path.$extraCertFile))
			{
				$extracerts = JFile::read($path.$extraCertFile);
			}
			if (empty($extracerts))
			{
				$extracerts = '';
			}
		}
		
		// Set up TCPDF
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
		
		if (!empty($certificate))
		{
			$pdf->setSignature($certificate, $secretkey, $secretKeyPass, $extracerts);
		}
		
		$pdf->SetFont('helvetica', '', 10);

		return $pdf;
	}
	
	/**
	 * Returns a list of known invoicing extensions
	 * 
	 * @param   integer  $style  0 = raw sections list, 1 = list options, 2 = key/description array
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