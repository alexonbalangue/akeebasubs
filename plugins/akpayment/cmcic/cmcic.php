<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentCmcic extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'cmcic',
			'ppKey'			=> 'PLG_AKPAYMENT_CMCIC_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/cic-paiement-horizon-grd.jpg',
		));
		
		parent::__construct($subject, $config);
		
		require_once dirname(__FILE__).'/cmcic/library/CMCIC_Tpe.inc.php';
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
		
		$cicTpe = $this->createCMCICTpe($subscription);
		
		$data = (object)array(
			'url'				=> $cicTpe->sUrlPaiement,
			'version'			=> $cicTpe->sVersion,
			'TPE'				=> $cicTpe->sNumero,
			'date'				=> date("d/m/Y:H:i:s"),
			'montant'			=> sprintf('%.2f',$subscription->gross_amount)
										. strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'reference'			=> $subscription->akeebasubs_subscription_id,
			'lgue'				=> $cicTpe->sLangue,
			'societe'			=> $cicTpe->sCodeSociete,
			'url_retour'		=> $cicTpe->sUrlKO,
			'url_retour_ok'		=> $cicTpe->sUrlOK,
			'url_retour_err'	=> $cicTpe->sUrlKO,
			'mail'				=> trim($user->email)
		);
		
		$cicMac = new CMCIC_Hmac($cicTpe);
		$data->MAC = $cicMac->computeHmac(
				$data->TPE
					. '*' . $data->date
					. '*' . $data->montant
					. '*' . $data->reference
					. '**' . $data->version
					. '*' . $data->lgue
					. '*' . $data->societe
					. '*' . $data->mail
					. '**********');

		@ob_start();
		include dirname(__FILE__).'/cmcic/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		JLoader::import('joomla.utilities.date');
		header("Pragma: no-cache");
		header("Content-type: text/plain");
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		$isValid = true;
		
		// Clean up second question mark in response
		preg_match('/000\?TPE=(.+)/', $data['marker'], $matches);
		$data['TPE'] = $matches[1];

		// Load the relevant subscription row
		if($isValid) {
			$id = $data['reference'];
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
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The reference is invalid';
		}
		
		if($isValid) {
			$cicTpe = $this->createCMCICTpe($subscription);
			$cicMac = new CMCIC_Hmac($cicTpe);
			$responseString = $cicTpe->sNumero
				. '*' . $data['date']
				. '*' . $data['montant']
				. '*' . $data['reference']
				. '*' . $data['texte-libre']
				. '*' . $cicTpe->sVersion
				. '*' . $data['code-retour']
				. '*' . $data['cvx']
				. '*' . $data['vld']
				. '*' . $data['brand']
				. '*' . $data['status3ds']
				. '*' . $data['numauto']
				. '*' . $data['motifrefus']
				. '*' . $data['originecb']
				. '*' . $data['bincb']
				. '*' . $data['hpancb']
				. '*' . $data['ipclient']
				. '*' . $data['originetr']
				. '*' . $data['veres']
				. '*' . $data['pares'] . '*';
			// Check IPN data for validity (i.e. protect against fraud attempt)
			$isValid = $this->isValidIPN($data, $cicMac, $responseString);
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';
		}
        
		// Check that bank_id has not been previously processed
		if($isValid) {
			if($data['MAC'] == $subscription->processor_key && $subscription->state == 'C') {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "I will not processed this payment twice";
			}
		}
		
		$currency = substr($data['montant'], -3);
		$amount = substr($data['montant'], 0, -3);
		
		// Check currency
		if($isValid) {
			if(strtoupper($currency) != strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR'))) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "Currency doesn't match.";
			}
		}

		// Check that amount is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($amount);
			$gross = $subscription->gross_amount;
			if($mc_gross > 0) {
				// A positive value means "payment". The prices MUST match!
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
			echo sprintf(CMCIC_CGI2_RECEIPT, (CMCIC_CGI2_MACNOTOK . $responseString));
			@$app->close();
			return false;
		}
		
		// Payment status
		$sandbox = $this->params->get('sandbox',0);
		if(($sandbox && $data['code-retour'] == 'payetest')
			|| (!$sandbox && $data['code-retour'] == 'paiement')){
			$newStatus = 'C';
		} else {
			$newStatus = 'X';
		}

		// Update subscription status (this+ also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $data['MAC'],
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
		
		@ob_end_clean();
		echo sprintf(CMCIC_CGI2_RECEIPT, CMCIC_CGI2_MACOK);
		@$app->close();
		
		return true;
	}
	
	
	/**
	 * Gets the form action URL
	 */
	private function getURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://ssl.paiement.cic-banques.fr/test/';
		} else {
			return 'https://ssl.paiement.cic-banques.fr/';
		}
	}
	
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data, $cicMac, $responseString)
	{
		$macCode = $cicMac->computeHmac($responseString);
		return $macCode == strtolower($data['MAC']);
	}
	
	private function createCMCICTpe($subscription)
	{
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		// Language settings (FR, EN, DE, IT, ES or NL)
		$lang = strtoupper(substr(JFactory::getLanguage()->getTag(), 0, 2));
		if($lang != 'FR' && $lang != 'EN' && $lang != 'DE' && $lang != 'IT' && $lang != 'ES' && $lang != 'NL') {
			$lang = 'FR';
		}
		
		define("CMCIC_CLE", trim($this->params->get('key','')));
		define("CMCIC_TPE", trim($this->params->get('tpe','')));
		define("CMCIC_VERSION", '3.0');
		define("CMCIC_SERVEUR", $this->getURL());
		define("CMCIC_CODESOCIETE", trim($this->params->get('societe','')));
		define("CMCIC_URLOK", $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)));
		define("CMCIC_URLKO", $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)));
		
		return new CMCIC_Tpe($lang);
	}
}