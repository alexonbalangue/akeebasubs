<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentSagepay extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'sagepay',
			'ppKey'			=> 'PLG_AKPAYMENT_SAGEPAY_TITLE',
			'ppImage'		=> JURI::root().'plugins/akpayment/sagepay/sagepay/logo.png'
		));

		parent::__construct($subject, $config);

		// No cURL? Well, that's no point on continuing...
		if(!function_exists('curl_init'))
		{
			if(version_compare(JVERISON, '3.0', 'ge'))
			{
				throw new Exception('Sagepay payment plugin needs cURL extension in order to work', 500);
			}
			else
			{
				JError::raiseError(500, 'Sagepay payment plugin needs cURL extension in order to work');
			}
		}
	}

	/**
	 *
	 * @param string $paymentmethod
	 * @param JUser $user
	 * @param AkeebasubsTableLevel $level
	 * @param AkeebasubsTableSubscription $subscription
	 *
	 * @return string
	 */
	public function onAKPaymentNew($paymentmethod, $user, $level, $subscription)
	{
		if($paymentmethod != $this->ppName) return false;

		// SagePay handles the transaction in a different way: we don't send the customer to its site and then
		// get the result; we ask the customer for more details, then POST SagePay. If everything is ok, we
		// authorize the subscription
		$callbackUrl = JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=sagepay&sid='.$subscription->akeebasubs_subscription_id;
		$data = (object)array(
			'url'			=> $callbackUrl,
			'amount'		=> (int)($subscription->gross_amount * 100),
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'description'	=> $level->title . ' #' . $subscription->akeebasubs_subscription_id,
			'cardholder'	=> $user->name
		);

		@ob_start();
		include dirname(__FILE__).'/sagepay/form.php';
		$html = @ob_get_clean();

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');

		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;

		$sagePostUrl = $this->buildSageUrl($data, $data['sid']);

		$this->validateSubscription($sagePostUrl);

		return true;
	}

	public function selectMonth()
	{
		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 1; $i <= 12; $i++) {
			$m = sprintf('%02u', $i);
			$options[] = JHTML::_('select.option',$m,$m);
		}

		return JHTML::_('select.genericlist', $options, 'card-expiry-month', 'class="input-small"', 'value', 'text', '', 'card-expiry-month');
	}

	public function selectYear()
	{
		$year = gmdate('Y');
		$shortYear = gmdate('y');

		$options = array();
		$options[] = JHTML::_('select.option',0,'--');
		for($i = 0; $i <= 10; $i++)
		{
			$y = sprintf('%04u', $i+$year);
			$value = sprintf('%d', $i+$shortYear);
			$options[] = JHTML::_('select.option',$value,$y);
		}

		return JHTML::_('select.genericlist', $options, 'card-expiry-year', 'class="input-small"', 'value', 'text', '', 'card-expiry-year');
	}

	public function selectCardType()
	{
		$options[] = JHTML::_('select.option', '', '--');
		$options[] = JHTML::_('select.option', 'AMEX', 'American Express');
		$options[] = JHTML::_('select.option', 'DELTA', 'Delta');
		$options[] = JHTML::_('select.option', 'DC', 'Diners');
		$options[] = JHTML::_('select.option', 'JCB', 'JCB');
		$options[] = JHTML::_('select.option', 'LASER', 'Laser');
		$options[] = JHTML::_('select.option', 'MAESTRO', 'Maestro');
		$options[] = JHTML::_('select.option', 'MC', 'Mastercard');
		$options[] = JHTML::_('select.option', 'MCDEBIT', 'Mastercard Debit card');
		$options[] = JHTML::_('select.option', 'VISA', 'Visa');
		$options[] = JHTML::_('select.option', 'UKE', 'Visa Electron');

		return JHTML::_('select.genericlist', $options, 'card-type', 'class="input-medium"', 'value', 'text', '', 'card-type');
	}

	private function getPaymentURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://test.sagepay.com/gateway/service/vspdirect-register.vsp';
		} else {
			return 'https://live.sagepay.com/gateway/service/vspdirect-register.vsp';
		}
	}

	private function buildSageUrl($data, $subsid)
	{
		$subscription = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
							->getItem($subsid);

		$user  = JFactory::getUser($subscription->user_id);
		$level = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
					->getItem($subscription->akeebasubs_level_id);

		$kuser = FOFModel::getTmpInstance('Users', 'AkeebasubsModel')
					->user_id($subscription->user_id)
					->getFirstItem();

		$nameParts = explode(' ', $user->name, 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}

		$string  = 'VPSProtocol=3.00';
		$string .= '&TxType=PAYMENT';
		$string .= '&Vendor='.$this->params->get('vendor');
		$string .= '&VendorTxCode='.md5(time());

		// Subscription info
		$string .= '&Amount='.number_format($subscription->gross_amount, 2);
		$string .= '&Currency='.urlencode(strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')));
		$string .= '&Description='.urlencode($level->title . ' - [ ' . $user->username . ' ]');

		// Credit Card info
		$string .= '&CardHolder='.urlencode($data['card-holder']);
		$string .= '&CardNumber='.urlencode($data['card-number']);
		$string .= '&ExpiryDate='.$data['card-expiry-month'].$data['card-expiry-year'];
		$string .= '&CV2='.$data['card-cvc'];
		$string .= '&CardType='.$data['card-type'];

		// Billing info
		$string .= '&BillingFirstnames='.urlencode($firstName);
		$string .= '&BillingSurname='.urlencode($lastName);
		$string .= '&BillingAddress1='.urlencode($kuser->address1);

		if($kuser->address2)
		{
			$string .= '&strBillingAddress2='.urlencode($kuser->address2);
		}

		$string .= '&BillingCity='.urlencode($kuser->city);
		$string .= '&BillingPostCode='.urlencode($kuser->zip);
		$string .= '&BillingCountry='.urlencode($kuser->country);

		// Other
		$string .= '&CustomerEMail='.urlencode($user->email);
		$string .= '&Apply3DSecure=0';
		$string .= '&AccountType=E';

		return $string;
	}

	private function validateSubscription($url)
	{
		$this->debug($url);

		$curlSession = curl_init();

		// Set the URL
		curl_setopt ($curlSession, CURLOPT_URL, $this->getPaymentURL());
		curl_setopt ($curlSession, CURLOPT_HEADER, 0);
		curl_setopt ($curlSession, CURLOPT_POST, 1);
		curl_setopt ($curlSession, CURLOPT_POSTFIELDS, $url);
		curl_setopt ($curlSession, CURLOPT_RETURNTRANSFER,1);
		curl_setopt ($curlSession, CURLOPT_TIMEOUT, 30);
		//The next two lines must be present for the kit to work with newer version of cURL
		//You should remove them if you have any problems in earlier versions of cURL
		curl_setopt ($curlSession, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt ($curlSession, CURLOPT_SSL_VERIFYHOST, 1);

		$rawresponse = curl_exec($curlSession);

		//Split response into name=value pairs
		$response = explode('=', $rawresponse);
		// Check that a connection was made
		if (curl_error($curlSession))
		{
			// If it wasn't...
			$output['Status'] = "FAIL";
			$output['StatusDetail'] = curl_error($curlSession);
		}

		curl_close ($curlSession);

		// Tokenise the response
		for ($i=0; $i<count($response); $i++)
		{
			// Find position of first "=" character
			$splitAt = strpos($response[$i], "=");
			// Create an associative (hash) array with key/value pairs ('trim' strips excess whitespace)
			$output[trim(substr($response[$i], 0, $splitAt))] = trim(substr($response[$i], ($splitAt+1)));
		}

		$this->debug(print_r($output, true));
	}

	private function debug($string)
	{
		$handle = fopen(JPATH_ROOT.'/log.txt', 'a+');
		fwrite($handle, date('Y-m-d H:i:s').' --- '.$string.PHP_EOL);
		fclose($handle);
	}
}