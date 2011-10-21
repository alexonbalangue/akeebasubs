<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentOffline extends JPlugin
{
	private $ppName = 'offline';
	private $ppKey = 'PLG_AKPAYMENT_OFFLINE_TITLE';

	public function __construct(&$subject, $config = array())
	{
		if(!version_compare(JVERSION, '1.6.0', 'ge')) {
			if(!is_object($config['params'])) {
				$config['params'] = new JParameter($config['params']);
			}
		}
		parent::__construct($subject, $config);
		
		require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
		
		// Load the language files
		$jlang =& JFactory::getLanguage();
		$jlang->load('plg_akpayment_offline', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_offline', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_offline', JPATH_ADMINISTRATOR, null, true);
	}

	public function onAKPaymentGetIdentity()
	{
		$title = $this->params->get('title','');
		if(empty($title)) $title = JText::_($this->ppKey);
		$ret = array(
			'name'		=> $this->ppName,
			'title'		=> $title
		);
		return (object)$ret;
	}
	
	/**
	 * Returns the payment form to be submitted by the user's browser. The form must have an ID of
	 * "paymentForm" and a visible submit button.
	 * 
	 * @param string $paymentmethod
	 * @param JUser $user
	 * @param KDatabaseRow $level
	 * @param KDatabaseRow $subscription
	 * @return string
	 */
	public function onAKPaymentNew($paymentmethod, $user, $level, $subscription)
	{
		if($paymentmethod != $this->ppName) return false;
		
		$html = $this->params->get('instructions','');
		if(empty($html)) {
			$html = <<<ENDTEMPLATE
<p>Dear Sir/Madam,<br/>
In order to complete your payment, please deposit {AMOUNT}€ to our bank account:</p>
<p>
<b>IBAN</b>: XX00.000000.00000000.00000000<br/>
<b>BIC</b>: XXXXXXXX
</p>
<p>Please reference subscription code {SUBSCRIPTION} in your payment. Make sure that any bank charges are paid by you in full and not deducted from the transferred amount. If you're using e-Banking to transfer the funds, please select the "OUR" bank expenses option.</p>
<p>Thank you in advance,<br/>
The management</p>
ENDTEMPLATE;
		}
		$html = str_replace('{AMOUNT}', sprintf('%01.02f', $subscription->gross_amount), $html);
		$html = str_replace('{SUBSCRIPTION}', sprintf('%06u', $subscription->akeebasubs_subscription_id), $html);
		$html = '<div>'.$html.'</div>';

		return $html;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		return false;
	}
}