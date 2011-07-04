<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsCcinvoices extends JPlugin
{
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange(KDatabaseRowDefault $row)
	{
		// Only handle not expired subscriptions
		if( ($row->state == "C") && $row->enabled ) {
			$db = JFactory::getDBO();
		
			// Get or create ccInvoices contact for user
			$contact_id = $this->getContactID($row->user_id);
			
			// @todo Load the existing invoices of the user
			$sql = 'SELECT * FROM `#__ccinvoices_invoices` WHERE `contact_id` = '.$contact_id;
			$db->setQuery($sql);
			$invoices = $db->loadObjectList();
			
			// Check if any of the invoices references this subscription
			if(count($invoices)) foreach($invoices as $invoice) {
				// Try to understand which subscription ID corresponds to this invoice
				$note = strip_tags($invoice->note);
				if(strstr($note, 'Subscription ID: ') === false) continue;
				$start = strpos($note, 'Subscription ID: '); //17
				$partial = substr($note, $start+17);
				$parts = explode(' ', $partial);
				$sub_id = $parts[0];
				// If there is an invoice for the current subscription, bail out
				if($sub_id == $row->id) return;
			}
			
			// Create new invoice
			$db->setQuery('SELECT max(`number`) FROM `#__ccinvoices_invoices`');
			$max1 = $db->loadResult();
			$db->setQuery('SELECT max(`custom_invoice_number`) FROM `#__ccinvoices_invoices`');
			$max2 = $db->loadResult();
			$invoice_number = max($max1, $max2);
			$invoice_number++;
			
			$subname = KFactory::tmp('admin::com.akeebasubs.model.levels')
					->id($row->akeebasubs_level_id)
					->getItem()
					->title;
			
			$invoice = (object)array(
				'number'		=> $invoice_number,
				'invoice_date'	=> $row->created_on,
				'status'		=> 4,
				'duedate'		=> $row->created_on,
				'numbercheck'	=> 0,
				'communication'	=> '',
				'discount'		=> 0,
				'subtotal'		=> $row->net_amount,
				'totaltax'		=> $row->tax_amount,
				'total'			=> $row->gross_amount,
				'quantity'		=> 1,
				'pname'			=> $subname.' subscription',
				'price'			=> $row->net_amount,
				'tax'			=> sprintf('%.2f', 100*($row->tax_amount/$row->net_amount)),
				'note'			=> "<p>Subscription ID: {$row->id}<br/>Paid with {$row->processor}, ref nr {$row->processor_key}</p>",
				'contact_id'	=> $contact_id
			);
			$db->insertObject('#__ccinvoices_invoices', $invoice, 'id');
			
			// @todo Try to send the invoice
		}
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
		
		$sql = 'SELECT `contact_id` FROM `#__ccinvoices_users` WHERE `user_id` = '.(int)$userid;
		$db->setQuery($sql);
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
		$juser = KFactory::tmp('admin::com.akeebasubs.model.jusers')
			->id($userid)
			->getItem();
			
		$list = KFactory::tmp('site::com.akeebasubs.model.users')
			->user_id($userid)
			->getList();
		if(!count($list)) {
			$kuser = KFactory::tmp('site::com.akeebasubs.model.users')->getItem();
		} else {
			$list->getIterator()->rewind();
			$kuser = $list->getIterator()->current();
		}
		
		// get the next contact number
		$db->setQuery('SELECT max(contact_number) FROM `#__ccinvoices_contacts`');
		$contact_number = $db->loadResult();
		if(!$contact_number) $contact_number = 0;
		$contact_number++;
		
		// get country/state names
		$dummy = KFactory::get('admin::com.akeebasubs.template.helper.listbox');
		$country = ComAkeebasubsTemplateHelperListbox::$countries[$kuser->country];
		$state = ComAkeebasubsTemplateHelperListbox::$states[$kuser->state];
		if($state == 'N/A') $state = '';
		
		// Create contact
		$name = $juser->name;
		$contact = $name;
		$address = $kuser->address1."\n".
			(empty($kuser->address2) ? '' : $kuser->address2."\n").
			$kuser->zip." ".$kuser->city."\n".
			(empty($state) ? '' : "$state\n") .
			$country;
		$email = $juser->email;
		
		$sql = 'REPLACE INTO `#__ccinvoices_contacts` (`name`,`contact`,`contact_number`,`address`,`email`) VALUES ('.
			$db->quote($name).', '.
			$db->quote($contact).', '.
			$db->quote($contact_number).', '.
			$db->quote($address).', '.
			$db->quote($email).
		')';
		$db->setQuery($sql);
		$db->query();
		
		$id = $db->insertid();
		
		$sql = 'REPLACE INTO `#__ccinvoices_users` (`user_id`,`contact_id`) VALUES ('.
			$userid.', '.$id.')';
		$db->setQuery($sql);
		$db->query();
		
		return $id;
	}

}