<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentExact extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'exact',
			'ppKey'			=> 'PLG_AKPAYMENT_EXACT_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/exact.jpg'
		));

		parent::__construct($subject, $config);
	}

	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * "paymentForm" and a visible submit button.
	 *
	 * @param string $paymentmethod
	 * @param JUser $user
	 * @param AkeebasubsTableLevel $level
	 * @param AkeebasubsTableSubscription $subscription
	 * @return string
	 */
	public function onAKPaymentNew($paymentmethod, $user, $level, $subscription)
	{
		if($paymentmethod != $this->ppName) return false;
		
		$nameParts = explode(' ', trim($user->name), 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
		
		$lang = strtolower(substr(JFactory::getLanguage()->getTag(), 0, 2));
		$pageId = '';
		if(in_array($lang, array('en', 'fr'))) {
			$pageId = trim($this->params->get('page_id_' . $lang, ''));
		}
		if(empty($pageId)) {
			$defaultLang = trim($this->params->get('default_lang', 'en'));
			$pageIdEn = trim($this->params->get('page_id_en', ''));
			$pageIdFr = trim($this->params->get('page_id_fr', ''));
			if(empty($pageIdEn)) {
				$defaultLang = 'fr';
			}else if(empty($pageIdEn)) {
				$defaultLang = 'en';
			}
			if($defaultLang == 'fr') {
				$pageId = $pageIdFr;
			} else {
				$pageId = $pageIdEn;
			}
		}

		$taxable = ($subscription->tax_amount > 0) ? 'YES' : 'NO';
		$data = (object)array(
			'url'				=> $this->getPaymentURL(),
			'x_login'			=> $pageId,
			'x_fp_sequence'		=> rand(1, 60000),
			'x_fp_timestamp'	=> time(),
			'x_currency_code'	=> strtoupper(AkeebasubsHelperCparams::getParam('currency', 'CAD')),
			'x_amount'			=> sprintf('%.2f',$subscription->gross_amount),
			'x_show_form'		=> 'PAYMENT_FORM',
			'x_line_item'		=> $level->akeebasubs_level_id . '<|>' . $level->title . '<|><|>1<|>' . sprintf('%.2f',$subscription->net_amount) . '<|>' . $taxable . '<|>',
			'x_po_num'			=> $subscription->akeebasubs_subscription_id,
			'x_first_name'		=> $firstName,
			'x_last_name'		=> $lastName,
			'x_address'			=> trim($kuser->address1),
			'x_city'			=> trim($kuser->city),
			'x_zip'				=> trim($kuser->zip),
			'x_country'			=> AkeebasubsHelperSelect::decodeCountry(trim($kuser->country)),
			'x_email'			=> trim($user->email),
			'x_relay_response'	=> 'TRUE',
			'x_relay_url'		=> JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=exact'
		);
		
		// Company
		if($kuser->isbusiness) {
			$data->x_company = trim($kuser->businessname);
		}
		// State
		$state = trim($kuser->state);
		if(!empty($state)) {
			$data->x_state = $state;
		}
		// Tax
		if($taxable == 'YES') {
			$data->x_tax = sprintf('%.2f', $subscription->tax_amount);
		}
		// Sandbox
		$sandbox = $this->params->get('sandbox', 0);
		if($sandbox) {
			$data->x_test_request = 'TRUE';
		} else {
			$data->x_test_request = 'FALSE';
		}
		// Hash code
		$hash = hash_hmac("md5",
				$data->x_login. "^" .
				$data->x_fp_sequence . "^" .
				$data->x_fp_timestamp . "^" .
				$data->x_amount . "^" .
				$data->x_currency_code,
				trim($this->params->get('transaction_key', '')));
		$data->x_fp_hash = $hash;

		@ob_start();
		include dirname(__FILE__).'/exact/form.php';
		$html = @ob_get_clean();

		return $html;
	}


	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['x_po_num'];
			$subscription = null;
			if($id > 0) {
				$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->setId($id)
					->getItem();
				if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id) ) {
					$subscription = null;
					$isValid = false;
				}
			} else {
				$isValid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'There is no valid subscription ID';
		}
		
		// Check sandbox
		if($isValid) {
			$sandbox = $this->params->get('sandbox', 0);
			if(strtoupper($data['x_test_request']) == $sandbox) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Sandbox is not set correctly";
			}
		}
		
		// Check that transaction has not been previously processed
		if($isValid) {
			if($data['x_trans_id'] == $subscription->processor_key && $subscription->state == 'C') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not processed this transaction twice";
			}
		}
		
		// Check currency
		if($isValid) {
			if(strtoupper($data['x_currency_code']) != strtoupper(AkeebasubsHelperCparams::getParam('currency', 'CAD'))) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Currency is not correct";
			}
		}
		
		// Check amount
		if($isValid) {
			if($isValid && !is_null($subscription)) {
				$mc_gross = floatval($data['x_amount']);
				$gross = $subscription->gross_amount;
				if($mc_gross > 0) {
					// A positive value means "payment". The prices MUST match!
					// Important: NEVER, EVER compare two floating point values for equality.
					$isValid = ($gross - $mc_gross) < 0.01;
				}
				if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
			}
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);
		
		// Fraud attempt? Do nothing more!
		if(!$isValid) {
			$error_url = 'index.php?option='.JRequest::getCmd('option').
				'&view=level&slug='.$subscription->slug.
				'&layout='.JRequest::getCmd('layout','default');
			$error_url = JRoute::_($error_url,false);
			JFactory::getApplication()->redirect($error_url, $data['akeebasubs_failure_reason'], 'error');
			return false;
		}
		
		// Is payment successful?
		if($data['x_response_code'] == 1) {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['x_trans_id'],
				'state'							=> $newStatus,
				'enabled'						=> 0
		);
		JLoader::import('joomla.utilities.date');
		if($newStatus == 'C') {
			$this->fixDates($subscription, $updates);
		}
		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array(
			$subscription
		));
		
		// Redirect the user to the "thank you" page
		$thankyouUrl = JRoute::_('index.php?option=com_akeebasubs&view=message&slug=' . $subscription->slug . '&layout=order&subid=' . $subscription->akeebasubs_subscription_id, false);
		JFactory::getApplication()->redirect($thankyouUrl);
	}

	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox', 0);
		if($sandbox) {
			return 'https://rpm.demo.e-xact.com/payment';
		} else {
			return 'https://checkout.e-xact.com/payment';
		}
	}
	
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$hashCode = md5(trim($this->params->get('response_key','')) .
				$data['x_login'] .
				$data['x_trans_id'] .
				$data['x_amount']);
		return $hashCode == $data['x_MD5_Hash'];
	}
}