<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsCcinvoices extends JPlugin
{
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;
		if(!array_key_exists('enabled', (array)$info['modified'])) return;

		// Load the plugin's language files
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_ccinvoices', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_ccinvoices', JPATH_ADMINISTRATOR, null, true);
		// ccInvoices language files
		$lang->load('com_ccinvoices', JPATH_SITE, 'en-GB', true);
		$lang->load('com_ccinvoices', JPATH_SITE, $lang->getDefault(), true);
		$lang->load('com_ccinvoices', JPATH_SITE, null, true);
		$lang->load('com_ccinvoices', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('com_ccinvoices', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
		$lang->load('com_ccinvoices', JPATH_ADMINISTRATOR, null, true);

		// Do not issue invoices for free subscriptions
		if($row->gross_amount < 0.01) return;

		// Only handle not expired subscriptions
		if( ($row->state == "C") && $row->enabled ) {
			$db = JFactory::getDBO();

			// Get or create ccInvoices contact for user
			$contact_id = $this->getContactID($row->user_id);

			// LEGACY CHECK -- Will be removed in a future release
			/*
			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__ccinvoices_invoices'))
				->where($db->qn('contact_id').' = '.$db->q($contact_id));
			$db->setQuery($query);
			$invoices = $db->loadObjectList();
			if(count($invoices)) foreach($invoices as $invoice) {
				// Try to understand which subscription ID corresponds to this invoice
				$note = strip_tags($invoice->note);
				if(strstr($note, 'Subscription ID: ') === false) continue;
				$start = strpos($note, 'Subscription ID: '); //17
				$partial = substr($note, $start+17);
				$parts = explode(' ', $partial);
				$sub_id = $parts[0];
				// If there is an invoice for the current subscription, bail out
				if($sub_id == $row->akeebasubs_subscription_id) return;
			}
			*/

			// Check if there is an invoice for this subscription already
			$query = FOFQueryAbstract::getNew($db)
				->select('COUNT(*)')
				->from('#__akeebasubs_invoices')
				->where($db->qn('akeebasubs_subscription_id').' = '.$db->q($row->akeebasubs_subscription_id));
			$db->setQuery($query);
			$oldInvoices = $db->loadResult();
			if($oldInvoices) return;

			// Load the ccInvoices configuration
			$query = $db->getQuery(true)
				->select('*')
				->from($db->qn('#__ccinvoices_configuration'));
			$db->setQuery($query, 0, 1);
			$ccConfig = $db->loadObject();

			// Create new invoice
			$query = $db->getQuery(true)
				->select('MAX('.$db->qn('number').')')
				->from($db->qn('#__ccinvoices_invoices'));
			$db->setQuery($query);
			$max1 = $db->loadResult();
			
			$query = $db->getQuery(true)
				->select('MAX('.$db->qn('custom_invoice_number').')')
				->from($db->qn('#__ccinvoices_invoices'));
			$db->setQuery($query);
			$max2 = $db->loadResult();
			$invoice_number = max($max1, $max2);
			$invoice_number++;

			if($invoice_number < $ccConfig->invoice_start) $invoice_number = $ccConfig->invoice_start;

			$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
					->setId($row->akeebasubs_level_id)
					->getItem();
			$subname = $level->title;

			if(version_compare(JVERSION, '3.0', 'ge')) {
				$description = $this->params->get('description','');
			} else {
				$description = $this->params->getValue('description','');
			}
			if(empty($description)) {
				$suffix = JText::_('PLG_AKEEBASUBS_CCINVOICES_SUFFIX');
				if(strtoupper($suffix) == 'PLG_AKEEBASUBS_CCINVOICES_SUFFIX') $suffix = ' subscription';
				$description = $subname.$suffix;
			} else {
				$description = $this->parseDescription($description, $row, $level);
			}

			if($row->tax_percent > 0) {
				$taxrate = $row->tax_percent;
			} else {
				$taxrate = 100*($row->tax_amount/$row->net_amount);
			}

			jimport('joomla.utilities.date');
			$jNow = new JDate();
			
			$note = "<p>Subscription ID: {$row->akeebasubs_subscription_id}<br/>Paid with {$row->processor}, ref nr {$row->processor_key}</p>";
			if(version_compare(JVERSION, '3.0', 'ge')) {
				$euvatoption = $this->params->get('euvatoption', 0);
			} else {
				$euvatoption = $this->params->getValue('euvatoption', 0);
			}
			if($euvatoption && ($row->tax_amount < 0.01)) {
				$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
					->user_id($row->user_id)
					->getFirstItem();
				
				$country = $kuser->country;
				$isbusiness = $kuser->isbusiness;
				$viesregistered = $kuser->viesregistered;
				$inEU = in_array($country, array('AT','BE','BG','CY','CZ','DK','EE','FI','FR','GB','DE','GR','HU','IE','IT','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE'));
				
				if($inEU && $isbusiness && $viesregistered) {
					if(version_compare(JVERSION, '3.0', 'ge')) {
						$euvatnote = $this->params->get('euvatnote', 'VAT liability is transferred to the recipient, pursuant EU Directive nr 2006/112/EC and local tax laws implementing this directive.');
					} else {
						$euvatnote = $this->params->getValue('euvatnote', 'VAT liability is transferred to the recipient, pursuant EU Directive nr 2006/112/EC and local tax laws implementing this directive.');
					}
					$note .= "<p>$euvatnote</p>";
				}
			}

			$invoice = (object)array(
				'number'		=> $invoice_number,
				'invoice_date'	=> $jNow->toSql(),
				'status'		=> 4,
				'duedate'		=> $jNow->toSql(),
				'numbercheck'	=> 0,
				'invoice_sent_date'	=> $jNow->toSql(),
				'communication'	=> '',
				'discount'		=> $row->discount_amount * 1.0,
				'subtotal'		=> $row->prediscount_amount,
				'totaltax'		=> $row->tax_amount,
				'total'			=> $row->gross_amount,
				'quantity'		=> 1,
				'pname'			=> $description,
				'price'			=> $row->net_amount,
				'tax'			=> sprintf('%.2f', $row->tax_percent),
				'note'			=> $note,
				'contact_id'	=> $contact_id
			);
			$db->insertObject('#__ccinvoices_invoices', $invoice, 'id');
			$id = $db->insertid();

			// Create an invoice payment
			$invoicePayment = (object)array(
				'inv_id'		=> $id,
				'method'		=> 'akeebasubs',
				'transaction_id'=> $row->processor.'/'.$row->processor_key,
				'pdate'			=> $jNow->toSql(),
				'status'		=> 1
			);
			$db->insertObject('#__ccinvoices_payment', $invoicePayment, 'id');

			// Create an Akeeba Subscriptions invoice record
			$object = (object)array(
				'akeebasubs_subscription_id'	=> $row->akeebasubs_subscription_id,
				'invoice_no'					=> $id,
				'invoice_date'					=> $jNow->toSql(),
				'enabled'						=> 1,
				'created_on'					=> $jNow->toSql(),
				'created_by'					=> $row->created_by,
			);
			$db->insertObject('#__akeebasubs_invoices', $object, 'akeebasubs_subscription_id');

			// Try to send the invoice
			if(!class_exists('ccInvoicesControllerInvoices')) {
				jimport('joomla.filesystem.file');
				$path = JPATH_ADMINISTRATOR.'/components/com_ccinvoices/controllers/invoices.php';
				if(JFile::exists($path)) {
					require_once $path;
				} else {
					return;
				}
			}

			$controller = new ccInvoicesControllerInvoices;
			$file_path = $this->createInvoice($id);
			$controller->sendEmail(0,1,0,$file_path,$id);
		}
	}

	/**
	 * Called whenever a subscription is displayed on the front-end list
	 *
	 * @param AkeebasubsTableSubscription $row
	 */
	public function onAKSubscriptionsList($row)
	{
		// @todo
		// index.php?option=com_ccinvoices&view=ccinvoices&task=download&id=1
	}

	/**
	 * Called whenever the administrator asks to refresh integration status.
	 *
	 * @param $user_id int The Joomla! user ID to refresh information for.
	 */
	public function onAKUserRefresh($user_id)
	{
		// Do nothing
	}

	private function getContactID($userid)
	{
		$db = JFactory::getDBO();

		$query = $db->getQuery(true)
			->select($db->qn('contact_id'))
			->from($db->qn('#__ccinvoices_users'))
			->where($db->qn('user_id').' = '.$db->q($userid));
		$db->setQuery($query);
		$id = $db->loadResult();

		if(!$id) {
			$id = $this->createContact($userid);
		}

		return $id;
	}

	private function createContact($userid)
	{
		$db = JFactory::getDBO();

		// Load user data
		$juser = JFactory::getUser($userid);

		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($userid)
			->getFirstItem();

		// get the next contact number
		$query = $db->getQuery(true)
				->select('MAX('.$db->qn('contact_number').')')
				->from($db->qn('#__ccinvoices_contacts'));
		$db->setQuery($query);
		$contact_number = $db->loadResult();
		if(!$contact_number) $contact_number = 0;
		$contact_number++;

		// get country/state names
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/select.php';
		$country = AkeebasubsHelperSelect::$countries[$kuser->country];
		$state = AkeebasubsHelperSelect::$states[$kuser->state];
		if($state == 'N/A') $state = '';

		// Create contact
		$name = $juser->name;
		$contact = $name;
		$address = $kuser->address1."\n".
			(empty($kuser->address2) ? '' : $kuser->address2."\n").
			$kuser->zip." ".$kuser->city."\n".
			(empty($state) ? '' : "$state\n") .
			$country .
			(empty($kuser->vatnumber) ? '' : "\nVAT ".$kuser->vatnumber);
		$email = $juser->email;
		
		$contact = (object)array(
			'name'				=> $name,
			'contact'			=> $contact,
			'contact_number'	=> $contact_number,
			'address'			=> $address,
			'email'				=> $email
		);
		$db->insertObject('#__ccinvoices_contacts', $contact);

		$db->setQuery($query);
		$db->query();

		$id = $db->insertid();

		$query = $db->getQuery(true)
			->delete($db->qn('#__ccinvoices_users'))
			->where($db->qn('user_id').' = '.$db->q($userid));
		$db->setQuery($query);
		$db->query();
		
		$query = $db->getQuery(true)
			->insert($db->qn('#__ccinvoices_users'))
			->columns(array(
				$db->qn('user_id'),
				$db->qn('contact_id')
			))->values(
				$db->q($userid).', '.$db->q($id)
			);
		$db->setQuery($query);
		$db->query();

		return $id;
	}

	private function createInvoice($id)
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__ccinvoices_configuration'))
			->where($db->qn('id').' = '.$db->q('1'));
		$db->setQuery($query, 0, 1);
		$config = $db->loadObject();
		
        require_once(JPATH_ADMINISTRATOR.'/components/com_ccinvoices/assets/tcpdf/tcpdf.php');
        require_once(JPATH_ADMINISTRATOR.'/components/com_ccinvoices/assets/tcpdf/config/lang/eng.php');
		
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Akeeba Subscriptions');
        $pdf->SetTitle('Invoice');
        $pdf->SetSubject('Invoice');
        $pdf->SetKeywords('Invoice');
        // set default header data
        //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        // set default monospaced font
        //$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        //set margins
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        //set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        //set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('times', '', 8);
        $pdf->AddPage();
		require_once JPATH_ADMINISTRATOR.'/components/com_ccinvoices/models/invoices.php';
        $model = new ccInvoicesModelInvoices;
		$template=$model->gettemplatelayout($id);
        $v=$pdf->writeHTML($template, true, false, false, false, '');
		
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn('#__ccinvoices_invoices'))
			->where($db->qn('id').' = '.$db->q($id));
		$db->setQuery($query, 0, 1);
		$invRow = $db->loadObject();
		
		if($config->invoice_format != "")
		{
			$file_name = $model->getInvoiceNumberFormat($invRow->number).".pdf";
		} else {
			$file_name = $invRow->number.".pdf";
		}
        $file_path = JPATH_ADMINISTRATOR.'/components/com_ccinvoices/assets/'.$file_name;
        $pdf->Output($file_path, 'F');
		return $file_path;
	}

	/**
	 * Parses a description string
	 *
	 * @param string $description
	 * @param AkeebasubsTableSubscription $row
	 * @param AkeebasubsTableLevel $level
	 */
	private function parseDescription($description, $row, $level)
	{
		// Get the user object for this subscription
		$user = JFactory::getUser($row->user_id);

		// Get the extra user parameters object for the subscription
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($row->user_id)
			->getFirstItem();

		// Merge the user objects
		$userdata = array_merge((array)$user, (array)($kuser->getData()));

		$text = $description;

		// Create and replace merge tags for subscriptions. Format [SUB:KEYNAME]
		foreach((array)($row->getData()) as $k => $v) {
			if(is_array($v) || is_object($v)) continue;
			if(substr($k,0,1) == '_') continue;
			if($k == 'akeebasubs_subscription_id') $k = 'id';
			$tag = '[SUB:'.strtoupper($k).']';
			$text = str_replace($tag, $v, $text);
		}

		// Create and replace merge tags for subscription level. Format [LEVEL:KEYNAME]
		foreach((array)($level->getData()) as $k => $v) {
			if(is_array($v) || is_object($v)) continue;
			if(substr($k,0,1) == '_') continue;
			if($k == 'akeebasubs_subscription_id') $k = 'id';
			$tag = '[LEVEL:'.strtoupper($k).']';
			$text = str_replace($tag, $v, $text);
		}

		// Create and replace merge tags for user data. Format [USER:KEYNAME]
		foreach($userdata as $k => $v) {
			if(is_object($v) || is_array($v)) continue;
			if(substr($k,0,1) == '_') continue;
			if($k == 'akeebasubs_subscription_id') $k = 'id';
			$tag = '[USER:'.strtoupper($k).']';
			$text = str_replace($tag, $v, $text);
		}

		// Create and replace merge tags for custom fields data. Format [CUSTOM:KEYNAME]
		if(array_key_exists('params', $userdata)) {
			$custom = json_decode($userdata['params']);
			if(!empty($custom)) foreach($custom as $k => $v) {
				if(substr($k,0,1) == '_') continue;
				$tag = '[CUSTOM:'.strtoupper($k).']';
				$text = str_replace($tag, $v, $text);
			}
		}

		return $text;
	}
}