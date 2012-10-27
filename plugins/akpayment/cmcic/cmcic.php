<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');
jimport('joomla.html.parameter');

class plgAkpaymentCmcic extends JPlugin
{
	private $ppName = 'cmcic';
	private $ppKey = 'PLG_AKPAYMENT_CMCIC_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}
			
		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		require_once dirname(__FILE__).'/cmcic/library/Utils.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_cmcic', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_cmcic', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_cmcic', JPATH_ADMINISTRATOR, null, true);
	}

	public function onAKPaymentGetIdentity()
	{
		$title = $this->params->get('title','');
		if(empty($title)) $title = JText::_($this->ppKey);
		$ret = array(
			'name'		=> $this->ppName,
			'title'		=> $title
		);
		$ret['image'] = trim($this->params->get('ppimage',''));
		if(empty($ret['image'])) {
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/cic-paiement-horizon-grd.jpg';
		}
		return (object)$ret;
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
		
		$data = (object)array(
			'url'					=> $this->getURL(),
			'version'				=> '1.2open',
			'TPE'					=> trim($this->params->get('tpe','')),
			'date'					=> Utils::HtmlEncode(date("d/m/Y:H:i:s")),
			'montant'				=> sprintf('%.2f',$subscription->gross_amount)
											. strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'reference'				=> Utils::HtmlEncode($subscription->akeebasubs_subscription_id),
			'lgue'					=> $lang,
			'societe'				=> Utils::HtmlEncode(trim($this->params->get('societe',''))),
			'url_retour'			=> Utils::HtmlEncode(JURI::base().'index.php?option=com_akeebasubs&view=callback&paymentmethod=cmcic'),
			'url_retour_ok'			=> Utils::HtmlEncode($rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id))),
			'url_retour_err'		=> Utils::HtmlEncode($rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=message&slug='.$slug.'&layout=cancel&subid='.$subscription->akeebasubs_subscription_id)))
		);
		
		$data->MAC = Utils::hmac_sha1(
				trim($this->params->get('key','')),
				$data->TPE
				. '*' . $data->date
				. '*' . $data->montant
				. '*' . $data->reference
				. '**' . $data->version
				. '*' . $data->lgue
				. '*' . $data->societe);

		@ob_start();
		include dirname(__FILE__).'/cmcic/form.php';
		$html = @ob_get_clean();
		
		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		jimport('joomla.utilities.date');
		
		// Check if we're supposed to handle this
		if($paymentmethod != $this->ppName) return false;
		
		// Check IPN data for validity (i.e. protect against fraud attempt)
		$isValid = $this->isValidIPN($data);
		if(!$isValid) $data['akeebasubs_failure_reason'] = 'Invalid response received.';

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
        
		// Check that bank_id has not been previously processed
		if($isValid && !is_null($subscription)) {
			if($subscription->state != 'N') {
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
				'state'							=> $newStatus,
				'enabled'						=> 0
		);
		jimport('joomla.utilities.date');
		if($newStatus == 'C') {
			// Fix the starting date if the payment was accepted after the subscription's start date. This
			// works around the case where someone pays by e-Check on January 1st and the check is cleared
			// on January 5th. He'd lose those 4 days without this trick. Or, worse, if it was a one-day pass
			// the user would have paid us and we'd never given him a subscription!
			$jNow = new JDate();
			$jStart = new JDate($subscription->publish_up);
			$jEnd = new JDate($subscription->publish_down);
			$now = $jNow->toUnix();
			$start = $jStart->toUnix();
			$end = $jEnd->toUnix();
			
			if($start < $now) {
				$duration = $end - $start;
				$start = $now;
				$end = $start + $duration;
				$jStart = new JDate($start);
				$jEnd = new JDate($end);
			}

			$updates['publish_up'] = $jStart->toSql();
			$updates['publish_down'] = $jEnd->toSql();
			$updates['enabled'] = 1;

		}
		$subscription->save($updates);

		// Run the onAKAfterPaymentCallback events
		jimport('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array(
			$subscription
		));
        
		return true;
	}
	
	
	/**
	 * Gets the form action URL
	 */
	private function getURL()
	{
		$sandbox = $this->params->get('sandbox',0);
		if($sandbox) {
			return 'https://ssl.paiement.cic-banques.fr/test/paiement.cgi';
		} else {
			return 'https://ssl.paiement.cic-banques.fr/paiement.cgi';
		}
	}
	
	/**
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$hashCode = Utils::hmac_sha1(
				trim($this->params->get('key','')),
				$data['retourPLUS']
				. trim($this->params->get('tpe',''))
				. '+' . $data['date']
				. '+' . $data['montant']
				. '+' . $data['reference']
				. '+' . $data['texte-libre']
				. '+1.2open'
				. '+' . $data['code-retour'] . '+');
		return $hashCode == $data['MAC'];
	}
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}
		$logFile = $logpath.'/akpayment_cmcic_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_cmcic_ipn-1.php';
				if(JFile::exists($altLog)) {
					JFile::delete($altLog);
				}
				JFile::copy($logFile, $altLog);
				JFile::delete($logFile);
				$dummy = "<?php die(); ?>\n";
				JFile::write($logFile, $dummy);
			}
		}
		$logData = JFile::read($logFile);
		if($logData === false) $logData = '';
		$logData .= "\n" . str_repeat('-', 80);
		$logData .= $isValid ? 'VALID CMCIC IPN' : 'INVALID CMCIC IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}