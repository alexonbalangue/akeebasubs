<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentIFthen extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'ifthen',
			'ppKey'			=> 'PLG_AKPAYMENT_IFTHEN_TITLE',
			'ppImage'		=> rtrim(JURI::base(),'/').'/media/com_akeebasubs/images/frontend/multibanco.jpg'
		));
		
		parent::__construct($subject, $config);
		
		require_once dirname(__FILE__).'/ifthen/library/mb.php';
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
			'processor_key'		=> str_replace(' ', '', $data->referencia)
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
		JLoader::import('joomla.utilities.date');
		
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
}