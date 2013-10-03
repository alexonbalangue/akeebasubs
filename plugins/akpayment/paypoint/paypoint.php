<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentPayPoint extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'paypoint',
			'ppKey'			=> 'PLG_AKPAYMENT_PAYPOINT_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/pp-logo.png',
		));

		parent::__construct($subject, $config);
	}

	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * 'paymentForm' and a visible submit button.
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

		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();

		$data = (object)array(
			'merchant'			=> $this->params->get('merchant_id', ''),
			'trans_id'			=> $subscription->akeebasubs_subscription_id,
			'amount'			=> sprintf('%.2f',$subscription->gross_amount),
			'currency'			=> strtoupper(AkeebasubsHelperCparams::getParam('currency','GBP')),
			'bill_name'			=> trim($user->name),
			'bill_addr_1'		=> trim($kuser->address1),
			'bill_addr_2'		=> trim($kuser->address2),
			'bill_city'			=> trim($kuser->city),
			'bill_company'		=> trim($kuser->businessname),
			'bill_country'		=> AkeebasubsHelperSelect::decodeCountry(trim($kuser->country)),
			'bill_post_code'	=> trim($user->zip),
			'bill_state'		=> trim($kuser->state),
			'bill_email'		=> trim($user->email),
			'test_status'		=> $this->getTestStatus(),
			'prod'				=> $level->title,
			'ssl_cb'			=> 'false',
			'merchant_logo'		=> $this->params->get('merchant_logo', ''),
			'callback'			=> $this->getCallbackURL()
		);
		
		if(strtolower(substr($data->callback, 0, 8)) == 'https://') {
			$data->ssl_cb = 'true';
		}
		$data->digest = md5($data->trans_id . $data->amount . $this->params->get('password', ''));

		@ob_start();
		include dirname(__FILE__).'/paypoint/form.php';
		$html = @ob_get_clean();

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		$isValid = true;

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['trans_id'];
			$subscription = null;
			if($id > 0) {
				$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->setId($id)
					->getItem();
				if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->akeebasubs_subscription_id != $id) ) {
					$subscription = null;
					$isValid = false;
				}
                $slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
                    ->setId($subscription->akeebasubs_level_id)
                    ->getItem()
                    ->slug;
			} else {
				$isValid = false;
                $slug = '';
			}
		}
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

		// Check that the transaction has not been previously processed
		if($isValid && $subscription->statue == 'C') {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = 'I will not process the same transaction twice';
		}

		// Check that the transaction has not been previously processed
		if($isValid && strtoupper(AkeebasubsHelperCparams::getParam('currency','GBP')) == strtoupper($data['currency'])) {
			$isValid = false;
			$data['akeebasubs_failure_reason'] = 'Wrong currency';
		}

		// Check that amount_gross is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['amount']);
			$gross = $subscription->gross_amount;
			if($mc_gross > 0) {
				// A positive value means 'payment'. The prices MUST match!
				// Important: NEVER, EVER compare two floating point values for equality.
				$isValid = ($gross - $mc_gross) < 0.01;
			} else {
				$isPartialRefund = false;
				$temp_mc_gross = -1 * $mc_gross;
				$isPartialRefund = ($gross - $temp_mc_gross) > 0.01;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}

		// Log the IPN data
		$this->logIPN($data, $isValid);

		// Fraud attempt? Do nothing more!
		if(!$isValid) {
			@ob_end_clean();
			echo $this->getHTMLResponse($this->getURLProtocol(), $this->getURLBase(), $slug, 'cancel', $subscription->akeebasubs_subscription_id);
			JFactory::getApplication()->close();
			return false;
		}

		// Check the payment_status
		if($data['valid'] == 'true' && $data['code'] == 'A') {
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> md5($data['trans_id'] . $data['ip'] . time()),
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

		// Redirect to thank you or cancel page
		if($newStatus == 'C') {
			$layout = 'order';
		} else {
			$layout = 'cancel';
		}
		@ob_end_clean();
		echo $this->getHTMLResponse($this->getURLProtocol(), $this->getURLBase(), $slug, $layout, $subscription->akeebasubs_subscription_id);
		$app->close();
		return true;
	}
    
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data, $subscription)
	{
		$key = trim($this->params->get('digest_key',''));
		if(! empty($key)) {
			$transId = $subscription->akeebasubs_subscription_id;
			$amount = sprintf('%.2f',$subscription->gross_amount);
			$callback = $this->getCallbackURL();
			$calculatedCode = md5('trans_id=' . $transId . '&amount=' . $amount . '&callback=' . $callback . '&' . $key);
			return strtolower($calculatedCode) == strtolower($data['hash']);
		}
		return true;
	}
	
	private function getTestStatus()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'true';
		}
		return 'live';
	}
	
	private function getCallbackURL()
	{
		return JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=paypoint';
	}
	
	private function getURLProtocol()
	{
		$parts = explode('://', JURI::base());
		return $parts[0];
	}
	
	private function getURLBase()
	{
		$parts = explode('://', JURI::base());
		return $parts[1];
	}
	
	private function getHTMLResponse($protocol, $base, $slug, $layout, $sid)
	{
		$base = rtrim($base, '/');
return '<html>
<body>
<script language="javascript">
  protocol = "' . $protocol . '";
  base = "' . $base . '";
  slug = "' . $slug . '";
  layout = "' . $layout . '";
  sid = "' . $sid . '";
  window.location = protocol + "://" + base + "/index.php?option=com_akeebasubs&view=message&slug=" + slug + "&layout=" + layout + "&subid=" + sid;
</script>
</body>
</html>';
	}
}