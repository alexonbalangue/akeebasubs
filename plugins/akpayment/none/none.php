<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('_JEXEC') or die();

jimport('joomla.plugin.plugin');

class plgAkpaymentNone extends JPlugin
{
	private $ppName = 'none';
	private $ppKey = 'PLG_AKPAYMENT_NONE_TITLE';
	
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
		$jlang->load('plg_akpayment_none', JPATH_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('plg_akpayment_none', JPATH_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('plg_akpayment_none', JPATH_ADMINISTRATOR, null, true);
	}
	
	public function onAKPaymentGetIdentity()
	{
		$ret = array(
			'name'		=> $this->ppName,
			'title'		=> JText::_($this->ppKey)
		);
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
	<input type="submit" value="Complete subscription" />
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
		
		$id = (int)$data['subscription'];
		$updates = array(
			'akeebasubs_subscription_id' => $id,
			'processor_key'		=> md5(microtime(false)),
			'state'				=> 'C',
			'enabled'			=> 1,
			'publish_up'		=> $jStart->toMySQL(),
			'publish_down'		=> $jEnd->toMySQL()
		);
		$subscription->save($updates);
		
		// Run the onAKAfterPaymentCallback events
		jimport('joomla.plugin.helper');
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
		$url = str_replace('&amp;','&', JRoute::_('index.php?option=com_akeebasubs&view=message&layout=default&slug='.$slug.'&layout=order&subid='.$subscription->akeebasubs_subscription_id)); 
		$app = JFactory::getApplication();
		$app->redirect($url);
		
		// Everything is fine, no matter what
		return true;
	}
}