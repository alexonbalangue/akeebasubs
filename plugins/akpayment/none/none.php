<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2013 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

$akpaymentinclude = include_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/assets/akpayment.php';
if(!$akpaymentinclude) { unset($akpaymentinclude); return; } else { unset($akpaymentinclude); }

class plgAkpaymentNone extends plgAkpaymentAbstract
{
	public function __construct(&$subject, $config = array())
	{
		$config = array_merge($config, array(
			'ppName'		=> 'none',
			'ppKey'			=> 'PLG_AKPAYMENT_NONE_TITLE',
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

		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		
		$uri = $rootURL.str_replace('&amp;','&',JRoute::_('index.php?option=com_akeebasubs&view=callback&paymentmethod=none'));
		
		$t1 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_HEADER');
		$t2 = JText::_('COM_AKEEBASUBS_LEVEL_REDIRECTING_BODY');
		
		$form = <<<ENDFORM
<h3>$t1</h3>
<p>$t2</p>
<form action="$uri" method="POST" id="paymentForm">
	<input type="hidden" name="subscription" value="{$subscription->akeebasubs_subscription_id}" />
	<input type="submit" class="btn" value="Complete subscription" />
</form>
ENDFORM;
		
		// This is a demo script; just add some GET parameters to the URI
		return $form;
	}
	
	public function onAKPaymentCallback($paymentmethod, $data)
	{
		if($paymentmethod != $this->ppName) return false;
		
		// Enable the subscription
		$id = (int)$data['subscription'];
		$subscription = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
			->setId($id)
			->getItem();
		
		if(empty($subscription->akeebasubs_subscription_id)) return false;
		
		if($subscription->akeebasubs_subscription_id != $id) return false;
		
		$id = (int)$data['subscription'];
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'		=> md5(microtime(false)),
			'state'				=> 'C',
			'enabled'			=> 1,
		);
		$this->fixDates($subscription, $updates);
		$subscription->save($updates);
		
		// Run the onAKAfterPaymentCallback events
		JLoader::import('joomla.plugin.helper');
		JPluginHelper::importPlugin('akeebasubs');
		$app = JFactory::getApplication();
		$jResponse = $app->triggerEvent('onAKAfterPaymentCallback',array(
			$subscription
		));
		
		// This plugin is a tricky one; it will redirect you to the thank you page
		$slug = FOFModel::getTmpInstance('Levels','AkeebasubsModel')
				->setId($subscription->akeebasubs_level_id)
				->getItem()
				->slug;
		$rootURL = rtrim(JURI::base(),'/');
		$subpathURL = JURI::base(true);
		if(!empty($subpathURL) && ($subpathURL != '/')) {
			$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
		}
		/**
		$url = $rootURL.str_replace('&amp;','&', JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)); 
		/**/ 
		$url = 'index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id;
		$app = JFactory::getApplication();
		$app->redirect($url);
		
		// Everything is fine, no matter what
		return true;
	}
}