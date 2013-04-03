<?php
/**
 * @package		zoolanders
 * @copyright	Copyright (c) 2013 ZOOlanders.com
 * @license		GNU GPLv2 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsZohoinvoice extends JPlugin
{
	protected $data = array();

	protected $eu_countries = array('IT', 'AT', 'BE', 'BG', 'CY', 'DK', 'EE', 'FI', 'FR', 'FX', 'GR', 'IE', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'GB', 'CZ', 'RO', 'SK', 'ES', 'SE', 'HU', 'DE');

	function plgAkeebasubsZohoinvoice(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->data['apikey'] = $this->params->get('apikey','');
		$this->data['authtoken'] = $this->params->get('authtoken','');
		$this->data['CompanyID'] = $this->params->get('companyid','');

		$this->data['scope'] = 'invoiceapi';
	}

	public function onAKGetInvoicingOptions()
	{
		$partial_path = $this->params->get('downloadpath', '/media/invoices/');

		jimport('joomla.filesystem.file');
		return array(
			'extension'		=> 'zohoinvoice',
			'title'			=> 'ZOHO Invoice',
			'enabled'		=> true,
			'backendurl'	=> 'https://invoice.zoho.com/view/ZB_Main/ZB_Invoice/ZB_PreviewInvoice?Preview_InvoiceID=%s',
			'frontendurl'	=> JURI::root() . '/' . $partial_path . '/%s.pdf'
		);
	}

	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		$site_only = $this->params->get('site_only', false);
		if(!$site_only ||(JFactory::getApplication()->isSite() && $site_only))
		{
			// Did the payment status just change to C or P? It's a new subscription
			if(array_key_exists('state', (array)$info['modified']) && in_array($row->state, array('P','C')) && $row->enabled && !$row->akeebasubs_invoice_id)
			{
				$level = FOFModel::getTmpInstance('Level', 'AkeebasubsModel')->setId($row->akeebasubs_level_id)->getItem();
				$this->createInvoice($row, $level);
			}
		}
	}

	/**
	 * Creates a ZOHO Invoice
	 */
	protected function createInvoice($sub, $level)
	{
		// Convert Date
		$date = date("Y-m-d", strtotime($sub->created_on));

		// Get User
		$user = $this->getUser($sub->user_id);

		// Get Customer from API
		$customer_id = $this->getCustomer($user);

		// EU Company
		$is_eu = $this->isEu($user->country);

		// Create the XML
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Invoice></Invoice>');
		$xml->CustomerID = $customer_id;
		$xml->InvoiceDate = $date;

		// Add Item
		$this->getItem($level, $sub, $xml);

		// Company but no tax => EU Company
		if ($sub->tax_amount <= 0)
		{
			$xml->Notes = $this->params->get('notes_notax', '');
			if (!$user->isbusiness && !$is_eu)
			{
				$xml->Notes = $this->params->get('notes_notax_private', $this->params->get('notes_notax', ''));
			}
		}

		// XML String
		$data = $this->data;
		$data['XMLString'] = $xml->asXML();

		// Send Invoice?
		if ($this->params->get('send', 1)) {
			$data['Send'] = 'true';
		} else {
			$data['Send'] = 'false';
		}

		$url = 'https://invoice.zoho.com/api/invoices/create/';

		// Create Invoice
		$answer = $this->sendRequest($url, $data, 'POST');
		$answer = new SimpleXMLElement($answer);

		// Add Payment
		$invoice_id = (string)$answer->Invoice->InvoiceID;
		$invoice_num = (string)$answer->Invoice->InvoiceNumber;
		$this->createPayment($invoice_id, $sub);

		// Sync Locally
		$this->syncInvoice($sub, $invoice_id, $invoice_num);
	}

	/**
	 * Create a new Item remotely
	 */
	protected function getItem($level, $sub, &$xml)
	{
		$xml->InvoiceItems = new SimpleXMLElement ('<InvoiceItems><InvoiceItem></InvoiceItem></InvoiceItems>');
		$xml->InvoiceItems->InvoiceItem;

		$xml->InvoiceItems->InvoiceItem->ItemName = $level->title;
		$xml->InvoiceItems->InvoiceItem->ItemDescription = $level->description;
		$xml->InvoiceItems->InvoiceItem->Price = $sub->net_amount;
		$xml->InvoiceItems->InvoiceItem->Quantity = 1;
		$xml->InvoiceItems->InvoiceItem->Discount = 0;

		if($sub->tax_amount > 0)
		{
			$xml->InvoiceItems->InvoiceItem->Tax1Name = $this->params->get('tax', '');
		}
	}

	/**
	 * Get a ZOHO Invoice Customer, creating it if necessary
	 */
	protected function getCustomer( $user )
	{
		// Search Customer
		$data = $this->data;
		
		if($user->isbusiness)
		{
			$data['searchtext'] = $user->businessname;	
		}
		else
		{
			$data['searchtext'] = JUser::getInstance( $user->user_id )->name;	
		}

		$url = 'https://invoice.zoho.com/api/view/search/customers/';

		$answer = $this->sendRequest($url, $data, 'GET');
		$customer_id = $this->processCustomer( $answer, $data['searchtext'] );
		
		if (!$customer_id)
		{
			$customer_id = $this->createCustomer( $user );
		}

		return $customer_id;
	}

	/**
	 * Process the answer from ZOHO for Customer Search
	 */
	protected function processCustomer($answer, $name)
	{
		$xml = new SimpleXMLElement($answer);

		if ($xml->attributes()->status == '1')
		{
			$customers = $xml->Customers;
			if (count($customers->children()))
			{
				foreach ($customers->Customer as $customer)
				{
					// Check the name, the Zoho api searches partial text
					$cname = (string)$customer->Name;
					if ($name == $cname) {
						return $customer->CustomerID;
					}
				}
			}
		}

		return 0;
	}
	
	/**
	 * Creates a Customer on ZOHO Invoice
	 */
	protected function createCustomer( $user )
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Customer></Customer>');
		
		$user_name = JUser::getInstance( $user->user_id )->name;
		$aname = explode(' ', $user_name);
		$name = @$aname[0];
		$surname = @$aname[1];
		
		$xml->Name = $user_name;
		$xml->CurrencyCode = $this->params->get('currency', 'EUR');
		$xml->BillingAddress = $user->address1 . ' ' . $user->address2;
		$xml->BillingCity = $user->city;
		$xml->BillingZip = $user->zip;
		$xml->BillingCountry = $user->country;
		$xml->Contacts->Contact->FirstName = $name;
		$xml->Contacts->Contact->LastName = $surname;
		$xml->Contacts->Contact->EMail = trim( JUser::getInstance( $user->user_id )->email );
		
		if( $user->isbusiness )
		{
			$xml->Name = $user->businessname;

			if(strlen(trim($user->vatnumber))){
				$xml->CustomFields->CustomFieldLabel1 = $this->params->get('vat_id', '');
				$xml->CustomFields->CustomFieldValue1 = $user->vatnumber;
			}
		}
		
		$data = $this->data;
		$data['XMLString'] = $xml->asXML();
		
		$url = 'https://invoice.zoho.com/api/customers/create/';
		
		$answer = $this->sendRequest($url, $data, 'POST');
		
		$answer = new SimpleXMLElement($answer);
		
		return $answer->Customer->CustomerID;
	}

	/**
	 * Creates a Payment for the given Invoice on ZOHO
	 */
	protected function createPayment($invoice_id, $sub)
	{
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Payment></Payment>');
		
		$xml->InvoiceID = $invoice_id;
		$xml->Mode = '4'; // PayPal
		$xml->Amount = $sub->gross_amount;
		$xml->Description = $sub->processor_key;
		
		$data = $this->data;
		$data['XMLString'] = $xml->asXML();
		
		$url = 'https://invoice.zoho.com/api/payments/create';
		
		$answer = $this->sendRequest($url, $data, 'POST');
		$answer = new SimpleXMLElement($answer);
	}

	/**
	 * Stores into Akeeba Subs the invoice id to track invoices
	 */
	protected function syncInvoice($row, $invoice_id, $invoice_num)
	{
		// Do not issue invoices for free subscriptions
		if ($row->gross_amount < 0.01) return;

		// Should we handle this subscription?
		$generateAnInvoice 	= ($row->state == "C");
		$whenToGenerate 	= $this->params->get('generatewhen','0');
		
		if ($whenToGenerate == 1) 
		{
			// Handle new subscription, even if they are not yet enabled
			$specialCasePending = in_array($row->state, array('P','C')) && !$row->enabled;
			$generateAnInvoice = $generateAnInvoice || $specialCasePending;
		}
		
		// If the payment is over a week old do not generate an invoice. This
		// prevents accidentally creating an invoice for pas subscriptions not
		// handled by ZohoInvoice
		jimport('joomla.utilities.date');
		$jCreated 	= new JDate($row->created_on);
		$jNow 		= new JDate();
		$dateDiff 	= $jNow->toUnix() - $jCreated->toUnix();
		
		if ( $dateDiff > 604800) return;

		// Only handle not expired subscriptions
		if ( $generateAnInvoice ) 
		{
			$db = JFactory::getDBO();

			// Check if there is an invoice for this subscription already
			$query = $db->getQuery(true)
				->select('*')
				->from('#__akeebasubs_invoices')
				->where($db->qn('akeebasubs_subscription_id').' = '.$db->q($row->akeebasubs_subscription_id));
			$db->setQuery($query);
			$oldInvoices = $db->loadObjectList('akeebasubs_subscription_id');
			
			if (count($oldInvoices) > 0) {
				return;
			}

			$file = $this->downloadInvoice($invoice_id);

			$send = 'NULL';
			if ($this->params->get('send', 1)) {
				$send = $jNow->toSql();
			}

			// Create an Akeeba Subscriptions invoice record
			$object = (object)array(
				'akeebasubs_subscription_id'	=> $row->akeebasubs_subscription_id,
				'extension'						=> 'zohoinvoice',
				'invoice_no'					=> $invoice_id,
				'display_number'				=> $invoice_num,
				'invoice_date'					=> $jNow->toSql(),
				'enabled'						=> 1,
				'created_on'					=> $jNow->toSql(),
				'created_by'					=> $row->user_id,
				'filename'						=> $file,
				'sent_on'						=> $send
			);
			$db->insertObject('#__akeebasubs_invoices', $object, 'akeebasubs_subscription_id');
		}
	}

	protected function downloadInvoice($invoice_id)
	{
		if ($this->params->get('download', 0))
		{
			$partial_path = $this->params->get('downloadpath', '/media/invoices/');
			$path = JPATH_SITE . '/' . $partial_path . '/' . $invoice_id . '.pdf';

			// Search Customer
			$data = $this->data;
			$data['InvoiceID'] = $invoice_id;

			$url = 'https://invoice.zoho.com/api/invoices/pdf';

			$answer = $this->sendRequest($url, $data, 'GET');
			JFile::write($path, $answer);

			return realpath($partial_path . '/' . $invoice_id . '.pdf');
		}

		return false;
	}

	/**
	 * Get an Akeeba Subscriptions User
	 */
	protected function getUser($user_id)
	{
		$kuser = FOFModel::getTmpInstance('Users', 'AkeebasubsModel')->user_id($user_id)->getFirstItem();

		return $kuser;
	}

	/**
	 * Check if a country code is in the EU
	 */
	protected function isEu($country)
	{
		if (in_array($country, $this->eu_countries))
		{
			return true;
		}

		return false;
	}

	/**
	 * Sends the actual request to the REST webservice
	 */
	protected function sendRequest($url, $data, $type = 'POST')
	{
		$ch = curl_init();

		if ($type == 'POST')
		{
			$post_data = '';
			foreach ($data as $k => $v)
			{
				$post_data .= $k . '=' . urlencode($v) . '&';
			}
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/x-www-form-urlencoded"));
		}
		else
		{
			$url .= '?';
			foreach ($data as $key => $value)
			{
				$url .= $key . '=' . urlencode($value) . '&';
			}
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, 0);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

}
