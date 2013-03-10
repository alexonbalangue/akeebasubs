<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentPayu extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'payu',
			'ppKey'			=> 'PLG_AKPAYMENT_PAYU_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/ccavenue_lowres.gif'
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
		
		$nameParts = explode(' ', $user->name, 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
                //Begin of addition by IML to capture phone number field added to the signup form as it is a mandatory parameter for Payu
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();
                
        $params = json_decode($kuser->params);
        $phone = $params->phonenumber;
                //End of addition by IML
		$merchant = $this->params->get('merchant','');
		$WorkingKey = $this->params->get('workingkey','');
		$redirectURL = $rootURL.str_replace('&amp;','&',JRoute::_('/joomla/index.php?option=com_akeebasubs&view=callback&paymentmethod=payu'));
		$surl	= $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
		
		$pos = array(
			'key'			=> $merchant,
			'txnid'			=> $subscription->akeebasubs_subscription_id,
			'amount'        => $subscription->gross_amount,
			'productinfo'	=> $level->title,
			'firstname'	    => $firstName,
			'email'			=> $user->email,
			'hash'          => ''
		);
		$hash = '';

// Begin of addition by IML - Hash Sequence as per Payu (Should be handled in getchecksum function ideally)
$hashSequence = "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10";
if(empty($pos['hash']) && sizeof($pos) > 0) {
  if(
          empty($merchant)
          || empty($subscription->akeebasubs_subscription_id)
          || empty($subscription->gross_amount)
          || empty($firstName)
          || empty($user->email)
          || empty($phone)
          || empty($level->title)
          || empty($surl)
          || empty($surl)
  ) {
    $formError = 1;
  } else {
    $hashVarsSeq = explode('|', $hashSequence);
    $hash_string = '';
    foreach($hashVarsSeq as $hash_var) {
      $hash_string .= isset($pos[$hash_var]) ? $pos[$hash_var] : '';
      $hash_string .= '|';
    }
    $hash_string .= $WorkingKey;
    $hash = strtolower(hash('sha512', $hash_string));
    $action = $PAYU_BASE_URL . '/_payment';
  }
} elseif(!empty($posted['hash'])) {
  $hash = $pos['hash'];
  $action = $PAYU_BASE_URL . '/_payment';
}
		$checksum = $hash;
		// End of addition by IML
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		$data = (object)array(
			'url'			=> 'https://secure.payu.in/_payment',
			'merchant'		=> $merchant,
			'postback'		=> $redirectURL,
			'currency'		=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'firstname'		=> $firstName,
			'lastname'		=> $lastName,
			'phno'		    => $phone,
			'email'			=> $user->email,
			'checksum'		=> $checksum
		);
		
		$kuser = FOFModel::getTmpInstance('Users','AkeebasubsModel')
			->user_id($user->id)
			->getFirstItem();

		@ob_start();
		include dirname(__FILE__).'/payu/form.php';
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
		$hackAttempt = false;
		if(!$isValid) {
			$data['akeebasubs_failure_reason'] = 'Invalid checksum; the request has been tampered';
		}
		
		// Load the relevant subscription row
		if($isValid) {
			$id = array_key_exists('txnid', $data) ? (int)$data['txnid'] : -1;
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referenced subscription ID ("Order_Id" field) is invalid';
		}
		
		// Check that the merchant ID is what the site owner has configured
		if($isValid) {
			$merchant_id = $data['key'];
			$valid_id = $this->params->get('merchant','');
			$isValid = ($merchant_id == $valid_id);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Merchant ID does not match';
		}
			
		// Check that the amount is correct
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['amount']);
			$gross = $subscription->gross_amount;
			// Important: NEVER, EVER compare two floating point values for equality.
			$isValid = ($gross - $mc_gross) < 0.01;
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Paid amount does not match the subscription amount';
		}
		
		// Log the IPN data
		$this->logIPN($data, $isValid);
		
		// Fraud attempt? Do nothing more!
		if(!$isValid) die('Payment processing refused, please try again or contact us at sales@imustlearn.co.in');
		
		// Load the subscription level and get its slug
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;

		// Check the payment_status
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		switch($data['status'])
		{
			case 'success':
				$newStatus = 'C';
				$returnURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
				break;
			
			case 'pending':
				$newStatus = 'P';
				$returnURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id));
				break;
			
			case 'failure':
			default:
				$newStatus = 'X';
				$returnURL = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id));
				break;
		}

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
			'akeebasubs_subscription_id'				=> $id,
			'processor_key'		=> $data['mihpayid'],
			'state'				=> $newStatus,
			'enabled'			=> 0
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
		
		$app = JFactory::getApplication();
		$app->redirect($returnURL);
		
		return true;
	}

	/**
	 * Validates the incoming data against PayPal's IPN to make sure this is not a
	 * fraudelent request.
	 */
	private function isValidIPN($data)
	{
		$WorkingKey		= $this->params->get('workingkey','');
		
		$Merchant_Id	= $data['Merchant_Id'];
		$Amount			= $data['Amount'];
		$Order_Id		= $data['Order_Id'];
		$Checksum		= $data['hash'];
		$AuthDesc		= $data['AuthDesc'];
		
		$merc_hash_vars_seq = explode('|', "key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5|udf6|udf7|udf8|udf9|udf10");
      //generation of hash after transaction is = salt + status + reverse order of variables
      $merc_hash_vars_seq = array_reverse($merc_hash_vars_seq);
      
      $merc_hash_string = $WorkingKey . '|' . $data['status'];
	
      foreach ($merc_hash_vars_seq as $merc_hash_var) {
        $merc_hash_string .= '|';
        $merc_hash_string .= isset($data[$merc_hash_var]) ? $data[$merc_hash_var] : '';
        }
     
      $merc_hash =strtolower(hash('sha512', $merc_hash_string));
        if($merc_hash == $Checksum)
			return true ;
		else 
			return false ;
		
	}
	
	/**
	 * Get the checksum for an outbound paymenet request (form POST)
	 */
	private function getchecksum($MerchantId,$Amount,$OrderId ,$URL,$WorkingKey)
	{
		$str ="$MerchantId|$OrderId|$Amount|$URL|$WorkingKey";
		$adler = 1;
		$adler = $this->adler32($adler,$str);
		return $adler;
	}
	
	/**
	 * Verify the checksum for an inbound payment notification
	 */
	private function verifychecksum($MerchantId,$OrderId,$Amount,$AuthDesc,$CheckSum,$WorkingKey)
	{
		$str = "$MerchantId|$OrderId|$Amount|$AuthDesc|$WorkingKey";
		$adler = 1;
		$adler = $this->adler32($adler,$str);
		
		if($adler == $CheckSum)
			return true ;
		else 
			return false ;
	}
	
	private function adler32($adler , $str)
	{
		$BASE =  65521 ;
	
		$s1 = $adler & 0xffff ;
		$s2 = ($adler >> 16) & 0xffff;
		for($i = 0 ; $i < strlen($str) ; $i++)
		{
			$s1 = ($s1 + Ord($str[$i])) % $BASE ;
			$s2 = ($s2 + $s1) % $BASE ;
		}
		return $this->leftshift($s2 , 16) + $s1;
	}
	
	private function leftshift($str , $num)
	{
	
		$str = DecBin($str);
	
		for( $i = 0 ; $i < (64 - strlen($str)) ; $i++)
			$str = "0".$str ;
	
		for($i = 0 ; $i < $num ; $i++) 
		{
			$str = $str."0";
			$str = substr($str , 1 ) ;
		}
		return $this->cdec($str) ;
	}
	
	private function cdec($num)
	{
	
		for ($n = 0 ; $n < strlen($num) ; $n++)
		{
		   $temp = $num[$n] ;
		   $dec =  $dec + $temp*pow(2 , strlen($num) - $n - 1);
		}
	
		return $dec;
	}
}