<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');
jimport('joomla.html.parameter');

class plgAkpaymentIFthen extends JPlugin
{
	private $ppName = 'ifthen';
	private $ppKey = 'PLG_AKPAYMENT_IFTHEN_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!is_object($config['params'])) {
			jimport('joomla.registry.registry');
			$config['params'] = new JRegistry($config['params']);
		}
			
		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		require_once dirname(__FILE__).'/ifthen/library/mb.php';
		
		// Load the language files
		$jlang = JFactory::getLanguage();
		$jlang->load('plg_akpayment_ifthen', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_ifthen', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_ifthen', JPATH_ADMINISTRATOR, null, true);
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
			$ret['image'] = rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/multibanco.jpg';
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
		
		$data = (object)array(
			'url'				=> $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=callback&paymentmethod=ifthen')),
			'entidade'			=> trim($this->params->get('entidade','')),
			'subentidade'		=> trim($this->params->get('subentidade','')),
			'valor'				=> sprintf('%.2f',$subscription->gross_amount),
			'currency'			=> strtoupper(AkeebasubsHelperCparams::getParam('currency','EUR')),
			'subscription_id'	=> $subscription->akeebasubs_subscription_id
		);
		
		$data->referencia = MBLibrary::GenerateMbRef(
				$data->entidade,
				$data->subentidade,
				$data->subscription_id,
				$data->valor);
		
		$subscription->save(array(
			'processor_key'		=> $data->referencia
		));
		
		// Add the style for the payment form
		$document = JFactory::getDocument();
		$document->addStyleDeclaration(
'  table#ifthen-form {
	   width: 250px;
  }
  table#ifthen-form tr, table#ifthen-form thead, table#ifthen-form tfoot, table#ifthen-form td {
	   border: none;
  }
  table#ifthen-form > thead td {
	   padding-bottom: 5px;
  }
  table#ifthen-form > tfoot td {
	   padding-top: 6px;
  }
  table#ifthen-form table tr > td {
	   padding-right: 27px;
  }');
		
		@ob_start();
		include dirname(__FILE__).'/ifthen/form.php';
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
			$reference = $data['referencia'];
			$subscription = null;
			if(! empty($reference)) {
				$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->processor('ifthen')
					->paystate('N')
					->paykey($reference)
					->getFirstItem(true);
				$id = (int)$subscription->akeebasubs_subscription_id;
				if( ($subscription->akeebasubs_subscription_id <= 0) || ($subscription->processor_key != $reference) ) {
					$subscription = null;
					$isValid = false;
				}
			} else {
				$isValid = false;
			}
			if(!$isValid) $data['akeebasubs_failure_reason'] = 'The referencia is invalid';
		}
        
		// Check that entidade is correct
		if($isValid && !is_null($subscription)) {
			if(trim($this->params->get('entidade','')) != $data['entidade']) {
				$isValid = false;
				$data['akeebasubs_failure_reason'] = "The received entidade does not match the one that was sent.";
			}
		}
		
		// Check that amount_gross is correct
		$isPartialRefund = false;
		if($isValid && !is_null($subscription)) {
			$mc_gross = floatval($data['valor']);
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
		if(!$isValid) return false;
		
		// Payment status always COMPLETED if this point is reached
		$newStatus = 'C';

		// Update subscription status (this also automatically calls the plugins)
		$updates = array(
				'akeebasubs_subscription_id'	=> $id,
				'processor_key'					=> $reference,
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
	 * Validates the incoming data.
	 */
	private function isValidIPN($data)
	{
		$chave = trim($this->params->get('chave',''));
		if(! empty($chave)) {
			return trim($data['chave']) == trim($this->params->get('chave',''));
		}
		return false;
	}
	
	
	private function logIPN($data, $isValid)
	{
		$config = JFactory::getConfig();
		if(version_compare(JVERSION, '3.0', 'ge')) {
			$logpath = $config->get('log_path');
		} else {
			$logpath = $config->getValue('log_path');
		}
		$logFile = $logpath.'/akpayment_ifthen_ipn.php';
		jimport('joomla.filesystem.file');
		if(!JFile::exists($logFile)) {
			$dummy = "<?php die(); ?>\n";
			JFile::write($logFile, $dummy);
		} else {
			if(@filesize($logFile) > 1048756) {
				$altLog = $logpath.'/akpayment_ifthen_ipn-1.php';
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
		$logData .= $isValid ? 'VALID IFTHEN IPN' : 'INVALID IFTHEN IPN *** FRAUD ATTEMPT OR INVALID NOTIFICATION ***';
		$logData .= "\nDate/time : ".gmdate('Y-m-d H:i:s')." GMT\n\n";
		foreach($data as $key => $value) {
			$logData .= '  ' . str_pad($key, 30, ' ') . $value . "\n";
		}
		$logData .= "\n";
		JFile::write($logFile, $logData);
	}
}