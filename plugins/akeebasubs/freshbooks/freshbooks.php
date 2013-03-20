<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

class plgAkeebasubsFreshbooks extends JPlugin
{
	protected $notes = '';
	protected $terms = '';
	protected $emailMessage = '';
	protected $autoPayment = false;
	protected $generateWhenCompleted = true;
	
	public function __construct(& $subject, $config = array())
	{
		parent::__construct($subject, 'freshbooks', $config);
		
		// Load helper class
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akeebasubs_freshbooks', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akeebasubs_freshbooks', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akeebasubs_freshbooks', JPATH_ADMINISTRATOR, null, true);
		
		// Get params
		$configParams = @json_decode($config['params']);
		$url = trim($configParams->url);
		$token = trim($configParams->token);
		$this->notes = trim($configParams->notes);
		$this->terms = trim($configParams->terms);
		$this->emailMessage = $configParams->message;
		$this->autoPayment = (boolean)$configParams->payment;
		$this->generateWhenCompleted = !(boolean)$configParams->generatewhen;
		
		// Init FreshBooks
		include_once 'library/FreshBooks/HttpClient.php';
		include_once 'library/FreshBooks/Client.php';
		include_once 'library/FreshBooks/Invoice.php';
		include_once 'library/FreshBooks/Payment.php';
		FreshBooks_HttpClient::init($url, $token);
	}
	
	/**
	 * Called whenever a subscription is modified. Namely, when its enabled status,
	 * payment status or valid from/to dates are changed.
	 */
	public function onAKSubscriptionChange($row, $info)
	{
		if(is_null($info['modified']) || empty($info['modified'])) return;

		// Do not issue invoices for free subscriptions
		if($row->gross_amount < 0.01) return;

		// Should we handle this subscription?
		$generateAnInvoice = ($row->state == "C");
		if(! $this->generateWhenCompleted) {
			// Handle new subscription, even if they are not yet enabled
			$specialCasePending = in_array($row->state, array('P','N')) && !$row->enabled;
			$generateAnInvoice = $generateAnInvoice || $specialCasePending;
		}
		
		// If the payment is over a week old do not generate an invoice. This prevents
		// accidentally creating an invoice for past subscriptions not handled.
		JLoader::import('joomla.utilities.date');
		$jCreated = new JDate($row->created_on);
		$jNow = new JDate();
		$dateDiff = $jNow->toUnix() - $jCreated->toUnix();
		if($dateDiff > 604800) return;
				
		// Only handle not expired subscriptions
		if( $generateAnInvoice ) {
			// Init variables
			$today = $jNow->toFormat("%Y-%m-%d");
			$user = JUser::getInstance($row->user_id);
			$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
				->user_id($row->user_id)
				->getFirstItem();
			$level = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($row->akeebasubs_level_id)
				->getItem();
			$nameParts = explode(' ', trim($user->name), 2);
			$firstName = $nameParts[0];
			if(count($nameParts) > 1) {
				$lastName = $nameParts[1];
			} else {
				$lastName = '';
			}
		
			// Find the client if the account already exists
			$clientId = -1;
			$clients = array();
			$fbClientHelper = new FreshBooks_Client();
			if($fbClientHelper->listing($clients,
					$resultInfo,
					1,
					1,
					array('email' => $user->email)
				)) {
				if(sizeof($clients) > 0) {
					$client = $clients[0];
					$clientId = $client->clientId;
				}
			}

			// Create new client if the account was not found
			if($clientId < 0) {
				$newClient = new FreshBooks_Client();
				$newClient->email = $user->email;
				$newClient->firstName = $firstName;
				$newClient->lastName = $lastName;
				if(! empty($kuser->businessname)) {
					$newClient->organization = $kuser->businessname;	
				}
				$newClient->pStreet1 = $kuser->address1;
				$newClient->pStreet2 = $kuser->address2;
				$newClient->pCity = $kuser->city;
				if(! empty($kuser->state)) {
					$newClient->pState = $kuser->state;	
				}
				$newClient->pCountry = AkeebasubsHelperSelect::decodeCountry($kuser->country);
				$newClient->pCode = $kuser->zip;
				if($newClient->create()){
					$clientId = $newClient->clientId;
				}
			}

			// Check if the invoice was already generated. The invoice is considered to exist if it
			// has the same puchase order number (poNumber).
			$invoiceId = -1;
			$poNumber = strtoupper($level->slug) . '#' . $row->akeebasubs_subscription_id; // purchase order number
			$invoiceExists = false;
			$invoices = array();
			$resultInfo = array();
			$fbInvoiceHelper = new FreshBooks_Invoice();
			$page = 1;
			while(true) {
				// Loop through all invoice for this client
				// and check the poNumber.
				if($fbInvoiceHelper->listing($invoices,
						$resultInfo,
						$page,
						100,
						array('clientId' => $clientId)
					)) {
					foreach($invoices as $invoice) {
						if($invoice->poNumber == $poNumber) {
							$invoiceExists = true;
							$invoiceId = $invoice->invoiceId;
							break;
						}
					}
					if($invoiceExists || !($resultInfo['page'] < $resultInfo['pages'])) {
						break;
					}
					$page++;
				}
			}

			// Create new invoice
			if(! $invoiceExists) {
				$invoice = new FreshBooks_Invoice();
				$invoice->clientId = $clientId;
				$invoice->date = $today;
				$invoice->currencyCode = strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'));
				$invoice->poNumber = $poNumber;
				if(! empty($this->notes)) {
					$invoice->notes = $this->notes;	
				}
				if(! empty($this->terms)) {
					$invoice->terms = $this->terms;	
				}
				$invoice->status = 'viewed';
				$invoice->lines = array(
					array(
						'name'			=> $level->title,
						'unitCost'		=> sprintf('%.2f', $row->net_amount),
						'quantity'		=> 1,
						'tax1Percent'	=> $row->tax_percent
					)
				);
				if($invoice->create()) {
					$invoiceId = $invoice->invoiceId;
				}	
			}
			
			if($invoiceId > 0) {
				if($invoice->status != 'paid' && $row->state == "C" && $this->autoPayment) {
					// If the subscription is paid, create a corresponding payment of FreshBooks
					// in order to mark it as paid. This consists of two steps:
					// 1) Add the amount as credit to the client.
					$credit = new FreshBooks_Payment();
					$credit->clientId = $clientId;
					$credit->amount = sprintf('%.2f', $row->gross_amount);
					$credit->date = $invoice->date;
					$credit->type = 'Credit';
					$credit->currencyCode = $invoice->currencyCode;
					// 2) Create the payment with the credit.
					if($credit->create()) {
						$payment = new FreshBooks_Payment();
						$payment->clientId = $clientId;
						$payment->invoiceId = $invoiceId;
						$payment->amount = $credit->amount;
						$payment->date = $invoice->date;
						$payment->currencyCode = $invoice->currencyCode;
						$payment->create();
					}
				}
				
				if(! $invoiceExists) {
					// Send the invoice
					$invoice->sendByEmail('', $this->emailMessage);	
				}
			}
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
}