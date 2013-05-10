<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentOffline extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'offline',
			'ppKey'			=> 'PLG_AKPAYMENT_OFFLINE_TITLE',
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

		// Set the payment status to Pending
		$oSub = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->setId($subscription->akeebasubs_subscription_id)
			->getItem();
		$updates = array(
			'state'				=> 'P',
			'enabled'			=> 0,
			'processor_key'		=> md5(time()),
		);
		$oSub->save($updates);

		// Activate the user account, if the option is selected
		$activate = $this->params->get('activate', 0);
		if($activate && $user->block) {
			$updates = array(
				'block'			=> 0,
				'activation'	=> ''
			);
			$user->bind($updates);
			$user->save($updates);
		}

		// Render the HTML form
		$nameParts = explode(' ', $user->name, 2);
		$firstName = $nameParts[0];
		if(count($nameParts) > 1) {
			$lastName = $nameParts[1];
		} else {
			$lastName = '';
		}

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
		$html = str_replace('{FIRSTNAME}', $firstName, $html);
		$html = str_replace('{LASTNAME}', $lastName, $html);
		$html = str_replace('{LEVEL}', $level->title, $html);

		@include_once JPATH_SITE.'/components/com_akeebasubs/helpers/message.php';
		if(class_exists('AkeebasubsHelperMessage')) {
			$html = AkeebasubsHelperMessage::processLanguage($html);
		}

		$html = '<div>'.$html.'</div>';

		return $html;
	}

	public function onAKPaymentCallback($paymentmethod, $data)
	{
		return false;
	}
}