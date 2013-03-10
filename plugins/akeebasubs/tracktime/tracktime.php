<?php
/**
 * @package    Tracktime
 * @copyright  Copyright (c)2011-2013 Davide Tampellini
 * @license    GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsTracktime extends JPlugin
{
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;

		// no pro version, return
		if(!file_exists(JPATH_ROOT.'/administrator/components/com_tracktime/views/invoices/tmpl/default.php')){
			return;
		}

		// params missing
		if(!$this->params->getValue('invoice_template') || !$this->params->getValue('mail_template')){
			return;
		}

		// Load the plugin's language files
		$lang = JFactory::getLanguage();
		$lang->load('plg_akeebasubs_tracktime', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('plg_akeebasubs_tracktime', JPATH_ADMINISTRATOR, null, true);
		// TrackTime language files
		$lang->load('com_tracktime', JPATH_SITE, 'en-GB', true);
		$lang->load('com_tracktime', JPATH_SITE, $lang->getDefault(), true);
		$lang->load('com_tracktime', JPATH_SITE, null, true);
		$lang->load('com_tracktime', JPATH_ADMINISTRATOR, 'en-GB', true);
		$lang->load('com_tracktime', JPATH_ADMINISTRATOR, $lang->getDefault(), true);
		$lang->load('com_tracktime', JPATH_ADMINISTRATOR, null, true);

		// Do not issue invoices for free subscriptions
		if($row->gross_amount < 0.01) return;

		// Should we handle this subscription?
		$generateAnInvoice = ($row->state == "C") && $row->enabled;
		$whenToGenerate = $this->params->get('generatewhen','0');
		if($whenToGenerate == 1) {
			// Handle new subscription, even if they are not yet enabled
			$specialCasePending = in_array($row->state, array('P','C')) && !$row->enabled;
			$generateAnInvoice = $generateAnInvoice || $specialCasePending;
		}

		// If the payment is over a week old do not generate an invoice. This
		// prevents accidentally creating an invoice for past subscriptions not
		// handled by TrackTime
		JLoader::import('joomla.utilities.date');
		$jCreated = new JDate($row->created_on);
		$jNow = new JDate();
		$dateDiff = $jNow->toUnix() - $jCreated->toUnix();
		if($dateDiff > 604800) return;

		// Only handle not expired subscriptions
		if(!$generateAnInvoice ) return;

		$db = JFactory::getDBO();

		// Get or create ccInvoices contact for user
		$contact_id = $this->getContactID($row->user_id);

		// Check if there is an invoice for this subscription already
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from('#__akeebasubs_invoices')
			->where($db->qn('akeebasubs_subscription_id').' = '.$db->q($row->akeebasubs_subscription_id));
		$db->setQuery($query);
		$oldInvoices = $db->loadResult();
		if($oldInvoices) return;

		// Create new invoice
		$dummy 					 = new stdClass();
		$dummy->in_emission_date = date('Y-m-d');
		$dummy->in_number 		 = 0;

		$invoice_number = FOFModel::getTmpInstance('Invoices', 'TracktimeModel')->getLastInvoiceNumber($dummy);
		$invoice_number++;

		//save new invoice number inside the component params
		$params = JComponentHelper::getParams('com_tracktime');
		$params->set('lastInvoice', $invoice_number);
		$extension = JTable::getInstance('extension');
		$component_id = $extension->find(array('element' => 'com_tracktime',
											   'type'    => 'component'));

		$extension->load($component_id);
		$extension->params = (string) $params;
		$extension->check();
		$rc = $extension->store();

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

			/* CUSTOM TEXT - PERSONAL HACK
			if($country != 'IT' && $isbusiness)
			{
				$euvatnote = $this->params->getValue('euvatnote');
				$note .= "<p>$euvatnote</p>";
			}*/

			if($inEU && $isbusiness && $viesregistered) {
				if(version_compare(JVERSION, '3.0', 'ge')) {
					$euvatnote = $this->params->get('euvatnote', 'VAT liability is transferred to the recipient, pursuant EU Directive nr 2006/112/EC and local tax laws implementing this directive.');
				} else {
					$euvatnote = $this->params->getValue('euvatnote', 'VAT liability is transferred to the recipient, pursuant EU Directive nr 2006/112/EC and local tax laws implementing this directive.');
				}
				$note .= "<p>$euvatnote</p>";
			}
		}

		// Create the invoice main record
		$invoice = (object)array(
			'in_number'			=> $invoice_number,
			'in_state'			=> 2,
			'in_id_customers'	=> $contact_id,
			'in_title'			=> $description,
			'in_emission_date'	=> $jNow->toSql(),
			'in_due_date'		=> $jNow->toSql(),
			'in_total'			=> $row->gross_amount,
			'in_paid'			=> $row->gross_amount,
			'in_footer_notes'	=> $note
		);

		$db->insertObject('#__tracktime_invoices', $invoice, 'id_invoices');
		$id_invoices = $db->insertid();

		// Create invoice items
		$invoice_items = (object) array(
			'ir_id_invoices'	=> $id_invoices,
			'ir_descr'			=> $description,
			'ir_quantity'		=> 1,
			'ir_price'			=> $row->net_amount,
			'ir_tax'			=> $taxrate,
			'ir_tax_amount'		=> $row->tax_amount,
			'ir_discount'		=> 0,
			'ir_discount_type'	=> 0,
			'ir_net_amount'		=> $row->net_amount,
			'ir_amount'			=> $row->gross_amount
		);

		$db->insertObject('#__tracktime_invoices_rows', $invoice_items, 'id_invoices_rows');

		// Create an Akeeba Subscriptions invoice record
		$object = (object)array(
			'akeebasubs_subscription_id'	=> $row->akeebasubs_subscription_id,
			'extension'						=> 'tracktime',
			'invoice_no'					=> $invoice_number,
			'display_number'				=> $invoice_number,
			'invoice_date'					=> $jNow->toSql(),
			'enabled'						=> 1,
			'created_on'					=> $jNow->toSql(),
			'created_by'					=> $row->created_by,
		);
		$db->insertObject('#__akeebasubs_invoices', $object, 'akeebasubs_subscription_id');

		$mail_subject = $this->params->getValue('mail_subject', '');
		if(!$mail_subject){
			$mail_subject = JText::_('PLG_AKEEBASUBS_TRACKTIME_MAILSUBJECT').' '.$description;
		}
		else{
			$mail_subject = $this->parseDescription($mail_subject, $row, $level);
		}

		$config['input']['id_invoices'] = $id_invoices;
		$config['input']['mail_template'] = $this->params->getValue('mail_template');
		$config['input']['invoice_template'] = $this->params->getValue('invoice_template');
		$config['input']['cu_email'] = JFactory::getUser($row->user_id)->email;
		$config['input']['mail_subject'] = $mail_subject;

		FOFController::getTmpInstance('com_tracktime', 'invoices', $config)
			->sendMail();
	}

	private function getContactID($userid)
	{
		$db = JFactory::getDBO();

		$query = $db->getQuery(true)
			->select($db->qn('id_customers'))
			->from($db->qn('#__tracktime_customers'))
			->where($db->qn('cu_uid').' = '.$db->q($userid));
		$db->setQuery($query);
		$id = $db->loadResult();
		if(!$id) {
			$id = $this->createContact($userid);
		} else {
			$id = $this->createContact($userid, $id);
		}

		return $id;
	}

	private function createContact($userid, $contact_id = null)
	{
		$db = JFactory::getDBO();

		// Load user data
		$juser = JFactory::getUser($userid);

		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($userid)
			->getFirstItem();

		// get country/state names
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/select.php';
		$country = AkeebasubsHelperSelect::$countries[$kuser->country];
		if(array_key_exists($kuser->state, AkeebasubsHelperSelect::$states)) {
			$state = AkeebasubsHelperSelect::$states[$kuser->state];
		} else {
			$state = '';
		}
		if($state == 'N/A') $state = '';

		// Create contact
		if($kuser->isbusiness) {
			$cu_name = $kuser->businessname;
			$cu_vat  = ($kuser->country == 'GR' ? 'EL' : $kuser->country).' '.$kuser->vatnumber;
		} else {
			$cu_name = $juser->name;
			$cu_vat  = '';
		}

		$cu_address  = $kuser->address1."\n".(empty($kuser->address2) ? '' : $kuser->address2);
		$cu_town	 = $kuser->zip." ".$kuser->city;
		$cu_district = (empty($state) ? '' : $state).' '.$country;
		$cu_email 	 = $juser->email;

		$contact = array(
			'cu_name'		=> $cu_name,
			'cu_address'	=> $cu_address,
			'cu_town'		=> $cu_town,
			'cu_district'	=> $cu_district,
			'cu_email'		=> $cu_email,
			'cu_vat'		=> $cu_vat,
			'cu_uid'		=> $userid,
			'enabled'		=> 1
		);

		if(is_null($contact_id)) {
			// CREATE NEW CONTACT
			$customer = (object)$contact;
			$customer->cu_note = 'Customer created by Akeeba Subscriptions';

			$db->insertObject('#__tracktime_customers', $customer);
			$id = $db->insertid();

			return $id;
		} else {
			// UPDATE EXISTING CONTACT
			$query = $db->getQuery(true)
				->update($db->qn('#__tracktime_customers'))
				->where($db->qn('cu_uid').' = '.$db->q($contact_id));
			foreach($contact as $k => $v) {
				$query->set($db->qn($k).' = '.$db->q($v));
			}
			$db->setQuery($query);
			$db->execute();

			return $contact_id;
		}
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
	
	public function onAKGetInvoicingOptions()
	{
		JLoader::import('joomla.filesystem.file');
		$enabled = JFile::exists(JPATH_ROOT.'/administrator/components/com_tracktime/views/invoices/tmpl/default.php');
		return array(
			'extension'		=> 'tracktime',
			'title'			=> 'TrackTime',
			'enabled'		=> $enabled,
			'backendurl'	=> null, // @todo Davide must provide this
			'frontendurl'	=> null, // @todo Davide must provide this
		);
	}
}